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
        'INSERT INTO threads (branch_id, token, subject, customer_name, customer_email, customer_phone, user_id, order_id, custom_request_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [(int) ($links['branch_id'] ?? 0) ?: main_branch_id(), bin2hex(random_bytes(16)), str_limit($subject, 160), $name, $email, $phone ?: null,
         $links['user_id'] ?? null, $links['order_id'] ?? null, $links['custom_request_id'] ?? null]
    );
    return get_thread((int) db()->lastInsertId());
}

/** The conversation for an order, created the first time it is needed. */
function order_thread(array $order): array
{
    return db_one('SELECT * FROM threads WHERE order_id = ?', [$order['id']])
        ?? create_thread('Order ' . $order['order_number'], $order['customer_name'], $order['customer_email'], $order['customer_phone'],
            ['order_id' => $order['id'], 'user_id' => $order['user_id'], 'branch_id' => $order['branch_id'] ?? null]);
}

/** The conversation for a custom cake request, created the first time it is needed. */
function custom_request_thread(array $request): array
{
    return db_one('SELECT * FROM threads WHERE custom_request_id = ?', [$request['id']])
        ?? create_thread($request['occasion'] . ' cake request', $request['name'], $request['email'], $request['phone'],
            ['custom_request_id' => $request['id'], 'user_id' => find_verified_user_by_email($request['email'])['id'] ?? null, 'branch_id' => $request['branch_id'] ?? null]);
}

