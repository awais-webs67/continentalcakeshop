<?php
declare(strict_types=1);

// Payment methods the admin can switch on or off from the settings table.
// A method is offered at checkout only when it is enabled AND fully configured.

const PAYMENT_STRIPE = 'stripe';
const PAYMENT_PAYID = 'payid';
const PAYMENT_BANK = 'bank';

function payment_method_definitions(): array
{
    return [
        PAYMENT_STRIPE => [
            'label'        => setting('payment_stripe_label', 'Credit or debit card'),
            'description'  => setting('payment_stripe_note', 'Pay securely with Visa, Mastercard, Amex, Apple Pay or Google Pay through Stripe.'),
            'enabled'      => setting_bool('payment_stripe_enabled'),
            'configured'   => setting('stripe_secret_key') !== '',
            'needs_proof'  => false,
        ],
        PAYMENT_PAYID => [
            'label'        => setting('payment_payid_label', 'PayID'),
            'description'  => setting('payment_payid_note', 'Send the total to our PayID from your banking app, then upload a screenshot of the payment.'),
            'enabled'      => setting_bool('payment_payid_enabled'),
            'configured'   => setting('payid_identifier') !== '',
            'needs_proof'  => true,
            'details'      => [
                'PayID'        => setting('payid_identifier'),
                'Account name' => setting('payid_account_name', setting('store_short_name')),
            ],
        ],
        PAYMENT_BANK => [
            'label'        => setting('payment_bank_label', 'Bank transfer'),
            'description'  => setting('payment_bank_note', 'Transfer the total to our bank account, then upload a screenshot or PDF of the receipt.'),
            'enabled'      => setting_bool('payment_bank_enabled'),
            'configured'   => setting('bank_bsb') !== '' && setting('bank_account_number') !== '',
            'needs_proof'  => true,
            'details'      => [
                'Account name'   => setting('bank_account_name', setting('store_short_name')),
                'BSB'            => setting('bank_bsb'),
                'Account number' => setting('bank_account_number'),
            ],
        ],
    ];
}

// Ways staff can record money taken in the shop or over the phone.
const IN_STORE_PAYMENT_METHODS = [
    'cash'   => 'Cash',
    'eftpos' => 'Card in the shop (EFTPOS)',
];

/** Every method staff can record a payment with (online methods are listed even when switched off). */
function staff_payment_methods(): array
{
    $list = IN_STORE_PAYMENT_METHODS;
    foreach (payment_method_definitions() as $key => $method) {
        $list[$key] = $method['label'];
    }
    return $list;
}

/** Methods the customer can pick right now. */
function available_payment_methods(): array
{
    return array_filter(payment_method_definitions(), static fn ($m) => $m['enabled'] && $m['configured']);
}

function payment_method_label(string $method): string
{
    return payment_method_definitions()[$method]['label'] ?? IN_STORE_PAYMENT_METHODS[$method] ?? ucfirst($method);
}

// ---------------------------------------------------------------------------
// Stripe Checkout (REST API over cURL, so no Composer packages are needed)
// ---------------------------------------------------------------------------

function stripe_request(string $method, string $path, array $params = []): array
{
    $ch = curl_init('https://api.stripe.com/v1/' . ltrim($path, '/'));
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => setting('stripe_secret_key') . ':',
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => ['Stripe-Version: 2024-06-20'],
    ];
    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = http_build_query($params);
    }
    curl_setopt_array($ch, $options);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        throw new RuntimeException('Could not reach Stripe: ' . $error);
    }
    $data = json_decode((string) $body, true) ?: [];
    if ($status >= 400) {
        throw new RuntimeException('Stripe error: ' . ($data['error']['message'] ?? 'HTTP ' . $status));
    }
    return $data;
}

/**
 * Creates a Stripe Checkout session for an order and returns the hosted payment URL.
 * Normally it charges what is still owed. $amount charges a set part instead (a deposit).
 */
function stripe_create_checkout(array $order, array $items, ?float $amount = null, string $label = ''): string
{
    $due = round($amount ?? order_balance($order), 2);
    if ($due <= 0) {
        throw new RuntimeException('Nothing is owing on this order.');
    }
    $params = [
        'mode'                 => 'payment',
        'customer_email'       => $order['customer_email'],
        'client_reference_id'  => $order['order_number'],
        'metadata'             => ['order_number' => $order['order_number']],
        'payment_intent_data'  => ['metadata' => ['order_number' => $order['order_number']]],
        'success_url'          => order_url($order) . '&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'           => order_url($order) . '&cancelled=1',
        'line_items'           => [],
    ];
    // Part payments (a deposit, or the balance after one) are one line with the amount owed.
    if (!$items || abs($due - (float) $order['total']) > 0.004) {
        $params['line_items'][] = [
            'quantity'   => 1,
            'price_data' => [
                'currency'     => 'aud',
                'unit_amount'  => (int) round($due * 100),
                'product_data' => ['name' => $label !== '' ? $label : 'Balance for order ' . $order['order_number']],
            ],
        ];
        $items = [];
    }
    foreach ($items as $item) {
        $params['line_items'][] = [
            'quantity'   => (int) $item['quantity'],
            'price_data' => [
                'currency'     => 'aud',
                'unit_amount'  => (int) round((float) $item['unit_price'] * 100),
                'product_data' => ['name' => $item['product_name'] . (trim($item['size_label'] . ($item['options'] ? ', ' . $item['options'] : ''), ', ') !== '' ? ' (' . trim($item['size_label'] . ($item['options'] ? ', ' . $item['options'] : ''), ', ') . ')' : '')],
            ],
        ];
    }
    if ($items && (float) $order['delivery_fee'] > 0) {
        $params['line_items'][] = [
            'quantity'   => 1,
            'price_data' => [
                'currency'     => 'aud',
                'unit_amount'  => (int) round((float) $order['delivery_fee'] * 100),
                'product_data' => ['name' => 'Delivery'],
            ],
        ];
    }

    // A coupon discount becomes a one-off Stripe coupon for this exact amount.
    if ($items && (float) ($order['discount'] ?? 0) > 0) {
        $stripeCoupon = stripe_request('POST', 'coupons', [
            'amount_off'      => (int) round((float) $order['discount'] * 100),
            'currency'        => 'aud',
            'duration'        => 'once',
            'max_redemptions' => 1,
            'name'            => 'Coupon ' . $order['coupon_code'],
        ]);
        $params['discounts'] = [['coupon' => $stripeCoupon['id']]];
    }

    $session = stripe_request('POST', 'checkout/sessions', $params);
    db_run('UPDATE orders SET stripe_session_id = ? WHERE id = ?', [$session['id'], $order['id']]);
    return $session['url'];
}

