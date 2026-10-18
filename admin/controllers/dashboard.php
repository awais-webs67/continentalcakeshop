<?php
$admin = require_admin();
$today = date('Y-m-d');
$canOrders = admin_can('orders', $admin);

$stats = $canOrders ? [
    'due_today'    => (int) db_value("SELECT COUNT(*) FROM orders WHERE pickup_date = ? AND order_status <> 'cancelled'", [$today]),
    'to_verify'    => (int) db_value("SELECT COUNT(*) FROM orders WHERE payment_status = 'awaiting_verification'"),
    'new_orders'   => (int) db_value("SELECT COUNT(*) FROM orders WHERE order_status = 'new'"),
    'month_sales'  => (float) db_value("SELECT COALESCE(SUM(total), 0) FROM orders WHERE order_status <> 'cancelled' AND created_at >= ?", [date('Y-m-01')]),
    'month_orders' => (int) db_value("SELECT COUNT(*) FROM orders WHERE order_status <> 'cancelled' AND created_at >= ?", [date('Y-m-01')]),
] : [];

$todayOrders = $canOrders ? db_all(
    "SELECT o.*, (SELECT GROUP_CONCAT(CONCAT(i.quantity, ' x ', i.product_name) SEPARATOR ', ') FROM order_items i WHERE i.order_id = o.id) AS summary
     FROM orders o WHERE o.pickup_date = ? AND o.order_status <> 'cancelled' ORDER BY o.pickup_time IS NULL, o.pickup_time",
    [$today]
) : [];
$toSchedule = $canOrders ? db_all("SELECT * FROM orders WHERE fulfilment = 'delivery' AND pickup_time IS NULL AND order_status NOT IN ('cancelled', 'completed') ORDER BY pickup_date LIMIT 8") : [];
$toVerify = $canOrders ? db_all("SELECT * FROM orders WHERE payment_status = 'awaiting_verification' ORDER BY created_at LIMIT 6") : [];
$recent = $canOrders ? db_all('SELECT * FROM orders ORDER BY created_at DESC, id DESC LIMIT 6') : [];
$requests = admin_can('custom', $admin) ? db_all("SELECT * FROM custom_requests WHERE status = 'new' ORDER BY event_date LIMIT 5") : null;
$threads = admin_can('messages', $admin) ? db_all(
    'SELECT t.*, (SELECT body FROM thread_messages m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) AS last_body
     FROM threads t WHERE t.unread_admin = 1 ORDER BY t.last_message_at DESC LIMIT 5'
) : null;
$chart = admin_can('reports', $admin) ? report_by_day(date('Y-m-d', strtotime('-13 days')), $today) : null;

admin_view('dashboard', compact('stats', 'todayOrders', 'toSchedule', 'toVerify', 'recent', 'requests', 'threads', 'chart', 'canOrders') + ['title' => 'Dashboard']);
