<?php
declare(strict_types=1);

// Conversations between the shop and its customers. Every order, custom cake request and
// contact form message can have one. Customers open it with the private link in their emails
// (or from their account) and reply on the website; staff answer from Admin > Messages.

function thread_url(array $thread): string
{
    return url('messages/' . $thread['token']);
}

function get_thread_by_token(string $token): ?array
{
    return preg_match('/^[a-f0-9]{32}$/', $token) ? db_one('SELECT * FROM threads WHERE token = ?', [$token]) : null;
}

function get_thread(int $id): ?array
{
    return db_one('SELECT * FROM threads WHERE id = ?', [$id]);
}

function get_thread_messages(int $threadId): array
{
    return db_all(
        'SELECT m.*, a.name AS admin_name FROM thread_messages m LEFT JOIN admins a ON a.id = m.admin_id
         WHERE m.thread_id = ? ORDER BY m.created_at, m.id',
        [$threadId]
    );
}

function create_thread(string $subject, string $name, string $email, ?string $phone = null, array $links = []): array
{
    db_run(
        'INSERT INTO threads (token, subject, customer_name, customer_email, customer_phone, user_id, order_id, custom_request_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [bin2hex(random_bytes(16)), str_limit($subject, 160), $name, $email, $phone ?: null,
         $links['user_id'] ?? null, $links['order_id'] ?? null, $links['custom_request_id'] ?? null]
    );
    return get_thread((int) db()->lastInsertId());
}

/** The conversation for an order, created the first time it is needed. */
function order_thread(array $order): array
{
    return db_one('SELECT * FROM threads WHERE order_id = ?', [$order['id']])
        ?? create_thread('Order ' . $order['order_number'], $order['customer_name'], $order['customer_email'], $order['customer_phone'],
            ['order_id' => $order['id'], 'user_id' => $order['user_id']]);
}

/** The conversation for a custom cake request, created the first time it is needed. */
function custom_request_thread(array $request): array
{
    return db_one('SELECT * FROM threads WHERE custom_request_id = ?', [$request['id']])
        ?? create_thread($request['occasion'] . ' cake request', $request['name'], $request['email'], $request['phone'],
            ['custom_request_id' => $request['id'], 'user_id' => find_verified_user_by_email($request['email'])['id'] ?? null]);
}

/** Adds a message and marks the conversation unread for the other side. */
function add_thread_message(array $thread, string $sender, string $body, ?int $adminId = null): void
{
    db_run('INSERT INTO thread_messages (thread_id, sender, admin_id, body) VALUES (?, ?, ?, ?)', [$thread['id'], $sender, $adminId, $body]);
    db_run(
        $sender === 'customer'
            ? "UPDATE threads SET unread_admin = 1, status = 'open', last_message_at = NOW() WHERE id = ?"
            : 'UPDATE threads SET unread_customer = 1, unread_admin = 0, last_message_at = NOW() WHERE id = ?',
        [$thread['id']]
    );
}

/** How long after its last check a chat page still counts as open. */
const CHAT_LIVE_SECONDS = 40;

/** True while the other side has this conversation open (their page checked in a moment ago). */
function thread_seen_live(array $thread, string $who): bool
{
    $seen = $thread[$who . '_seen_at'] ?? null;
    return $seen && strtotime((string) $seen) >= time() - CHAT_LIVE_SECONDS;
}

/** Records that the shop or the customer has the conversation open right now. */
function mark_thread_seen(array $thread, string $who): void
{
    db_run($who === 'admin'
        ? 'UPDATE threads SET admin_seen_at = NOW(), unread_admin = 0 WHERE id = ?'
        : 'UPDATE threads SET customer_seen_at = NOW(), unread_customer = 0 WHERE id = ?', [$thread['id']]);
}

/** Messages newer than the given id. */
function thread_messages_after(int $threadId, int $afterId): array
{
    return db_all(
        'SELECT m.*, a.name AS admin_name FROM thread_messages m LEFT JOIN admins a ON a.id = m.admin_id
         WHERE m.thread_id = ? AND m.id > ? ORDER BY m.id',
        [$threadId, $afterId]
    );
}

/** One chat bubble, as the customer ('customer') or the shop ('admin') sees it. */
function chat_bubble_html(array $m, array $thread, string $view): string
{
    $prefix = $view === 'admin' ? 'chat-admin' : 'chat';
    $who = $view === 'admin'
        ? ($m['sender'] === 'shop' ? ($m['admin_name'] ?: 'Shop') : $thread['customer_name'])
        : ($m['sender'] === 'shop' ? setting('store_short_name') : 'You');
    return '<div class="' . $prefix . '__msg ' . $prefix . '__msg--' . e($m['sender']) . '" data-msg-id="' . (int) $m['id'] . '">'
        . '<div class="' . $prefix . '__bubble">' . nl2br(e($m['body'])) . '</div>'
        . '<p class="' . $prefix . '__meta">' . e($who) . ' &middot; ' . e(date('j M Y, g:ia', strtotime((string) $m['created_at']))) . '</p></div>';
}

/** A customer wrote: save it and let the shop know by email (unless staff are reading it live). */
function customer_message(array $thread, string $body): void
{
    add_thread_message($thread, 'customer', $body);
    $notify = setting('order_notification_email');
    if ($notify !== '' && !thread_seen_live($thread, 'admin')) {
        send_mail(
            $notify,
            'New message from ' . $thread['customer_name'] . ': ' . $thread['subject'],
            render_email('message-admin', ['thread' => $thread, 'body' => $body, 'title' => 'New message from ' . $thread['customer_name'], 'preheader' => mb_substr($body, 0, 90)]),
            null,
            $thread['customer_email']
        );
    }
}

/** The shop replied: save it and email the customer, with a button to answer on the website. */
function shop_message(array $thread, string $body, int $adminId): bool
{
    add_thread_message($thread, 'shop', $body, $adminId);
    if (thread_seen_live($thread, 'customer')) {
        return true; // The customer has the chat open and sees the reply straight away.
    }
    return send_mail(
        $thread['customer_email'],
        'Re: ' . $thread['subject'] . ' - ' . setting('store_short_name'),
        render_email('message-customer', ['thread' => $thread, 'body' => $body, 'title' => 'A message from ' . setting('store_short_name'), 'preheader' => mb_substr($body, 0, 90), 'reply_url' => thread_url($thread)]),
        null,
        setting('store_email')
    );
}

/** Threads with unread customer messages, for the admin badge. */
function unread_thread_count(): int
{
    try {
        return (int) db_value('SELECT COUNT(*) FROM threads WHERE unread_admin = 1');
    } catch (PDOException) {
        return 0;
    }
}
