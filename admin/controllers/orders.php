<?php
// All orders: quick views (what needs doing, today, tomorrow, coming up...), search and filters.
require_admin();

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$open = "o.order_status NOT IN ('completed', 'cancelled')";
// Each quick view: [label, help text, SQL condition, sort]
$views = [
    'action'   => ['Needs action', 'New orders, payments to check and deliveries without a time',
        "(o.order_status = 'new' OR o.payment_status = 'awaiting_verification' OR (o.fulfilment = 'delivery' AND o.pickup_time IS NULL AND {$open}))", 'o.pickup_date, o.pickup_time'],
    'today'    => ['Today', 'Pickups and deliveries today', "o.pickup_date = '{$today}' AND o.order_status <> 'cancelled'", 'o.pickup_time IS NOT NULL, o.pickup_time'],
    'tomorrow' => ['Tomorrow', 'Bake list for tomorrow', "o.pickup_date = '{$tomorrow}' AND o.order_status <> 'cancelled'", 'o.pickup_time IS NOT NULL, o.pickup_time'],
    'upcoming' => ['Coming up', 'Every order for a later day', "o.pickup_date > '{$tomorrow}' AND o.order_status <> 'cancelled'", 'o.pickup_date, o.pickup_time'],
    'unpaid'   => ['Money owing', 'Orders with a balance still to pay', "{$open} AND o.amount_paid - o.amount_refunded < o.total - 0.004", 'o.pickup_date, o.pickup_time'],
    'done'     => ['Completed', 'Collected or delivered', "o.order_status = 'completed'", 'o.pickup_date DESC, o.id DESC'],
    'cancelled' => ['Cancelled', 'Cancelled orders', "o.order_status = 'cancelled'", 'o.created_at DESC'],
    'all'      => ['All orders', 'Newest first', '1 = 1', 'o.created_at DESC, o.id DESC'],
];
$current = isset($views[$_GET['view'] ?? '']) ? $_GET['view'] : 'all';

$status = (string) ($_GET['status'] ?? '');
$payment = (string) ($_GET['payment'] ?? '');
$channel = (string) ($_GET['channel'] ?? '');
$fulfilment = (string) ($_GET['fulfilment'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$from = (string) ($_GET['from'] ?? '');
$to = (string) ($_GET['to'] ?? '');
$sort = in_array($_GET['sort'] ?? '', ['placed', 'date'], true) ? $_GET['sort'] : '';

$where = [$views[$current][2]];
$params = [];
if (isset(ORDER_STATUSES[$status])) {
    $where[] = 'o.order_status = ?';
    $params[] = $status;
}
if (isset(PAYMENT_STATUSES[$payment])) {
    $where[] = 'o.payment_status = ?';
    $params[] = $payment;
}
if (isset(ORDER_CHANNELS[$channel])) {
    $where[] = 'o.channel = ?';
    $params[] = $channel;
}
if (in_array($fulfilment, ['pickup', 'delivery'], true)) {
    $where[] = $fulfilment === 'delivery' ? "o.fulfilment = 'delivery'" : "o.fulfilment <> 'delivery'";
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
$orderBy = match ($sort) {
    'placed' => 'o.created_at DESC, o.id DESC',
    'date'   => 'o.pickup_date, o.pickup_time IS NOT NULL, o.pickup_time',
    default  => $views[$current][3],
};

$result = paginate(
    'SELECT o.*, ' . ORDER_SUMMARY_SQL . " AS summary FROM orders o WHERE {$whereSql} ORDER BY {$orderBy}",
    "SELECT COUNT(*) FROM orders o WHERE {$whereSql}",
    $params
);
$viewCounts = [];
foreach ($views as $key => $v) {
    $viewCounts[$key] = (int) db_value('SELECT COUNT(*) FROM orders o WHERE ' . $v[2]);
}
$filtersOn = $status !== '' || $payment !== '' || $channel !== '' || $fulfilment !== '' || $from !== '' || $to !== '' || $sort !== '';

admin_view('orders', compact('result', 'views', 'current', 'viewCounts', 'status', 'payment', 'channel', 'fulfilment', 'q', 'from', 'to', 'sort', 'filtersOn') + ['title' => 'Orders']);
