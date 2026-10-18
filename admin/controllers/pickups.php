<?php
require_admin();
$start = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['date'] ?? '')) ? $_GET['date'] : date('Y-m-d');
$days = max(1, min(14, query_int('days', 7)));
$end = date('Y-m-d', strtotime($start . ' +' . ($days - 1) . ' days'));

$orders = db_all(
    "SELECT o.*, (SELECT GROUP_CONCAT(CONCAT(quantity, ' x ', product_name, ' (', size_label, ')') SEPARATOR ', ') FROM order_items WHERE order_id = o.id) AS summary
     FROM orders o WHERE o.pickup_date BETWEEN ? AND ? AND o.order_status <> 'cancelled' ORDER BY o.pickup_date, o.pickup_time",
    [$start, $end]
);
$byDay = [];
foreach ($orders as $o) {
    $byDay[$o['pickup_date']][] = $o;
}
admin_view('pickups', compact('start', 'end', 'days', 'byDay') + ['title' => 'Pickup schedule']);
