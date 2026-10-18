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

/** Methods the customer can pick right now. */
function available_payment_methods(): array
{
    return array_filter(payment_method_definitions(), static fn ($m) => $m['enabled'] && $m['configured']);
}

function payment_method_label(string $method): string
{
    return payment_method_definitions()[$method]['label'] ?? ucfirst($method);
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

/** Creates a Stripe Checkout session for an order and returns the hosted payment URL. */
function stripe_create_checkout(array $order, array $items): string
{
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
    foreach ($items as $item) {
        $params['line_items'][] = [
            'quantity'   => (int) $item['quantity'],
            'price_data' => [
                'currency'     => 'aud',
                'unit_amount'  => (int) round((float) $item['unit_price'] * 100),
                'product_data' => ['name' => $item['product_name'] . ' (' . $item['size_label'] . ')'],
            ],
        ];
    }
    if ((float) $order['delivery_fee'] > 0) {
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
    if ((float) ($order['discount'] ?? 0) > 0) {
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
    if ($order['payment_status'] === 'paid') {
        return true;
    }
    if ($sessionId === '' || $sessionId !== $order['stripe_session_id']) {
        return false;
    }
    $session = stripe_request('GET', 'checkout/sessions/' . rawurlencode($sessionId));
    if (($session['payment_status'] ?? '') === 'paid') {
        mark_order_paid($order, (string) ($session['payment_intent'] ?? $sessionId));
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

/** Refunds a Stripe card payment in full. Returns the refund id. */
function stripe_refund(array $order): string
{
    $intent = (string) $order['payment_reference'];
    if (!str_starts_with($intent, 'pi_')) {
        throw new RuntimeException('This order has no Stripe payment to refund.');
    }
    $refund = stripe_request('POST', 'refunds', ['payment_intent' => $intent, 'metadata' => ['order_number' => $order['order_number']]]);
    return (string) ($refund['id'] ?? '');
}
