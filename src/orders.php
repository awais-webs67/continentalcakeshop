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

// The customer's preferred delivery window. The shop then sets the exact time.
const DELIVERY_WINDOWS = [
    'any'       => 'Any time',
    'morning'   => 'Morning (9am to 12pm)',
    'afternoon' => 'Afternoon (12pm to 4pm)',
    'evening'   => 'Evening (4pm to 7pm)',
];

const PAYMENT_STATUSES = [
    'pending'               => 'Awaiting payment',
    'awaiting_verification' => 'Payment being checked',
    'paid'                  => 'Paid',
    'failed'                => 'Payment failed',
    'refunded'              => 'Refunded',
];

function generate_order_number(): string
{
    do {
        $number = 'CCS-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    } while (db_value('SELECT 1 FROM orders WHERE order_number = ?', [$number]));
    return $number;
}

/**
 * Saves the order and its lines in one transaction. Prices come from $items,
 * which cart_items() has already loaded from the database.
 */
function create_order(array $data, array $items): array
{
    $subtotal = cart_subtotal($items);
    $deliveryFee = $data['fulfilment'] === 'delivery' ? (float) setting('delivery_fee', '0') : 0.0;
    $total = round($subtotal + $deliveryFee, 2);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $number = generate_order_number();
        $token = bin2hex(random_bytes(16));
        db_run(
            'INSERT INTO orders (order_number, user_id, access_token, customer_name, customer_email, customer_phone, fulfilment,
                                 delivery_address, delivery_suburb, delivery_postcode, delivery_window, pickup_date, pickup_time,
                                 subtotal, delivery_fee, total, payment_method, payment_status, payment_reference, payment_proof, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $number, $data['user_id'] ?? null, $token, $data['name'], $data['email'], $data['phone'], $data['fulfilment'],
                $data['delivery_address'] ?: null, $data['delivery_suburb'] ?: null, $data['delivery_postcode'] ?: null,
                $data['fulfilment'] === 'delivery' ? $data['delivery_window'] : null,
                $data['pickup_date'], $data['fulfilment'] === 'pickup' ? $data['pickup_time'] . ':00' : null,
                $subtotal, $deliveryFee, $total, $data['payment_method'],
                $data['payment_proof'] ? 'awaiting_verification' : 'pending',
                $data['payment_reference'] ?: null, $data['payment_proof'], $data['notes'] ?: null,
            ]
        );
        $orderId = (int) $pdo->lastInsertId();

        $insert = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, size_id, product_name, size_label, unit_price, quantity, cake_message, line_total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($items as $item) {
            $insert->execute([
                $orderId, $item['product_id'], $item['size_id'], $item['name'], $item['size_label'],
                $item['price'], $item['qty'], $item['message'] !== '' ? $item['message'] : null, $item['line_total'],
            ]);
        }
        log_order_event($orderId, 'placed', 'Order placed online, paying by ' . payment_method_label($data['payment_method']) . '.');
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
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

/** Full date and time, e.g. "Saturday 2 November 2026, 3:00pm", or the date plus "time to be confirmed". */
function order_when(array $order): string
{
    $when = format_pickup($order['pickup_date'], $order['pickup_time'] ?: null);
    return $order['pickup_time'] ? $when : $when . ', time to be confirmed';
}

/** Short time for lists: "3pm", or the delivery window while the time is not set. */
function order_time_label(array $order): string
{
    if ($order['pickup_time']) {
        return format_time($order['pickup_time']);
    }
    $window = DELIVERY_WINDOWS[$order['delivery_window'] ?? ''] ?? null;
    return 'Time to confirm' . ($window && $order['delivery_window'] !== 'any' ? ' (' . strtolower(strtok($window, ' ')) . ')' : '');
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

function mark_order_paid(array $order, string $reference): void
{
    $updated = db_run(
        "UPDATE orders SET payment_status = 'paid', payment_reference = ?,
                order_status = IF(order_status = 'new', 'confirmed', order_status)
         WHERE id = ? AND payment_status <> 'paid'",
        [$reference, $order['id']]
    )->rowCount();
    if ($updated > 0) {
        log_order_event((int) $order['id'], 'paid', 'Card payment received through Stripe.');
        send_order_emails(get_order((int) $order['id']));
    }
}
