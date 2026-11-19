<?php
// The chat with one customer: every conversation they have with the shop (contact form, each
// order, each custom cake request, their emails) together, by email address, as one history.
// Staff choose which order or request a reply is about ("about" = thread:ID, order:ID,
// request:ID or new), and the reply goes to that conversation and its email conversation.
$admin = require_admin();

// Old links (an order, a request or one conversation) open the customer's chat at that topic.
$about = '';
if (query_int('order') && admin_can('orders')) {
    $order = get_order(query_int('order'));
    if (!$order) {
        redirect(admin_url('messages'));
    }
    require_branch_access($order, 'messages');
    if (!valid_email((string) $order['customer_email'])) {
        flash('error', 'Order ' . $order['order_number'] . ' has no email address, so there is no chat. Call ' . $order['customer_name'] . ($order['customer_phone'] ? ' on ' . $order['customer_phone'] : '') . ' instead.');
        redirect(admin_url('orders/view', ['id' => $order['id']]));
    }
    $email = (string) $order['customer_email'];
    $about = 'thread:' . order_thread($order)['id'];
} elseif (query_int('request') && admin_can('custom')) {
    $request = db_one('SELECT * FROM custom_requests WHERE id = ?', [query_int('request')]);
    if (!$request) {
        redirect(admin_url('messages'));
    }
    require_branch_access($request, 'messages');
    $email = (string) $request['email'];
    $about = 'thread:' . custom_request_thread($request)['id'];
} elseif (query_int('id') && !is_post()) {
    $thread = get_thread(query_int('id'));
    if (!$thread) {
        flash('error', 'That conversation was not found.');
        redirect(admin_url('messages'));
    }
    require_branch_access($thread, 'messages');
    redirect(admin_url('messages/view', ['email' => mb_strtolower($thread['customer_email']), 'about' => 'thread:' . $thread['id']]));
} else {
    $email = mb_strtolower(trim(input('email')));
}
if (!empty($about)) {
    redirect(admin_url('messages/view', ['email' => mb_strtolower($email), 'about' => $about]));
}
if (!valid_email($email)) {
    flash('error', 'That customer was not found.');
    redirect(admin_url('messages'));
}

$branchSql = branch_sql('branch_id');
$threads = person_threads($email, $branchSql);
$threadsById = array_column($threads, null, 'id');
// What else this customer has, so a reply can be about an order or request that has no chat yet.
$orders = db_all('SELECT * FROM orders WHERE LOWER(customer_email) = ?' . $branchSql . ' ORDER BY created_at DESC LIMIT 30', [$email]);
$requests = db_all('SELECT * FROM custom_requests WHERE LOWER(email) = ?' . $branchSql . ' ORDER BY created_at DESC LIMIT 30', [$email]);
$user = db_one('SELECT id, name, phone FROM users WHERE LOWER(email) = ?', [$email]);
$latest = $threads[0] ?? null;
$person = [
    'email' => $email,
    'name'  => $latest['customer_name'] ?? $orders[0]['customer_name'] ?? $requests[0]['name'] ?? $user['name'] ?? $email,
    'phone' => $latest['customer_phone'] ?? $orders[0]['customer_phone'] ?? $requests[0]['phone'] ?? $user['phone'] ?? '',
];
if (!$threads && !$orders && !$requests && !$user) {
    flash('error', 'That customer was not found.');
    redirect(admin_url('messages'));
}
$self = admin_url('messages/view', ['email' => $email]);

/** The conversation a reply is about: an existing one, or the one for an order or request (made now), or a new one. */
$resolve = static function (string $about) use ($threadsById, $orders, $requests, $person, $latest): ?array {
    [$kind, $id] = array_pad(explode(':', $about, 2), 2, '0');
    $id = (int) $id;
    return match ($kind) {
        'thread'  => $threadsById[$id] ?? null,
        'order'   => ($o = array_values(array_filter($orders, static fn ($o) => (int) $o['id'] === $id))[0] ?? null) ? order_thread($o) : null,
        'request' => ($r = array_values(array_filter($requests, static fn ($r) => (int) $r['id'] === $id))[0] ?? null) ? custom_request_thread($r) : null,
        'new'     => create_thread('Website message', $person['name'], $person['email'], $person['phone'] ?: null, [
            'branch_id' => (int) ($latest['branch_id'] ?? $orders[0]['branch_id'] ?? $requests[0]['branch_id'] ?? 0) ?: (admin_branch_id() ?? main_branch_id()),
            'user_id'   => find_verified_user_by_email($person['email'])['id'] ?? null,
        ]),
        default   => null,
    };
};

