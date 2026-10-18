<?php
declare(strict_types=1);

const ORDER_STATUSES = [
    'new'         => 'Received',
    'confirmed'   => 'Confirmed',
    'in_progress' => 'Being prepared',
    'ready'       => 'Ready for pickup',
    'completed'   => 'Completed',
    'cancelled'   => 'Cancelled',
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
                                 delivery_address, pickup_date, pickup_time, subtotal, delivery_fee, total, payment_method,
                                 payment_status, payment_reference, payment_proof, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $number, $data['user_id'] ?? null, $token, $data['name'], $data['email'], $data['phone'], $data['fulfilment'],
                $data['delivery_address'] ?: null, $data['pickup_date'], $data['pickup_time'] . ':00',
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
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return get_order($orderId);
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
        send_order_emails(get_order((int) $order['id']));
    }
}
