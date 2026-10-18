<?php
require_admin();

$status = (string) ($_GET['status'] ?? '');
$payment = (string) ($_GET['payment'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$from = (string) ($_GET['from'] ?? '');
$to = (string) ($_GET['to'] ?? '');

$where = ['1 = 1'];
$params = [];
if (isset(ORDER_STATUSES[$status])) {
    $where[] = 'o.order_status = ?';
    $params[] = $status;
}
if (isset(PAYMENT_STATUSES[$payment])) {
    $where[] = 'o.payment_status = ?';
    $params[] = $payment;
}
if ($q !== '') {
    $where[] = '(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ? OR o.customer_phone LIKE ?)';
    array_push($params, ...array_fill(0, 4, '%' . $q . '%'));
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $where[] = 'o.pickup_date >= ?';
    $params[] = $from;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $where[] = 'o.pickup_date <= ?';
    $params[] = $to;
}
$whereSql = implode(' AND ', $where);

$result = paginate(
    "SELECT o.*, (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) AS item_count FROM orders o WHERE {$whereSql} ORDER BY o.created_at DESC, o.id DESC",
    "SELECT COUNT(*) FROM orders o WHERE {$whereSql}",
    $params
);
$counts = array_column(db_all('SELECT order_status, COUNT(*) AS n FROM orders GROUP BY order_status'), 'n', 'order_status');

admin_view('orders', compact('result', 'status', 'payment', 'q', 'from', 'to', 'counts') + ['title' => 'Orders']);