/** Adds a message (with a photo or PDF from chat_attachment_upload(), if any) and marks the conversation unread for the other side. */
function add_thread_message(array $thread, string $sender, string $body, ?int $adminId = null, ?array $file = null): void
{
    db_run('INSERT INTO thread_messages (thread_id, sender, admin_id, body, attachment, attachment_name) VALUES (?, ?, ?, ?, ?, ?)',
        [$thread['id'], $sender, $adminId, $body, $file['path'] ?? null, $file['name'] ?? null]);
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

// ---------------------------------------------------------------------------
// Photos and PDFs in a chat. They are private (storage/uploads/chat): staff open them through
// the admin panel, the customer through their conversation's private link.
// ---------------------------------------------------------------------------

/** The photo or PDF sent with a chat message (form field "attachment"): [file or null, error or null]. */
function chat_attachment_upload(): array
{
    if (!has_upload('attachment')) {
        return [null, null];
    }
    [$path, $error] = store_private_upload('attachment', 'chat', UPLOAD_RECEIPT_TYPES);
    if ($error) {
        return [null, $error];
    }
    $name = str_limit(trim((string) preg_replace('/[^\w .()-]+/u', '', basename((string) $_FILES['attachment']['name']))), 120);
    return [['path' => $path, 'name' => $name !== '' ? $name : basename($path)], null];
}

/** True when a chat file is a photo the page can show (others open as a file). */
function chat_file_is_image(string $path): bool
{
    return (bool) preg_match('/\.(jpe?g|png|webp)$/i', $path);
}

/** Where the shop ('admin') or the customer opens a message's file. */
function chat_file_url(array $m, array $thread, string $view): string
{
    return $view === 'admin' ? admin_url('file', ['path' => $m['attachment']]) : thread_url($thread) . '/file/' . (int) $m['id'];
}

/** Short text for a message in lists and alerts: its words, or the file it carries. */
function message_preview(array $m): string
{
    return trim((string) $m['body']) !== '' ? (string) $m['body'] : (!empty($m['attachment']) ? 'Sent a file: ' . ($m['attachment_name'] ?: 'attachment') : '');
}

/** SQL for the same preview in a query on thread_messages m. */
const MESSAGE_PREVIEW_SQL = "IF(m.body = '' AND m.attachment IS NOT NULL, CONCAT('Sent a file: ', IFNULL(m.attachment_name, 'attachment')), m.body)";

/**
 * One chat bubble, as the customer ('customer') or the shop ('admin') sees it. $topic (the order or
 * request it is about) is shown under the bubble in the shop's chat with one customer.
 */
function chat_bubble_html(array $m, array $thread, string $view, string $topic = ''): string
{
    $prefix = $view === 'admin' ? 'chat-admin' : 'chat';
    $who = $view === 'admin'
        ? ($m['sender'] === 'shop' ? ($m['admin_name'] ?: 'Shop') : $thread['customer_name'])
        : ($m['sender'] === 'shop' ? setting('store_short_name') : 'You');
    $file = '';
    if (!empty($m['attachment'])) {
        $url = e(chat_file_url($m, $thread, $view));
        $name = e($m['attachment_name'] ?: 'Attachment');
        $file = chat_file_is_image((string) $m['attachment'])
            ? '<a class="chat-file chat-file--photo" href="' . $url . '" target="_blank" rel="noopener"' . ($view === 'admin' ? ' data-lightbox="' . $name . '"' : '') . '><img src="' . $url . '" alt="' . $name . '" loading="lazy"></a>'
            : '<a class="chat-file" href="' . $url . '" target="_blank" rel="noopener">' . icon('file', '', 22) . '<span>' . $name . '</span></a>';
    }
    $text = trim((string) $m['body']) !== '' ? nl2br(e($m['body'])) : '';
    return '<div class="' . $prefix . '__msg ' . $prefix . '__msg--' . e($m['sender']) . '" data-msg-id="' . (int) $m['id'] . '" data-thread="' . (int) $m['thread_id'] . '">'
        . '<div class="' . $prefix . '__bubble' . ($file !== '' ? ' has-file' : '') . '">' . $file . ($text !== '' ? '<span class="chat-text">' . $text . '</span>' : '') . '</div>'
        . '<p class="' . $prefix . '__meta">' . e($who) . ($topic !== '' ? ' &middot; <span class="chat-tag">' . e($topic) . '</span>' : '') . ' &middot; ' . e(date('j M Y, g:ia', strtotime((string) $m['created_at']))) . '</p></div>';
}

/** A customer wrote: save it and let the shop know by email (unless staff are reading it live). */
function customer_message(array $thread, string $body, ?array $file = null): void
{
    add_thread_message($thread, 'customer', $body, null, $file);
    $notify = shop_alert_address((int) ($thread['branch_id'] ?? 0), (string) $thread['customer_email']);
    if ($notify !== '' && !thread_seen_live($thread, 'admin')) {
        $conversation = thread_conversation($thread);
        send_branch_mail(
            (int) ($thread['branch_id'] ?? 0),
            $notify,
            alert_subject($thread['customer_name'], $conversation[1]),
            render_email('message-admin', ['thread' => $thread, 'body' => $body, 'file' => $file, 'title' => 'New message from ' . $thread['customer_name'], 'preheader' => mb_substr(message_preview(['body' => $body, 'attachment' => $file['path'] ?? null, 'attachment_name' => $file['name'] ?? null]), 0, 90)]),
            $thread['customer_email'],
            conversation_reference($conversation, 'shop')
        );
    }
}

/** The shop replied: save it and email the customer, with a button to answer on the website. */
function shop_message(array $thread, string $body, int $adminId, ?array $file = null): bool
{
    add_thread_message($thread, 'shop', $body, $adminId, $file);
    if (thread_seen_live($thread, 'customer')) {
        return true; // The customer has the chat open and sees the reply straight away.
    }
    $conversation = thread_conversation($thread);
    return with_branch((int) ($thread['branch_id'] ?? 0) ?: null, static fn () => send_mail(
        $thread['customer_email'],
        customer_subject($conversation),
        render_email('message-customer', ['thread' => $thread, 'body' => $body, 'file' => $file, 'title' => 'A message from ' . setting('store_short_name'), 'preheader' => mb_substr(message_preview(['body' => $body, 'attachment' => $file['path'] ?? null, 'attachment_name' => $file['name'] ?? null]), 0, 90), 'reply_url' => thread_url($thread)]),
        null,
        setting('store_email'),
        conversation_reference($conversation, 'customer')
    ));
}

/** Customers with unread messages, for the admin badge (one per person, like the Messages list). */
function unread_thread_count(): int
{
    try {
        return (int) db_value('SELECT COUNT(DISTINCT LOWER(customer_email)) FROM threads WHERE unread_admin = 1');
    } catch (PDOException) {
        return 0;
    }
}

// ---------------------------------------------------------------------------
// One chat per customer in the panel. A customer can have several conversations (the contact
// form, each order, each custom cake request, their emails); staff see them together, by email
// address, as one history, and choose which order or request a reply is about.
// ---------------------------------------------------------------------------

/** Every conversation of one customer email, newest first. $branchSql limits it to the shops staff may see. */
function person_threads(string $email, string $branchSql = ''): array
{
    return db_all('SELECT * FROM threads WHERE LOWER(customer_email) = ?' . $branchSql . ' ORDER BY last_message_at IS NULL, last_message_at DESC, id DESC', [mb_strtolower(trim($email))]);
}

/** The messages of those conversations, oldest first (only those after $afterId). */
function person_messages(array $threads, int $afterId = 0): array
{
    $ids = array_map(static fn ($t) => (int) $t['id'], $threads);
    if (!$ids) {
        return [];
    }
    return db_all(
        'SELECT m.*, a.name AS admin_name FROM thread_messages m LEFT JOIN admins a ON a.id = m.admin_id
         WHERE m.thread_id IN (' . implode(',', $ids) . ') AND m.id > ? ORDER BY m.id',
        [$afterId]
    );
}

/** What a conversation is about, for the chat and the reply picker: "Order CC-1001", "Birthday cake request". */
function thread_topic(array $thread): string
{
    return thread_conversation($thread)[1];
}

/**
 * Bubbles for the shop's chat with one customer. A line with the topic starts each run of
 * messages about the same order or request ($previousThread: the conversation of the message
 * already shown above them).
 */
function person_chat_html(array $messages, array $threadsById, int $previousThread = 0): string
{
    $html = '';
    foreach ($messages as $m) {
        $thread = $threadsById[(int) $m['thread_id']] ?? null;
        if (!$thread) {
            continue;
        }
        $topic = thread_topic($thread);
        if ((int) $m['thread_id'] !== $previousThread) {
            $html .= '<p class="chat-topic" data-topic-for="' . (int) $m['id'] . '"><span>' . e($topic) . '</span></p>';
            $previousThread = (int) $m['thread_id'];
        }
        $html .= chat_bubble_html($m, $thread, 'admin', $topic);
    }
    return $html;
}

// ---------------------------------------------------------------------------
// Quick replies: ready answers staff send in a tap from the chat. {name} is the customer's
// first name, {about} what the reply is about ("Order CC-1001"), {shop} and {phone} the shop's.
// Staff change them from the chat (saved in the setting chat_quick_replies).
// ---------------------------------------------------------------------------

const CHAT_QUICK_REPLIES_DEFAULT = [
    ['Thanks, on it', 'Hi {name}, thank you for your message. We will get back to you shortly.'],
    ['Order is ready', 'Hi {name}, good news: {about} is ready. See you soon!'],
    ['Payment received', 'Hi {name}, thank you, we have received your payment for {about}.'],
    ['Need more details', 'Hi {name}, could you send us a photo and a few more details (size, colours and the writing on the cake) so we can give you an exact price?'],
    ['Change is fine', 'Hi {name}, no problem, we have made that change to {about}.'],
    ['Please call us', 'Hi {name}, it may be quicker to talk. Please call us on {phone} and we will sort it out.'],
];
const CHAT_QUICK_REPLIES_MAX = 20;

/** The quick replies as [[title, text], ...]. */
function chat_quick_replies(): array
{
    $saved = json_decode((string) setting('chat_quick_replies'), true);
    return is_array($saved) ? $saved : CHAT_QUICK_REPLIES_DEFAULT;
}

/** Saves the quick replies from the edit form (titles[] and texts[]); empty rows are left out. */
function chat_quick_replies_save(array $titles, array $texts): int
{
    $list = [];
    foreach ($texts as $i => $text) {
        $text = trim(str_limit((string) $text, 1000));
        $title = trim(str_limit((string) ($titles[$i] ?? ''), 40));
        if ($text !== '') {
            $list[] = [$title !== '' ? $title : mb_strimwidth($text, 0, 30, '...'), $text];
        }
    }
    $list = array_slice($list, 0, CHAT_QUICK_REPLIES_MAX);
    setting_save('chat_quick_replies', json_encode($list, JSON_UNESCAPED_UNICODE));
    return count($list);
}
