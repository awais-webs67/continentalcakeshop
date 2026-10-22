<?php
// Customers: people with an account and guests who ordered without one. Guests are grouped by
// email (or by phone for phone and shop orders without an email). A guest whose email later
// gets an account is shown under that account.
require_admin();

$type = in_array($_GET['type'] ?? '', ['accounts', 'guests'], true) ? $_GET['type'] : 'all';
$q = trim((string) ($_GET['q'] ?? ''));

$phoneKey = "REPLACE(REPLACE(REPLACE(REPLACE(o.customer_phone, ' ', ''), '-', ''), '(', ''), ')', '')";
// Every text column is given the same collation so the two lists can be joined with UNION.
$c = static fn (string $expr): string => "CONVERT({$expr} USING utf8mb4) COLLATE utf8mb4_unicode_ci";
$accountsSql = "SELECT {$c("'account'")} AS kind, {$c('CAST(u.id AS CHAR)')} AS ckey, {$c('u.name')} AS name, {$c('u.email')} AS email, {$c("COALESCE(u.phone, '')")} AS phone,
        COUNT(o.id) AS order_count, COALESCE(SUM(o.amount_paid - o.amount_refunded), 0) AS spent,
        MAX(o.created_at) AS last_order_at, u.created_at AS since
    FROM users u LEFT JOIN orders o ON o.user_id = u.id OR (o.user_id IS NULL AND o.customer_email <> '' AND o.customer_email = u.email)
    GROUP BY u.id";
$guestKey = "CASE WHEN o.customer_email <> '' THEN LOWER(o.customer_email) ELSE CONCAT('tel:', {$phoneKey}) END";
$guestsSql = "SELECT {$c("'guest'")} AS kind, {$c($guestKey)} AS ckey,
        {$c("SUBSTRING_INDEX(GROUP_CONCAT(o.customer_name ORDER BY o.created_at DESC SEPARATOR '\\n'), '\\n', 1)")} AS name,
        {$c('MAX(o.customer_email)')} AS email,
        {$c("SUBSTRING_INDEX(GROUP_CONCAT(o.customer_phone ORDER BY o.created_at DESC SEPARATOR '\\n'), '\\n', 1)")} AS phone,
        COUNT(*) AS order_count, COALESCE(SUM(o.amount_paid - o.amount_refunded), 0) AS spent,
        MAX(o.created_at) AS last_order_at, MIN(o.created_at) AS since
    FROM orders o
    WHERE o.user_id IS NULL AND (o.customer_email = '' OR NOT EXISTS (SELECT 1 FROM users u WHERE u.email = o.customer_email))
    GROUP BY {$guestKey}";
$source = match ($type) {
    'accounts' => $accountsSql,
    'guests'   => $guestsSql,
    default    => "({$accountsSql}) UNION ALL ({$guestsSql})",
};
$where = '1 = 1';
$params = [];
if ($q !== '') {
    $where = '(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)';
    $params = array_fill(0, 3, '%' . $q . '%');
}
$result = paginate(
    "SELECT c.* FROM ({$source}) c WHERE {$where} ORDER BY COALESCE(c.last_order_at, c.since) DESC",
    "SELECT COUNT(*) FROM ({$source}) c WHERE {$where}",
    $params
);
$counts = [
    'accounts' => (int) db_value("SELECT COUNT(*) FROM ({$accountsSql}) c"),
    'guests'   => (int) db_value("SELECT COUNT(*) FROM ({$guestsSql}) c"),
];
$counts['all'] = $counts['accounts'] + $counts['guests'];
admin_view('customers', compact('result', 'q', 'type', 'counts') + ['title' => 'Customers']);