/** Checks a returning Checkout session with Stripe and marks the order paid if it is. */
function stripe_confirm_session(array $order, string $sessionId): bool
{
    if ($order['payment_status'] === 'paid' && order_balance($order) <= 0) {
        return true;
    }
    if ($sessionId === '' || $sessionId !== $order['stripe_session_id']) {
        return false;
    }
    $session = stripe_request('GET', 'checkout/sessions/' . rawurlencode($sessionId));
    if (($session['payment_status'] ?? '') === 'paid') {
        mark_order_paid($order, (string) ($session['payment_intent'] ?? $sessionId), isset($session['amount_total']) ? $session['amount_total'] / 100 : null);
        return true;
    }
    return false;
}

/** Verifies the Stripe-Signature header of a webhook request. */
function stripe_verify_webhook(string $payload, string $header, string $secret, int $tolerance = 300): bool
{
    $timestamp = null;
    $signatures = [];
    foreach (explode(',', $header) as $part) {
        [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($k === 't') {
            $timestamp = (int) $v;
        } elseif ($k === 'v1') {
            $signatures[] = $v;
        }
    }
    if (!$timestamp || !$signatures || abs(time() - $timestamp) > $tolerance) {
        return false;
    }
    $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    foreach ($signatures as $signature) {
        if (hash_equals($expected, $signature)) {
            return true;
        }
    }
    return false;
}

/**
 * Refunds $amount of the card payments on an order through Stripe, newest payment first
 * (a deposit and a balance are two payments). Each refund is recorded. Returns the refund ids.
 */
function stripe_refund(array $order, float $amount, ?int $adminId = null): array
{
    $amount = round($amount, 2);
    $payments = db_all(
        "SELECT reference, amount FROM payments WHERE order_id = ? AND kind = 'payment' AND reference LIKE 'pi\_%' ORDER BY id DESC",
        [$order['id']]
    );
    // Orders paid before payment history was kept have the Stripe payment on the order itself.
    if (!$payments && str_starts_with((string) $order['payment_reference'], 'pi_')) {
        $payments = [['reference' => $order['payment_reference'], 'amount' => $order['amount_paid']]];
    }
    if (!$payments) {
        throw new RuntimeException('This order has no Stripe card payment to refund.');
    }
    $ids = [];
    foreach ($payments as $payment) {
        if ($amount <= 0) {
            break;
        }
        // What is still refundable on this card payment, according to Stripe.
        $intent = stripe_request('GET', 'payment_intents/' . rawurlencode($payment['reference']));
        $refundable = (float) $payment['amount'];
        if (!empty($intent['latest_charge'])) {
            $charge = stripe_request('GET', 'charges/' . rawurlencode((string) $intent['latest_charge']));
            $refundable = round(((int) ($charge['amount_captured'] ?? 0) - (int) ($charge['amount_refunded'] ?? 0)) / 100, 2);
        }
        $part = min($amount, $refundable);
        if ($part <= 0) {
            continue;
        }
        $refund = stripe_request('POST', 'refunds', [
            'payment_intent' => $payment['reference'],
            'amount'         => (int) round($part * 100),
            'metadata'       => ['order_number' => $order['order_number']],
        ]);
        $id = (string) ($refund['id'] ?? '');
        record_refund($order, $part, PAYMENT_STRIPE, $id !== '' ? $id : null, 'Card refund through Stripe.', $adminId);
        $order = get_order((int) $order['id']);
        $ids[] = $id;
        $amount = round($amount - $part, 2);
    }
    if ($amount > 0.004) {
        throw new RuntimeException('Stripe could only refund part of that amount; ' . money($amount) . ' was not refunded.');
    }
    return $ids;
}

/** Refunds made in the Stripe dashboard are copied onto the order (each refund only once). */
function stripe_sync_refunds(array $order, string $chargeId): void
{
    $refunds = stripe_request('GET', 'refunds?charge=' . rawurlencode($chargeId) . '&limit=100');
    foreach ($refunds['data'] ?? [] as $refund) {
        if (($refund['status'] ?? '') !== 'failed' && ($refund['status'] ?? '') !== 'canceled') {
            record_refund($order, ((int) $refund['amount']) / 100, PAYMENT_STRIPE, (string) $refund['id'], 'Refunded in the Stripe dashboard.');
            $order = get_order((int) $order['id']);
        }
    }
}
