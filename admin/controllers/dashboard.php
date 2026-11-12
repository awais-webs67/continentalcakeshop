<?php
$admin = require_admin();
$today = date('Y-m-d');
$canOrders = admin_can('orders', $admin);
$b = branch_sql('branch_id');     // one branch, or every branch
$ob = branch_sql('o.branch_id');

$stats = $canOrders ? [
    'due_today'    => (int) db_value("SELECT COUNT(*) FROM orders WHERE pickup_date = ? AND order_status <> 'cancelled'" . $b, [$today]),
    'to_verify'    => (int) db_value("SELECT COUNT(*) FROM orders WHERE payment_status = 'awaiting_verification'" . $b),
    'new_orders'   => (int) db_value("SELECT COUNT(*) FROM orders WHERE order_status = 'new'" . $b),
    'month_sales'  => (float) db_value("SELECT COALESCE(SUM(total), 0) FROM orders WHERE order_status <> 'cancelled' AND created_at >= ?" . $b, [date('Y-m-01')]),
    'month_orders' => (int) db_value("SELECT COUNT(*) FROM orders WHERE order_status <> 'cancelled' AND created_at >= ?" . $b, [date('Y-m-01')]),
] : [];

// Each list shows a few items and says how many there are in all, with a link to the rest,
// so the page stays short however busy the shop is.
$todayOrders = $canOrders ? db_all(
    'SELECT o.*, ' . ORDER_SUMMARY_SQL . " AS summary FROM orders o WHERE o.pickup_date = ? AND o.order_status <> 'cancelled'{$ob}
     ORDER BY o.order_status = 'completed', o.pickup_time IS NULL, o.pickup_time LIMIT 8",
    [$today]
) : [];
$counts = $canOrders ? [
    'today_done'  => (int) db_value("SELECT COUNT(*) FROM orders WHERE pickup_date = ? AND order_status = 'completed'" . $b, [$today]),
    'to_schedule' => (int) db_value("SELECT COUNT(*) FROM orders WHERE fulfilment = 'delivery' AND pickup_time IS NULL AND order_status NOT IN ('cancelled', 'completed')" . $b),
] : [];
// "Waiting for you" shows each kind of job once, with how many there are and the first one or two.
$toSchedule = $canOrders ? db_all("SELECT * FROM orders WHERE fulfilment = 'delivery' AND pickup_time IS NULL AND order_status NOT IN ('cancelled', 'completed'){$b} ORDER BY pickup_date LIMIT 1") : [];
$toVerify = $canOrders ? db_all("SELECT * FROM orders WHERE payment_status = 'awaiting_verification'{$b} ORDER BY pickup_date, created_at LIMIT 2") : [];
$newest = $canOrders ? db_one("SELECT customer_name, created_at FROM orders WHERE order_status = 'new'{$b} ORDER BY created_at DESC, id DESC LIMIT 1") : null;
$recent = $canOrders ? db_all('SELECT o.*, ' . ORDER_SUMMARY_SQL . " AS summary FROM orders o WHERE 1 = 1{$ob} ORDER BY o.created_at DESC, o.id DESC LIMIT 5") : [];
$requests = admin_can('custom', $admin) ? db_all("SELECT * FROM custom_requests WHERE status = 'new'{$b} ORDER BY event_date LIMIT 1") : null;
$counts['requests'] = $requests !== null ? (int) db_value("SELECT COUNT(*) FROM custom_requests WHERE status = 'new'" . $b) : 0;
$threads = admin_can('messages', $admin) ? db_all(
    'SELECT t.*, (SELECT body FROM thread_messages m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) AS last_body
     FROM threads t WHERE t.unread_admin = 1' . branch_sql('t.branch_id') . ' ORDER BY t.last_message_at DESC LIMIT 1'
) : null;
$counts['unread'] = $threads !== null ? (int) db_value('SELECT COUNT(*) FROM threads WHERE unread_admin = 1' . branch_sql('branch_id')) : 0;
$chart = admin_can('reports', $admin) ? report_by_day(date('Y-m-d', strtotime('-13 days')), $today) : null;

admin_view('dashboard', compact('stats', 'counts', 'todayOrders', 'toSchedule', 'toVerify', 'newest', 'recent', 'requests', 'threads', 'chart', 'canOrders') + ['title' => 'Dashboard']);
