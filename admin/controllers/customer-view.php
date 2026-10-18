<?php
require_admin();
$id = query_int('id');
$customer = $id ? db_one('SELECT id, name, email, phone, created_at, last_login_at FROM users WHERE id = ?', [$id]) : null;
if (!$customer) {
    flash('error', 'That customer was not found.');
    redirect(admin_url('customers'));
}
// Guest orders placed with the same email are shown too, since it is usually the same person.
$orders = db_all('SELECT * FROM orders WHERE user_id = ? OR (user_id IS NULL AND customer_email = ?) ORDER BY created_at DESC', [$customer['id'], $customer['email']]);
$paid = array_sum(array_map(static fn ($o) => $o['payment_status'] === 'paid' ? (float) $o['total'] : 0, $orders));
$requests = db_all('SELECT * FROM custom_requests WHERE email = ? ORDER BY created_at DESC', [$customer['email']]);
admin_view('customer-view', compact('customer', 'orders', 'paid', 'requests') + ['title' => $customer['name']]);