// The open chat checks every few seconds for new messages in any of this customer's conversations.
if (!is_post() && input('poll') === '1') {
    session_write_close();
    foreach ($threads as $t) {
        mark_thread_seen($t, 'admin');
    }
    $new = person_messages($threads, max(0, query_int('after')));
    json_out([
        'messages' => $new ? [['id' => (int) end($new)['id'], 'html' => person_chat_html($new, $threadsById, query_int('thread'))]] : [],
    ]);
}

if (is_post()) {
    $action = input('action');
    if ($action === 'reply') {
        $thread = $resolve(input('about'));
        $body = trim(str_limit(input('body'), 5000));
        [$file, $error] = $thread ? chat_attachment_upload() : [null, 'Choose what the reply is about.'];
        if ($error || (!$file && mb_strlen($body) < 2)) {
            $error ??= 'Please write a reply.';
            is_fetch_request() ? json_out(['ok' => false, 'error' => $error]) : flash('error', $error);
            redirect($self . '#reply');
        }
        $live = thread_seen_live($thread, 'customer');
        $sent = shop_message($thread, $body, (int) $admin['id'], $file);
        if (input('close') === '1') {
            db_run("UPDATE threads SET status = 'closed' WHERE LOWER(customer_email) = ?" . $branchSql, [$email]);
        }
        $note = $live ? 'They have the chat open, so they see it straight away.'
            : ($sent ? 'Also emailed to ' . $thread['customer_email'] . ' (about ' . thread_topic($thread) . ').' : 'Saved, but the email could not be sent. Check the shop\'s email account in Branches > ' . branch_name((int) $thread['branch_id']) . ' > Email.');
        if (is_fetch_request() && input('close') !== '1') {
            json_out(['ok' => true, 'note' => $note, 'about' => 'thread:' . $thread['id'], 'topic' => thread_topic($thread)]);
        }
        flash($sent ? 'success' : 'error', $note);
        redirect(admin_url('messages/view', ['email' => $email, 'about' => 'thread:' . $thread['id']]) . '#reply');
    }
    if ($action === 'status') {
        $open = (bool) array_filter($threads, static fn ($t) => $t['status'] === 'open');
        db_run('UPDATE threads SET status = ?, unread_admin = 0 WHERE LOWER(customer_email) = ?' . $branchSql, [$open ? 'closed' : 'open', $email]);
        flash('success', $open ? 'Chat closed. It opens again when ' . $person['name'] . ' writes.' : 'Chat opened again.');
    }
    redirect($self);
}

foreach ($threads as $t) {
    mark_thread_seen($t, 'admin');
}
$messages = person_messages($threads);

// The reply picker: each conversation, then orders and requests without one yet, then a new message.
$choices = [];
foreach ($threads as $t) {
    $choices['thread:' . $t['id']] = thread_topic($t) . ($t['status'] === 'closed' ? ' (closed)' : '');
}
$withChat = array_flip(array_filter(array_column($threads, 'order_id')));
foreach ($orders as $o) {
    if (!isset($withChat[$o['id']])) {
        $choices['order:' . $o['id']] = 'Order ' . $o['order_number'] . ' · ' . date('j M', strtotime($o['pickup_date']));
    }
}
$requestWithChat = array_flip(array_filter(array_column($threads, 'custom_request_id')));
foreach ($requests as $r) {
    if (!isset($requestWithChat[$r['id']]) && !$r['order_id']) {
        $choices['request:' . $r['id']] = $r['occasion'] . ' cake request · ' . date('j M', strtotime($r['event_date']));
    }
}
if (!array_filter($threads, static fn ($t) => !$t['order_id'] && !$t['custom_request_id'])) {
    $choices['new'] = 'Something else (new message)';
}
// Answer about what they last wrote about, unless a link chose a topic.
$lastFromCustomer = array_values(array_filter(array_reverse($messages), static fn ($m) => $m['sender'] === 'customer'))[0] ?? null;
$selected = input('about');
if (!isset($choices[$selected])) {
    $selected = $lastFromCustomer ? 'thread:' . $lastFromCustomer['thread_id'] : (array_key_first($choices) ?? 'new');
}
$isOpen = (bool) array_filter($threads, static fn ($t) => $t['status'] === 'open');
$customerLink = $user ? admin_url('customers/view', ['id' => $user['id']]) : ($orders || $requests ? admin_url('customers/view', ['guest' => $email]) : null);
admin_view('message-view', compact('person', 'threads', 'threadsById', 'messages', 'orders', 'requests', 'choices', 'selected', 'isOpen', 'customerLink', 'self')
    + ['title' => $person['name']]);
