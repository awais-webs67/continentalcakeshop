<?php
// One customer: an account (?id=) or a guest (?guest=email, or ?guest=tel:digits for orders without an email).
require_admin();

$guestKey = trim((string) ($_GET['guest'] ?? ''));
if ($guestKey !== '') {
    $phoneSql = "REPLACE(REPLACE(REPLACE(REPLACE(customer_phone, ' ', ''), '-', ''), '(', ''), ')', '')";
    if (str_starts_with($guestKey, 'tel:')) {
        $orders = db_all("SELECT * FROM orders WHERE user_id IS NULL AND customer_email = '' AND {$phoneSql} = ? ORDER BY created_at DESC", [substr($guestKey, 4)]);
    } else {
        $orders = db_all('SELECT * FROM orders WHERE user_id IS NULL AND LOWER(customer_email) = ? ORDER BY created_at DESC', [strtolower($guestKey)]);
    }
    if (!$orders) {
        flash('error', 'That customer was not found.');
        redirect(admin_url('customers', ['type' => 'guests']));
    }
    $latest = $orders[0];
    $customer = [
        'id'            => null,
        'guest'         => true,
        'name'          => $latest['customer_name'],
        'email'         => (string) $latest['customer_email'],
        'phone'         => $latest['customer_phone'],
        'created_at'    => end($orders)['created_at'],
        'last_login_at' => null,
    ];
    $requests = $customer['email'] !== '' ? db_all('SELECT * FROM custom_requests WHERE email = ? ORDER BY created_at DESC', [$customer['email']]) : [];
} else {
    $id = query_int('id');
    $customer = $id ? db_one('SELECT id, name, email, phone, created_at, last_login_at FROM users WHERE id = ?', [$id]) : null;
    if (!$customer) {
        flash('error', 'That customer was not found.');
        redirect(admin_url('customers'));
    }
    $customer['guest'] = false;
    // Guest orders placed with the same email are shown too, since it is usually the same person.
    $orders = db_all('SELECT * FROM orders WHERE user_id = ? OR (user_id IS NULL AND customer_email = ?) ORDER BY created_at DESC', [$customer['id'], $customer['email']]);
    $requests = db_all('SELECT * FROM custom_requests WHERE email = ? ORDER BY created_at DESC', [$customer['email']]);
}
$paid = array_sum(array_map(static fn ($o) => order_net_paid($o), $orders));
admin_view('customer-view', compact('customer', 'orders', 'paid', 'requests') + ['title' => $customer['name']]);
