<?php
// Dashboard: how the shop is doing (sales, orders, top cakes) for the last 7, 30 or 90 days,
// and how many things wait for staff right now. The lists themselves live on their own pages.
$admin = require_admin();
$today = date('Y-m-d');
$canOrders = admin_can('orders', $admin);
$canReports = admin_can('reports', $admin);
$b = branch_sql('branch_id');     // one branch, or every branch

$days = in_array((int) ($_GET['days'] ?? 30), [7, 30, 90], true) ? (int) ($_GET['days'] ?? 30) : 30;
$from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
$prevTo = date('Y-m-d', strtotime($from . ' -1 day'));
$prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . ($days - 1) . ' days'));

// Waiting right now (counts only, each links to its list).
$badges = admin_badges();
$glance = [];
if ($canOrders) {
    $glance[] = ['Due today', (int) db_value("SELECT COUNT(*) FROM orders WHERE pickup_date = ? AND order_status <> 'cancelled'" . $b, [$today]),
        (int) db_value("SELECT COUNT(*) FROM orders WHERE pickup_date = ? AND order_status = 'completed'" . $b, [$today]) . ' done', admin_url('pickups'), false];
    $glance[] = ['To confirm', (int) db_value("SELECT COUNT(*) FROM orders WHERE order_status = 'new'" . $b), 'New orders', admin_url('orders', ['status' => 'new']), true];
    $glance[] = ['Payments to check', $badges['payments'], 'Screenshots', admin_url('orders', ['view' => 'all', 'payment' => 'awaiting_verification']), true];
    $glance[] = ['No delivery time', (int) db_value("SELECT COUNT(*) FROM orders WHERE fulfilment = 'delivery' AND pickup_time IS NULL AND order_status NOT IN ('cancelled', 'completed')" . $b), 'Deliveries', admin_url('orders', ['view' => 'action', 'fulfilment' => 'delivery']), true];
}
if (admin_can('messages', $admin)) {
    $glance[] = ['Messages', $badges['messages'], 'Unread', admin_url('messages', ['filter' => 'unread']), true];
}
if (admin_can('custom', $admin)) {
    $glance[] = ['Custom cakes', $badges['custom'], 'New requests', admin_url('custom-requests', ['status' => 'new']), true];
}

// Sales for the chosen days, compared with the days before.
$sales = $prev = $byDay = $top = $methods = $shops = $statuses = null;
if ($canReports) {
    $sales = report_summary($from, $today);
    $prev = report_summary($prevFrom, $prevTo);
    $byDay = report_by_day($from, $today);
    $top = report_top_products($from, $today, 6);
    $methods = report_by_method($from, $today);
    $statuses = array_column(db_all(
        'SELECT order_status, COUNT(*) AS n FROM orders WHERE created_at BETWEEN ? AND ?' . $b . ' GROUP BY order_status',
        [$from . ' 00:00:00', $today . ' 23:59:59']
    ), 'n', 'order_status');
    if (count(branches()) > 1 && admin_branch_id() === null) {
        $shops = db_all(
            "SELECT branch_id, COUNT(*) AS orders, SUM(total) AS total FROM orders
             WHERE order_status <> 'cancelled' AND created_at BETWEEN ? AND ? GROUP BY branch_id ORDER BY total DESC",
            [$from . ' 00:00:00', $today . ' 23:59:59']
        );
    }
}

admin_view('dashboard', compact('days', 'glance', 'sales', 'prev', 'byDay', 'top', 'methods', 'shops', 'statuses', 'canOrders', 'canReports') + ['title' => 'Dashboard']);
