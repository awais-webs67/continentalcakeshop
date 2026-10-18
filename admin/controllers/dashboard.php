<?php
$admin = require_admin();
$today = date('Y-m-d');

$stats = [
    'pickups_today' => (int) db_value("SELECT COUNT(*) FROM orders WHERE pickup_date = ? AND order_status <> 'cancelled'", [$today]),
    'to_verify'     => (int) db_value("SELECT COUNT(*) FROM orders WHERE payment_status = 'awaiting_verification'"),
    'new_orders'    => (int) db_value("SELECT COUNT(*) FROM orders WHERE order_status = 'new'"),
    'month_sales'   => (float) db_value("SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'paid' AND created_at >= ?", [date('Y-m-01')]),
    'month_orders'  => (int) db_value("SELECT COUNT(*) FROM orders WHERE order_status <> 'cancelled' AND created_at >= ?", [date('Y-m-01')]),
];

$todayPickups = db_all(
    "SELECT o.*, (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) AS item_count
     FROM orders o WHERE o.pickup_date = ? AND o.order_status <> 'cancelled' ORDER BY o.pickup_time",
    [$today]
);
$toVerify = db_all("SELECT * FROM orders WHERE payment_status = 'awaiting_verification' ORDER BY created_at LIMIT 6");
$recent = db_all('SELECT * FROM orders ORDER BY created_at DESC, id DESC LIMIT 8');
$requests = db_all("SELECT * FROM custom_requests WHERE status = 'new' ORDER BY event_date LIMIT 5");
$unread = (int) db_value('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0');

admin_view('dashboard', compact('stats', 'todayPickups', 'toVerify', 'recent', 'requests', 'unread') + ['title' => 'Dashboard']);
