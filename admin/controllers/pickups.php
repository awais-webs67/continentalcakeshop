<?php
// Pickups & deliveries: today's list first, then advance orders (any later day) and past days.
require_admin();
$today = date('Y-m-d');
$tab = in_array($_GET['tab'] ?? '', ['today', 'advance', 'past'], true) ? $_GET['tab'] : 'today';
$type = in_array($_GET['type'] ?? '', ['pickup', 'delivery'], true) ? $_GET['type'] : '';
$on = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['on'] ?? '')) ? $_GET['on'] : '';
$openOnly = ($_GET['open'] ?? '') === '1';
// Older links used ?date=: show that day in the right tab.
if (!isset($_GET['tab']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['date'] ?? ''))) {
    $tab = $_GET['date'] === $today ? 'today' : ($_GET['date'] > $today ? 'advance' : 'past');
    $on = $tab === 'advance' ? $_GET['date'] : '';
}

$typeSql = match ($type) {
    'delivery' => " AND o.fulfilment = 'delivery'",
    'pickup'   => " AND o.fulfilment <> 'delivery'",
    default    => '',
};
$select = 'SELECT o.*, ' . ORDER_SUMMARY_SQL . " AS summary FROM orders o WHERE o.order_status <> 'cancelled'";

$counts = [
    'today'   => (int) db_value("SELECT COUNT(*) FROM orders o WHERE o.order_status <> 'cancelled' AND o.pickup_date = ?", [$today]),
    'advance' => (int) db_value("SELECT COUNT(*) FROM orders o WHERE o.order_status <> 'cancelled' AND o.pickup_date > ?", [$today]),
    'past'    => (int) db_value("SELECT COUNT(*) FROM orders o WHERE o.order_status NOT IN ('cancelled', 'completed') AND o.pickup_date < ?", [$today]),
];

$byDay = [];
$stats = null;
if ($tab === 'today') {
    $orders = db_all($select . ' AND o.pickup_date = ?' . $typeSql . ' ORDER BY o.pickup_time IS NOT NULL, o.pickup_time, o.id', [$today]);
    $byDay[$today] = $orders;
    $all = db_all("SELECT * FROM orders o WHERE o.order_status <> 'cancelled' AND o.pickup_date = ?", [$today]);
    $stats = [
        'total'     => count($all),
        'pickup'    => count(array_filter($all, static fn ($o) => !is_delivery($o))),
        'delivery'  => count(array_filter($all, static fn ($o) => is_delivery($o))),
        'done'      => count(array_filter($all, static fn ($o) => $o['order_status'] === 'completed')),
        'to_pay'    => count(array_filter($all, static fn ($o) => order_balance($o) > 0.004)),
    ];
} elseif ($tab === 'advance') {
    $sql = $select . ($on !== '' ? ' AND o.pickup_date = ?' : ' AND o.pickup_date > ?') . $typeSql . ' ORDER BY o.pickup_date, o.pickup_time IS NOT NULL, o.pickup_time, o.id LIMIT 400';
    foreach (db_all($sql, [$on !== '' ? $on : $today]) as $o) {
        $byDay[$o['pickup_date']][] = $o;
    }
} else {
    // The last 30 days, newest first; "Not done" shows only orders still waiting to be marked done.
    $from = date('Y-m-d', strtotime('-30 days'));
    $sql = $select . ' AND o.pickup_date < ? AND o.pickup_date >= ?' . ($openOnly ? " AND o.order_status <> 'completed'" : '') . $typeSql . ' ORDER BY o.pickup_date DESC, o.pickup_time, o.id LIMIT 400';
    foreach (db_all($sql, [$today, $from]) as $o) {
        $byDay[$o['pickup_date']][] = $o;
    }
}

admin_view('pickups', compact('tab', 'type', 'on', 'openOnly', 'counts', 'byDay', 'stats', 'today') + ['title' => 'Pickups & deliveries']);
