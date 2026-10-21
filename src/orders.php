<?php
declare(strict_types=1);

const ORDER_STATUSES = [
    'new'         => 'Received',
    'confirmed'   => 'Confirmed',
    'in_progress' => 'Being prepared',
    'ready'       => 'Ready',
    'completed'   => 'Completed',
    'cancelled'   => 'Cancelled',
];

// Window names used by orders placed before the windows became editable in Settings.
const LEGACY_DELIVERY_WINDOWS = [
    'morning'   => 'Morning (9am to 12pm)',
    'afternoon' => 'Afternoon (12pm to 4pm)',
    'evening'   => 'Evening (4pm to 7pm)',
];

const PAYMENT_STATUSES = [
    'pending'               => 'Awaiting payment',
    'awaiting_verification' => 'Payment being checked',
    'partially_paid'        => 'Part paid',
    'paid'                  => 'Paid',
    'failed'                => 'Payment failed',
    'refunded'              => 'Refunded',
];

// Where an order came from. Staff orders are typed in the admin panel.
const ORDER_CHANNELS = [
    'online'   => 'Website',
    'phone'    => 'Phone order',
    'in_store' => 'In the shop',
    'quote'    => 'Custom cake quote',
];

/** Thrown when a coupon runs out between applying it and placing the order. */
final class CouponUnavailable extends RuntimeException
{
}

function generate_order_number(): string
{
    do {
        $number = 'CCS-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    } while (db_value('SELECT 1 FROM orders WHERE order_number = ?', [$number]));
    return $number;
}

/**
 * Saves the order and its lines in one transaction. Prices come from $items, which
 * cart_order_lines() (or the admin order form) has already loaded from the database.
 *
 * Optional $data keys used by staff orders: channel, custom_request_id, payment_status, admin_id.
 * $totals replaces the worked-out totals when staff set the delivery fee or a discount by hand.
 */
function create_order(array $data, array $items, ?array $coupon = null, ?array $totals = null): array
{
    $totals ??= order_totals(cart_subtotal($items), $data['fulfilment'], $coupon);
    $channel = isset(ORDER_CHANNELS[$data['channel'] ?? '']) ? $data['channel'] : 'online';

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Count the coupon use inside the transaction, so a code with one use left cannot be used twice.
        if ($coupon && !redeem_coupon($coupon)) {
            throw new CouponUnavailable('Sorry, the coupon ' . $coupon['code'] . ' has just been used up. Please remove it to continue.');
        }
        $number = generate_order_number();
        $token = bin2hex(random_bytes(16));
        $pickupTime = ($data['pickup_time'] ?? '') !== '' ? substr($data['pickup_time'], 0, 5) . ':00' : null;
        db_run(
            'INSERT INTO orders (order_number, user_id, channel, custom_request_id, access_token, customer_name, customer_email, customer_phone, fulfilment,
                                 delivery_address, delivery_suburb, delivery_postcode, delivery_window, pickup_date, pickup_time,
                                 subtotal, delivery_fee, coupon_code, discount, total, payment_method, payment_status, payment_reference, payment_proof, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $number, $data['user_id'] ?? null, $channel, $data['custom_request_id'] ?? null, $token,
                $data['name'], $data['email'], $data['phone'], $data['fulfilment'],
                ($data['delivery_address'] ?? '') ?: null, ($data['delivery_suburb'] ?? '') ?: null, ($data['delivery_postcode'] ?? '') ?: null,
                $data['fulfilment'] === 'delivery' && ($data['delivery_window'] ?? '') !== '' ? $data['delivery_window'] : null,
                $data['pickup_date'], $data['fulfilment'] === 'pickup' || $channel !== 'online' ? $pickupTime : null,
                $totals['subtotal'], $totals['delivery_fee'], $coupon['code'] ?? null, $totals['discount'], $totals['total'], $data['payment_method'],
                !empty($data['payment_proof']) ? 'awaiting_verification' : 'pending',
                ($data['payment_reference'] ?? '') ?: null, $data['payment_proof'] ?? null, ($data['notes'] ?? '') ?: null,
            ]
        );
        $orderId = (int) $pdo->lastInsertId();
        insert_order_lines($orderId, $items);

        $how = match ($channel) {
            'phone'    => 'Phone order entered by staff',
            'in_store' => 'Order taken in the shop',
            'quote'    => 'Custom cake quote accepted online',
            default    => 'Order placed online',
        };
        log_order_event($orderId, 'placed', $how . ', paying by ' . payment_method_label($data['payment_method']) . '.'
            . ($coupon ? ' Coupon ' . $coupon['code'] . ' saved ' . money($totals['discount']) . '.' : '')
            . ($coupon === null && $totals['discount'] > 0 ? ' Discount of ' . money($totals['discount']) . '.' : ''), $data['admin_id'] ?? null);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return get_order($orderId);
}

