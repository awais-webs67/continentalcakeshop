<?php
require_admin();

$q = trim((string) ($_GET['q'] ?? ''));
$where = '1 = 1';
$params = [];
if ($q !== '') {
    $where = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $params = array_fill(0, 3, '%' . $q . '%');
}
$result = paginate(
    "SELECT u.id, u.name, u.email, u.phone, u.created_at, u.last_login_at,
            COUNT(o.id) AS order_count, COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN o.total END), 0) AS spent,
            MAX(o.created_at) AS last_order_at
     FROM users u LEFT JOIN orders o ON o.user_id = u.id
     WHERE {$where} GROUP BY u.id ORDER BY u.created_at DESC",
    "SELECT COUNT(*) FROM users u WHERE {$where}",
    $params
);
$guestOrders = (int) db_value('SELECT COUNT(*) FROM orders WHERE user_id IS NULL');
admin_view('customers', compact('result', 'q', 'guestOrders') + ['title' => 'Customers']);
