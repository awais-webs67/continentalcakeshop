<?php
require_admin();

if (is_post()) {
    $id = post_int('id');
    $action = input('action');
    if ($action === 'read') {
        db_run('UPDATE contact_messages SET is_read = 1 - is_read WHERE id = ?', [$id]);
    } elseif ($action === 'replied') {
        db_run('UPDATE contact_messages SET is_read = 1, replied_at = NOW() WHERE id = ?', [$id]);
        flash('success', 'Marked as replied.');
    } elseif ($action === 'read_all') {
        db_run('UPDATE contact_messages SET is_read = 1 WHERE is_read = 0');
        flash('success', 'All messages marked as read.');
    } elseif ($action === 'delete') {
        db_run('DELETE FROM contact_messages WHERE id = ?', [$id]);
        flash('success', 'Message deleted.');
    }
    admin_back('messages');
}

$filter = (string) ($_GET['filter'] ?? '');
$where = match ($filter) {
    'unread'  => 'is_read = 0',
    'replied' => 'replied_at IS NOT NULL',
    default   => '1 = 1',
};
$result = paginate(
    "SELECT * FROM contact_messages WHERE {$where} ORDER BY created_at DESC, id DESC",
    "SELECT COUNT(*) FROM contact_messages WHERE {$where}"
);
$unread = admin_badges()['messages'];
admin_view('messages', compact('result', 'filter', 'unread') + ['title' => 'Messages']);