/** Writes order lines: cakes (with size and options), add-ons and custom lines typed by staff. */
function insert_order_lines(int $orderId, array $items): void
{
    $insert = db()->prepare(
        'INSERT INTO order_items (order_id, product_id, size_id, addon_id, product_name, size_label, options, unit_price, quantity, cake_message, line_total)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($items as $item) {
        $insert->execute([
            $orderId, $item['product_id'] ?? null, $item['size_id'] ?? null, $item['addon_id'] ?? null,
            str_limit((string) $item['name'], 160), str_limit((string) ($item['size_label'] ?? ''), 60),
            ($item['options'] ?? '') !== '' ? str_limit($item['options'], 255) : null,
            $item['price'], $item['qty'], ($item['message'] ?? '') !== '' ? $item['message'] : null, $item['line_total'],
        ]);
    }
}

// ---------------------------------------------------------------------------
// Payments and refunds. Every amount is a row in the payments table; the order keeps
// the totals (amount_paid, amount_refunded) and a status worked out from them.
// ---------------------------------------------------------------------------

/** What the customer still owes (negative when the shop owes money back). */
function order_balance(array $order): float
{
    return round((float) $order['total'] - ((float) $order['amount_paid'] - (float) $order['amount_refunded']), 2);
}

/** Money kept from the order after refunds. */
function order_net_paid(array $order): float
{
    return round((float) $order['amount_paid'] - (float) $order['amount_refunded'], 2);
}

function get_order_payments(int $orderId): array
{
    return db_all(
        'SELECT p.*, a.name AS admin_name FROM payments p LEFT JOIN admins a ON a.id = p.admin_id WHERE p.order_id = ? ORDER BY p.created_at, p.id',
        [$orderId]
    );
}

/**
 * Records money received. A reference that was already recorded is ignored, so Stripe's return
 * page and webhook can both report the same payment safely. Returns true when it was new.
 */
function record_payment(array $order, float $amount, string $method, ?string $reference = null, string $note = '', ?int $adminId = null): bool
{
    return add_payment_row($order, 'payment', $amount, $method, $reference, $note, $adminId);
}

function record_refund(array $order, float $amount, string $method, ?string $reference = null, string $note = '', ?int $adminId = null): bool
{
    return add_payment_row($order, 'refund', $amount, $method, $reference, $note, $adminId);
}

function add_payment_row(array $order, string $kind, float $amount, string $method, ?string $reference, string $note, ?int $adminId): bool
{
    $amount = round($amount, 2);
    if ($amount <= 0) {
        return false;
    }
    $added = db_run(
        'INSERT IGNORE INTO payments (order_id, kind, amount, method, reference, note, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$order['id'], $kind, $amount, str_limit($method, 20), $reference !== null && $reference !== '' ? str_limit($reference, 190) : null, $note !== '' ? str_limit($note, 255) : null, $adminId]
    )->rowCount() === 1;
    if ($added) {
        refresh_order_payment((int) $order['id']);
        log_order_event((int) $order['id'], $kind === 'refund' ? 'refunded' : 'paid',
            ($kind === 'refund' ? 'Refund of ' : 'Payment of ') . money($amount) . ' by ' . payment_method_label($method) . '.' . ($note !== '' ? ' ' . $note : ''), $adminId);
    }
    return $added;
}

