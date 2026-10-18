<?php
require_admin();

// Inbox of conversations with customers (contact form, orders and custom cake requests).
$filter = (string) ($_GET['filter'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$where = ['t.last_message_at IS NOT NULL'];
$params = [];
match ($filter) {
    'unread' => $where[] = 't.unread_admin = 1',
    'closed' => $where[] = "t.status = 'closed'",
    'all'    => null,
    default  => $where[] = "t.status = 'open'",
};
if ($q !== '') {
    $where[] = '(t.customer_name LIKE ? OR t.customer_email LIKE ? OR t.subject LIKE ?)';
    array_push($params, ...array_fill(0, 3, '%' . $q . '%'));
}
$whereSql = implode(' AND ', $where);
$result = paginate(
    "SELECT t.*, (SELECT body FROM thread_messages m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) AS last_body,
            (SELECT sender FROM thread_messages m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) AS last_sender,
            (SELECT COUNT(*) FROM thread_messages m WHERE m.thread_id = t.id) AS message_count
     FROM threads t WHERE {$whereSql} ORDER BY t.unread_admin DESC, t.last_message_at DESC",
    "SELECT COUNT(*) FROM threads t WHERE {$whereSql}",
    $params
);
$unread = unread_thread_count();
admin_view('messages', compact('result', 'filter', 'q', 'unread') + ['title' => 'Messages']);
