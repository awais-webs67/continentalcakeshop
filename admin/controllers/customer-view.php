<?php
// One customer: an account (?id=) or a guest (?guest=email, or ?guest=tel:digits for orders without an email).
require_admin();

$guestKey = trim((string) ($_GET['guest'] ?? ''));
if ($guestKey !== '') {
    $phoneSql = "REPLACE(REPLACE(REPLACE(REPLACE(customer_phone, ' ', ''), '-', ''), '(', ''), ')', '')";
    if (str_starts_with($guestKey, 'tel:')) {
        $orders = db_all("SELECT * FROM orders WHERE user_id IS NULL AND customer_email = '' AND {$phoneSql} = ? " . branch_sql('branch_id') . ' ORDER BY created_at DESC', [substr($guestKey, 4)]);
    } else {
        $orders = db_all('SELECT * FROM orders WHERE user_id IS NULL AND LOWER(customer_email) = ?' . branch_sql('branch_id') . ' ORDER BY created_at DESC', [strtolower($guestKey)]);
    }
    // Someone who only asked for a custom cake has requests and no orders.
    $requests = !str_starts_with($guestKey, 'tel:') ? db_all('SELECT * FROM custom_requests WHERE LOWER(email) = ?' . branch_sql('branch_id') . ' ORDER BY created_at DESC', [strtolower($guestKey)]) : [];
    if (!$orders && !$requests) {
        flash('error', 'That customer was not found.');
        redirect(admin_url('customers', ['type' => 'guests']));
    }
    $latest = $orders[0] ?? null;
    $customer = [
        'id'            => null,
        'guest'         => true,
        'name'          => $latest['customer_name'] ?? $requests[0]['name'],
        'email'         => (string) ($latest['customer_email'] ?? $requests[0]['email']),
        'phone'         => $latest['customer_phone'] ?? $requests[0]['phone'],
        'created_at'    => min(array_merge(array_column($orders, 'created_at'), array_column($requests, 'created_at'))),
        'last_login_at' => null,
    ];
} else {
    $id = query_int('id');
    $customer = $id ? db_one('SELECT id, name, email, phone, created_at, last_login_at FROM users WHERE id = ?', [$id]) : null;
    if (!$customer) {
        flash('error', 'That customer was not found.');
        redirect(admin_url('customers'));
    }
    $customer['guest'] = false;
    // Guest orders placed with the same email are shown too, since it is usually the same person.
    $orders = db_all('SELECT * FROM orders WHERE (user_id = ? OR (user_id IS NULL AND customer_email = ?))' . branch_sql('branch_id') . ' ORDER BY created_at DESC', [$customer['id'], $customer['email']]);
    $requests = db_all('SELECT * FROM custom_requests WHERE email = ?' . branch_sql('branch_id') . ' ORDER BY created_at DESC', [$customer['email']]);
}
$paid = array_sum(array_map(static fn ($o) => order_net_paid($o), $orders));
admin_view('customer-view', compact('customer', 'orders', 'paid', 'requests') + ['title' => $customer['name']]);
