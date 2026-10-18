<?php
require_admin();

[$from, $to] = report_range();
$range = [$from . ' 00:00:00', $to . ' 23:59:59'];
$name = static fn (string $what): string => "continental-cakes-{$what}-{$from}-to-{$to}.csv";

switch ((string) ($_GET['type'] ?? 'orders')) {
    case 'items':
        $rows = db_all(
            "SELECT o.order_number, o.created_at, o.customer_name, o.order_status, o.payment_status,
                    i.product_name, i.size_label, i.quantity, i.unit_price, i.line_total, i.cake_message
             FROM order_items i JOIN orders o ON o.id = i.order_id WHERE o.created_at BETWEEN ? AND ? ORDER BY o.created_at, o.id",
            $range
        );
        send_csv($name('items'), ['Order', 'Placed', 'Customer', 'Order status', 'Payment status', 'Cake', 'Size', 'Quantity', 'Unit price', 'Line total', 'Message on cake'],
            array_map('array_values', $rows));
    case 'daily':
        send_csv($name('daily-sales'), ['Date', 'Orders', 'Sales', 'Paid'],
            array_map(static fn ($d) => [$d['day'], $d['orders'], number_format($d['total'], 2, '.', ''), number_format($d['paid'], 2, '.', '')], report_by_day($from, $to)));
    case 'products':
        send_csv($name('products-sold'), ['Cake', 'Category', 'Quantity', 'Revenue'],
            array_map(static fn ($p) => [$p['product_name'], $p['category'], $p['qty'], $p['revenue']], report_top_products($from, $to, 1000)));
    default:
        $rows = db_all(
            "SELECT order_number, created_at, customer_name, customer_email, customer_phone, fulfilment, pickup_date,
                    TIME_FORMAT(pickup_time, '%H:%i'), CONCAT_WS(', ', delivery_address, delivery_suburb, delivery_postcode),
                    subtotal, discount, coupon_code, delivery_fee, total, payment_method, payment_status, payment_reference, order_status
             FROM orders WHERE created_at BETWEEN ? AND ? ORDER BY created_at, id",
            $range
        );
        send_csv($name('orders'), ['Order', 'Placed', 'Customer', 'Email', 'Phone', 'Pickup or delivery', 'Date', 'Time', 'Delivery address',
            'Subtotal', 'Discount', 'Coupon', 'Delivery fee', 'Total', 'Payment method', 'Payment status', 'Payment reference', 'Order status'],
            array_map('array_values', $rows));
}
