<?php
$admin = require_admin();

// Open a conversation, or start one from an order or custom cake request.
if (query_int('order') && admin_can('orders')) {
    $order = get_order(query_int('order'));
    $order ? redirect(admin_url('messages/view', ['id' => order_thread($order)['id']])) : redirect(admin_url('messages'));
}
if (query_int('request') && admin_can('custom')) {
    $request = db_one('SELECT * FROM custom_requests WHERE id = ?', [query_int('request')]);
    $request ? redirect(admin_url('messages/view', ['id' => custom_request_thread($request)['id']])) : redirect(admin_url('messages'));
}

$thread = get_thread(query_int('id') ?: post_int('id'));
if (!$thread) {
    flash('error', 'That conversation was not found.');
    redirect(admin_url('messages'));
}
$self = admin_url('messages/view', ['id' => $thread['id']]);

if (is_post()) {
    $action = input('action');
    if ($action === 'reply') {
        $body = trim(str_limit(input('body'), 5000));
        if (mb_strlen($body) < 2) {
            flash('error', 'Please write a reply.');
        } else {
            $sent = shop_message($thread, $body, (int) $admin['id']);
            if (input('close') === '1') {
                db_run("UPDATE threads SET status = 'closed' WHERE id = ?", [$thread['id']]);
            }
            flash($sent ? 'success' : 'error', $sent ? 'Reply sent to ' . $thread['customer_email'] . '.' : 'The reply was saved, but the email could not be sent. Check Settings > Emails.');
        }
    } elseif ($action === 'status') {
        db_run('UPDATE threads SET status = ?, unread_admin = 0 WHERE id = ?', [$thread['status'] === 'open' ? 'closed' : 'open', $thread['id']]);
        flash('success', $thread['status'] === 'open' ? 'Conversation closed. It opens again if the customer replies.' : 'Conversation opened again.');
    }
    redirect($self . '#reply');
}

db_run('UPDATE threads SET unread_admin = 0 WHERE id = ?', [$thread['id']]);
$messages = get_thread_messages((int) $thread['id']);
$order = $thread['order_id'] ? get_order((int) $thread['order_id']) : null;
$request = $thread['custom_request_id'] ? db_one('SELECT * FROM custom_requests WHERE id = ?', [$thread['custom_request_id']]) : null;
admin_view('message-view', compact('thread', 'messages', 'order', 'request') + ['title' => $thread['subject']]);