/** Works out amount paid, amount refunded and the payment status from the payments table. */
function refresh_order_payment(int $orderId): void
{
    $order = get_order($orderId);
    if (!$order) {
        return;
    }
    $sums = db_one(
        "SELECT COALESCE(SUM(CASE WHEN kind = 'payment' THEN amount END), 0) AS paid,
                COALESCE(SUM(CASE WHEN kind = 'refund' THEN amount END), 0) AS refunded
         FROM payments WHERE order_id = ?",
        [$orderId]
    );
    $paid = round((float) $sums['paid'], 2);
    $refunded = round((float) $sums['refunded'], 2);
    $net = $paid - $refunded;
    $status = match (true) {
        $paid > 0 && $refunded > 0 && $net <= 0.004             => 'refunded',
        $paid > 0 && $net >= (float) $order['total'] - 0.004     => 'paid',
        $net > 0.004                                             => 'partially_paid',
        $paid > 0                                                => 'refunded',
        default                                                  => $order['payment_status'],
    };
    db_run(
        "UPDATE orders SET amount_paid = ?, amount_refunded = ?, payment_status = ?,
                order_status = IF(order_status = 'new' AND ? IN ('paid', 'partially_paid'), 'confirmed', order_status)
         WHERE id = ?",
        [$paid, $refunded, $status, $status, $orderId]
    );
}

/** Puts the order lines' sum, delivery fee and discount back together after staff change the items. */
function recalculate_order_total(int $orderId, ?float $deliveryFee = null, ?float $discount = null): array
{
    $order = get_order($orderId);
    $subtotal = round((float) db_value('SELECT COALESCE(SUM(line_total), 0) FROM order_items WHERE order_id = ?', [$orderId]), 2);
    $deliveryFee = round(max(0, $deliveryFee ?? (float) $order['delivery_fee']), 2);
    $discount = round(min($subtotal, max(0, $discount ?? (float) $order['discount'])), 2);
    db_run('UPDATE orders SET subtotal = ?, delivery_fee = ?, discount = ?, total = ? WHERE id = ?',
        [$subtotal, $deliveryFee, $discount, round($subtotal - $discount + $deliveryFee, 2), $orderId]);
    refresh_order_payment($orderId);
    return get_order($orderId);
}

// ---------------------------------------------------------------------------
// Display helpers shared by the order page, emails, account and admin
// ---------------------------------------------------------------------------

function is_delivery(array $order): bool
{
    return ($order['fulfilment'] ?? 'pickup') === 'delivery';
}

/** "Ready for pickup" reads "Out for delivery" on delivery orders. */
function order_status_label(array $order, ?string $status = null): string
{
    $status ??= $order['order_status'];
    if ($status === 'ready') {
        return is_delivery($order) ? 'Out for delivery' : 'Ready for pickup';
    }
    return ORDER_STATUSES[$status] ?? ucfirst($status);
}

/** Delivery time windows the customer can prefer, set in Admin > Settings > Ordering. */
function delivery_windows(): array
{
    $windows = json_decode(setting('delivery_windows', '[]'), true);
    return is_array($windows) ? array_values(array_filter(array_map('strval', $windows), static fn ($w) => trim($w) !== '')) : [];
}

function delivery_window_label(?string $window): string
{
    if ($window === null || $window === '' || $window === 'any') {
        return 'Any time';
    }
    return LEGACY_DELIVERY_WINDOWS[$window] ?? $window;
}

/** Full date and time, e.g. "Saturday 2 November 2026, 1pm to 3pm", or the date plus "time to be confirmed". */
function order_when(array $order): string
{
    if (!$order['pickup_time']) {
        return format_pickup($order['pickup_date'], null) . ', time to be confirmed';
    }
    return format_pickup($order['pickup_date'], null) . ', ' . order_time_label($order);
}

