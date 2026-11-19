<?php
// Inbox: one row per customer (by email address), with every conversation they have with the
// shop together (contact form, orders, custom cake requests, their emails). Newest first.
$admin = require_admin();

// "Check emails now": read the inbox of each shop that has it switched on (normally every 15 minutes).
if (is_post() && input('action') === 'check_inbox') {
    $lines = [];
    foreach (branches() as $branch) {
        if (inbox_enabled($branch) && admin_sees_branch((int) $branch['id'])) {
            $lines[] = (count(branches()) > 1 ? $branch['name'] . ': ' : '') . inbox_check($branch);
        }
    }
    flash('success', $lines ? 'Emails checked. ' . ucfirst(implode('; ', $lines)) . '.' : 'No shop inbox is switched on yet (Branches > shop > Email).');
    redirect(admin_url('messages'));
}

$filter = (string) ($_GET['filter'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$where = ['t.last_message_at IS NOT NULL' . branch_sql('t.branch_id')];
$params = [];
if ($q !== '') {
    $where[] = '(t.customer_name LIKE ? OR t.customer_email LIKE ? OR t.customer_phone LIKE ? OR t.subject LIKE ?)';
    array_push($params, ...array_fill(0, 4, '%' . $q . '%'));
}
$having = match ($filter) {
    'unread' => 'unread > 0',
    'closed' => 'open_topics = 0',
    'all'    => '1 = 1',
    default  => 'open_topics > 0',
};
$whereSql = implode(' AND ', $where);
$people = "SELECT LOWER(t.customer_email) AS email,
        SUBSTRING_INDEX(GROUP_CONCAT(t.customer_name ORDER BY t.last_message_at DESC SEPARATOR '\\n'), '\\n', 1) AS name,
        MAX(t.last_message_at) AS last_at, SUM(t.unread_admin) AS unread, COUNT(*) AS topics, SUM(t.status = 'open') AS open_topics,
        GROUP_CONCAT(t.id) AS thread_ids, MIN(t.branch_id) AS branch_id
    FROM threads t WHERE {$whereSql} GROUP BY LOWER(t.customer_email) HAVING {$having}";
$result = paginate("SELECT * FROM ({$people}) p ORDER BY p.unread > 0 DESC, p.last_at DESC", "SELECT COUNT(*) FROM ({$people}) p", $params);

// The newest message of each person and what it was about.
foreach ($result['rows'] as $i => $p) {
    $ids = implode(',', array_map('intval', explode(',', (string) $p['thread_ids'])));
    $last = db_one('SELECT m.sender, m.thread_id, ' . MESSAGE_PREVIEW_SQL . " AS preview FROM thread_messages m WHERE m.thread_id IN ({$ids}) ORDER BY m.id DESC LIMIT 1");
    $result['rows'][$i]['last'] = $last;
    $result['rows'][$i]['topic'] = $last && ($t = get_thread((int) $last['thread_id'])) ? thread_topic($t) : '';
}
$unread = (int) db_value('SELECT COUNT(DISTINCT LOWER(t.customer_email)) FROM threads t WHERE t.unread_admin = 1' . branch_sql('t.branch_id'));
$inboxOn = (bool) array_filter(branches(), static fn ($b) => inbox_enabled($b) && admin_sees_branch((int) $b['id']));
admin_view('messages', compact('result', 'filter', 'q', 'unread', 'inboxOn') + ['title' => 'Messages']);
