<?php
// Inbox: one row per customer (by email address), with every conversation they have with the
// shop together (contact form, orders, custom cake requests, their emails). Newest first.
$admin = require_admin();

// While the panel is open, every admin page asks every 20 seconds (admin.js): a shop's inbox is
// read when it was last tried over 20 seconds ago (10 minutes after an error), so customers' emails
// arrive within about half a minute instead of waiting for the 15 minute job. One check at a time.
if (input('sync') === '1') {
    session_write_close();
    json_out(['checked' => inbox_sync(static fn (int $id): bool => admin_sees_branch($id))]);
}

// The owner's on/off switch for customers' emails in Messages, per shop (same as Branches > shop > Email).
if (is_post() && input('action') === 'inbox_switch' && ($admin['role'] ?? '') === 'owner' && ($branch = get_branch(post_int('branch_id')))) {
    $on = input('on') === '1';
    if ($on && !inbox_account($branch)) {
        flash('error', 'Set up ' . $branch['name'] . '\'s email account first (Branches > ' . $branch['name'] . ' > Email).');
    } else {
        inbox_switch($branch, $on);
        if ($on) {
            inbox_check($branch); // connects and starts from now
        }
        $error = (string) branch_setting((int) $branch['id'], 'inbox_error');
        flash($on && $error !== '' ? 'error' : 'success', $on
            ? ($error !== '' ? 'Switched on, but the inbox could not be read yet: ' . $error : 'Emails to ' . $branch['name'] . ' now come into Messages (new emails from now on).')
            : 'Emails to ' . $branch['name'] . ' no longer come into Messages. They are still in your email app.');
    }
    redirect(admin_url('messages'));
}

// Delete one customer's whole chat from the list (their orders and requests stay).
if (is_post() && input('action') === 'delete_chat' && valid_email(input('email'))) {
    $count = delete_person_chat(mb_strtolower(input('email')), branch_sql('branch_id'));
    flash('success', $count ? 'Chat deleted (' . $count . ' ' . ($count === 1 ? 'message' : 'messages') . '). Orders and custom cake requests are still there.' : 'That chat was already gone.');
    redirect(admin_url('messages', array_filter(['filter' => input('filter'), 'q' => input('q')])));
}

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
// The owner sees an on/off switch for each shop whose email account can be read.
$inboxShops = ($admin['role'] ?? '') === 'owner' ? array_values(array_filter(branches(), static fn ($b) => inbox_account($b) !== null)) : [];
admin_view('messages', compact('result', 'filter', 'q', 'unread', 'inboxOn', 'inboxShops') + ['title' => 'Messages']);