/** Short time for lists: "3pm", "1pm to 3pm", or the delivery window while the time is not set. */
function order_time_label(array $order): string
{
    if ($order['pickup_time']) {
        $to = $order['delivery_time_to'] ?? null;
        return format_time($order['pickup_time']) . ($to ? ' to ' . format_time($to) : '');
    }
    $window = $order['delivery_window'] ?? null;
    return 'Time to confirm' . ($window && $window !== 'any' ? ' (' . delivery_window_label($window) . ')' : '');
}

/** Second line under an order item: size, chosen options and the cake message. */
function order_item_detail(array $item): string
{
    $parts = array_filter([
        (string) ($item['size_label'] ?? ''),
        (string) ($item['options'] ?? ''),
        ($item['cake_message'] ?? '') !== '' && $item['cake_message'] !== null ? 'Message: "' . $item['cake_message'] . '"' : '',
    ], static fn ($p) => $p !== '');
    return implode(' · ', $parts);
}

function order_address(array $order): string
{
    if (!is_delivery($order)) {
        return store_address_line();
    }
    $place = trim(($order['delivery_suburb'] ?? '') . ' ' . ($order['delivery_postcode'] ?? ''));
    return trim((string) $order['delivery_address'] . ($place !== '' ? ', ' . $place : ''));
}

function log_order_event(int $orderId, string $event, string $message, ?int $adminId = null): void
{
    db_run('INSERT INTO order_events (order_id, admin_id, event, message) VALUES (?, ?, ?, ?)', [$orderId, $adminId, $event, str_limit($message, 500)]);
}

function get_order_events(int $orderId): array
{
    return db_all(
        'SELECT e.*, a.name AS admin_name FROM order_events e LEFT JOIN admins a ON a.id = e.admin_id
         WHERE e.order_id = ? ORDER BY e.created_at DESC, e.id DESC',
        [$orderId]
    );
}

function get_order(int $id): ?array
{
    return db_one('SELECT * FROM orders WHERE id = ?', [$id]);
}

function get_order_by_number(string $number): ?array
{
    return db_one('SELECT * FROM orders WHERE order_number = ?', [$number]);
}

function get_order_items(int $orderId): array
{
    return db_all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);
}

/** Private link the customer uses to view their order. */
function order_url(array $order): string
{
    return url('order/' . rawurlencode($order['order_number'])) . '?t=' . $order['access_token'];
}

/**
 * A Stripe card payment came through (from the return page or the webhook; both are safe).
 * $amount is what Stripe charged; null means the whole balance.
 */
function mark_order_paid(array $order, string $reference, ?float $amount = null): void
{
    $firstPayment = (float) $order['amount_paid'] <= 0;
    $amount ??= max(0, order_balance($order));
    if (!record_payment($order, $amount, PAYMENT_STRIPE, $reference, 'Card payment through Stripe.')) {
        return;
    }
    db_run('UPDATE orders SET payment_reference = ? WHERE id = ?', [$reference, $order['id']]);
    $updated = get_order((int) $order['id']);
    if ($firstPayment && $updated['channel'] === 'online') {
        send_order_emails($updated);
    } else {
        send_order_update_email($updated, $updated['payment_status'] === 'paid' ? 'Payment received' : 'Deposit received',
            'Thank you, we have received your payment of ' . money($amount) . '.'
            . (order_balance($updated) > 0 ? ' The balance of ' . money(order_balance($updated)) . ' is due before ' . (is_delivery($updated) ? 'delivery' : 'pickup') . '.' : ''));
        $notify = setting('order_notification_email');
        if ($notify !== '' && $updated['channel'] !== 'online') {
            send_mail($notify, 'Card payment ' . money($amount) . ' for ' . $updated['order_number'],
                render_email('order-new-admin', ['order' => $updated, 'items' => get_order_items((int) $updated['id']), 'title' => 'Card payment received', 'preheader' => money($amount) . ' from ' . $updated['customer_name']]),
                null, $updated['customer_email']);
        }
    }
}
