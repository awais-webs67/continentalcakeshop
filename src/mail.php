<?php
declare(strict_types=1);

// Branded HTML emails (with a plain-text part) sent through one of three drivers:
//   'mail' - PHP mail(), works on most cPanel hosts out of the box
//   'smtp' - any SMTP server (cPanel email account, Gmail, Microsoft 365...)
//   'log'  - writes each email to storage/logs/mail so you can read it during local testing
// The main shop's account comes from Admin > Branches > main shop > Email once it is saved there,
// otherwise from the 'mail' section of config/config.php. Another shop with its own account
// sends through it (branch_setting_override() while that shop is in context).

const MAIL_SETTING_KEYS = [
    'driver'          => 'mail_driver',
    'from_address'    => 'mail_from_address',
    'from_name'       => 'mail_from_name',
    'smtp.host'       => 'smtp_host',
    'smtp.port'       => 'smtp_port',
    'smtp.encryption' => 'smtp_encryption',
    'smtp.username'   => 'smtp_username',
];

/** True when the admin panel holds the email settings (they then replace config.php). */
function mail_settings_in_admin(): bool
{
    return setting('mail_driver') !== '';
}

function mail_config(string $key, mixed $default = null): mixed
{
    if (mail_settings_in_admin()) {
        if ($key === 'smtp.password') {
            return secret_decrypt(setting('smtp_password_encrypted'));
        }
        $value = setting(MAIL_SETTING_KEYS[$key] ?? '');
        return $value !== '' ? $value : $default;
    }
    return config('mail.' . $key, $default);
}

function mail_from(): array
{
    // One sending address for every shop (best for not landing in spam). With two shops the
    // sender name says which shop wrote, e.g. "The Continental Cake Shop Wright".
    $name = (string) mail_config('from_name', setting('store_short_name'));
    $branch = $GLOBALS['branch_context'] ?? null;
    if ($branch && multi_branch() && stripos($name, (string) $branch['name']) === false) {
        $name .= ' ' . $branch['name'];
    }
    return [
        (string) mail_config('from_address', config('mail_from', setting_raw('store_email'))),
        $name,
    ];
}

/**
 * Sends one email. $html is the full HTML document; $text is generated from it when omitted.
 * $reference (from conversation_reference()) puts the email in its conversation in the mail app.
 */
function send_mail(string $to, string $subject, string $html, ?string $text = null, ?string $replyTo = null, ?string $reference = null): bool
{
    if (!valid_email($to)) {
        return false;
    }
    [$fromAddress, $fromName] = mail_from();
    $text ??= html_to_text($html);
    $boundary = 'b' . bin2hex(random_bytes(12));
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $headers = [
        'Date: ' . date('r'),
        'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromAddress . '>',
        'Reply-To: ' . ($replyTo && valid_email($replyTo) ? $replyTo : setting('store_email', $fromAddress)),
        'MIME-Version: 1.0',
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . (parse_url((string) config('app_url'), PHP_URL_HOST) ?: 'localhost') . '>',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];
    if ($reference !== null) {
        $headers[] = 'In-Reply-To: ' . $reference;
        $headers[] = 'References: ' . $reference;
    }
    $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($text))
        . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($html))
        . "--{$boundary}--\r\n";

    try {
        return match (mail_config('driver', 'mail')) {
            'smtp'  => smtp_send($fromAddress, $to, $encodedSubject, $headers, $body),
            'log'   => log_mail($to, $encodedSubject, $headers, $body, $html),
            default => mail($to, $encodedSubject, $body, implode("\r\n", $headers), '-f' . $fromAddress),
        };
    } catch (Throwable $e) {
        $GLOBALS['mail_last_error'] = $e->getMessage();
        error_log('Email to ' . $to . ' failed: ' . $e->getMessage());
        return false;
    }
}

function log_mail(string $to, string $subject, array $headers, string $body, string $html): bool
{
    $dir = STORAGE_PATH . '/logs/mail';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = date('Ymd-His') . '-' . substr(preg_replace('/[^a-z0-9]+/i', '-', $to), 0, 40);
    // Two emails to the same person in the same second must not overwrite each other.
    $base = $name;
    for ($n = 2; is_file("{$dir}/{$name}.eml"); $n++) {
        $name = $base . '-' . $n;
    }
    file_put_contents("{$dir}/{$name}.eml", "To: {$to}\r\nSubject: {$subject}\r\n" . implode("\r\n", $headers) . "\r\n\r\n" . $body);
    file_put_contents("{$dir}/{$name}.html", $html);
    return true;
}

/** Sends as one shop: through its own email account when it has one, else the shared account. */
function send_branch_mail(?int $branchId, string $to, string $subject, string $html, ?string $replyTo = null, ?string $reference = null): bool
{
    return with_branch($branchId ?: null, static fn () => send_mail($to, $subject, $html, null, $replyTo, $reference));
}

/** Minimal SMTP client: SSL (port 465) or STARTTLS (port 587), with AUTH LOGIN. */
function smtp_send(string $from, string $to, string $subject, array $headers, string $body): bool
{
    $host = (string) mail_config('smtp.host');
    $port = (int) mail_config('smtp.port', 465);
    $secure = (string) mail_config('smtp.encryption', 'ssl');
    $socket = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, 20);
    if (!$socket) {
        throw new RuntimeException("Could not connect to SMTP server {$host}:{$port} ({$errstr})");
    }
    stream_set_timeout($socket, 20);

    $read = static function () use ($socket): string {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    };
    $send = static function (string $command, array $expect) use ($socket, $read): string {
        fwrite($socket, $command . "\r\n");
        $response = $read();
        if (!in_array((int) substr($response, 0, 3), $expect, true)) {
            throw new RuntimeException('SMTP error after "' . strtok($command, ' ') . '": ' . trim($response));
        }
        return $response;
    };

    $read();
    $hostname = parse_url((string) config('app_url'), PHP_URL_HOST) ?: 'localhost';
    $send('EHLO ' . $hostname, [250]);
    if ($secure === 'tls') {
        $send('STARTTLS', [220]);
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
            throw new RuntimeException('Could not start TLS with the SMTP server');
        }
        $send('EHLO ' . $hostname, [250]);
    }
    if ((string) mail_config('smtp.username') !== '') {
        $send('AUTH LOGIN', [334]);
        $send(base64_encode((string) mail_config('smtp.username')), [334]);
        $send(base64_encode((string) mail_config('smtp.password')), [235]);
    }
    $send('MAIL FROM:<' . $from . '>', [250]);
    $send('RCPT TO:<' . $to . '>', [250, 251]);
    $send('DATA', [354]);
    $message = "To: <{$to}>\r\nSubject: {$subject}\r\n" . implode("\r\n", $headers) . "\r\n\r\n" . $body;
    $send(preg_replace('/^\./m', '..', $message) . "\r\n.", [250]);
    $send('QUIT', [221]);
    fclose($socket);
    return true;
}

function html_to_text(string $html): string
{
    $html = preg_replace('#<(style|head)[^>]*>.*?</\1>#si', '', $html);
    $html = preg_replace('#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#si', '$2 ($1)', (string) $html);
    $html = preg_replace('#<(br|/p|/tr|/h[1-6]|/li|/div)[^>]*>#i', "\n", (string) $html);
    $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8');
    $text = preg_replace("/[ \t]+/", ' ', $text);
    return trim((string) preg_replace("/\n\s*\n+/", "\n\n", (string) $text));
}

// ---------------------------------------------------------------------------
// Templates
// ---------------------------------------------------------------------------

/** Renders templates/emails/{name}.php inside the branded layout. */
/** First name for "Hi Sarah,". Anything that looks like a link or an address becomes "there", so a form can't put a link in our emails. */
function email_first_name(?string $name): string
{
    $first = (string) strtok(trim((string) $name), ' ');
    return $first === '' || preg_match('#[./:@]|www#i', $first) ? 'there' : mb_substr($first, 0, 40);
}

function render_email(string $name, array $data): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require BASE_PATH . '/templates/emails/' . $name . '.php';
    $content = (string) ob_get_clean();
    $title = $data['title'] ?? setting('store_short_name');
    $preheader = $data['preheader'] ?? '';
    $reply_url = $data['reply_url'] ?? null;
    ob_start();
    require BASE_PATH . '/templates/emails/layout.php';
    return (string) ob_get_clean();
}

function email_button(string $href, string $label): string
{
    return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0"><tr><td style="border-radius:8px;background:#1E9B82">'
        . '<a href="' . e($href) . '" style="display:inline-block;padding:12px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#FFFFFF;text-decoration:none;border-radius:8px">' . e($label) . '</a>'
        . '</td></tr></table>';
}

function email_admin_url(string $path = ''): string
{
    return url('admin/' . ltrim($path, '/'));
}

// ---------------------------------------------------------------------------
// Email conversations. Every email about one order, custom cake request or chat has the same
// subject and points to the same conversation (References header), so Gmail, Outlook and Apple
// Mail show them together as one conversation instead of separate emails. A conversation is
// [id, subject], e.g. ['order-12', 'Order CC-1001'].
// ---------------------------------------------------------------------------

/** An order's conversation. An order made from a custom cake request stays in that request's conversation. */
function order_conversation(array $order): array
{
    $request = !empty($order['custom_request_id']) ? db_one('SELECT id, occasion FROM custom_requests WHERE id = ?', [(int) $order['custom_request_id']]) : null;
    return $request ? request_conversation($request) : ['order-' . (int) $order['id'], 'Order ' . $order['order_number']];
}

function request_conversation(array $request): array
{
    return ['request-' . (int) $request['id'], $request['occasion'] . ' cake request'];
}

/** A chat's conversation: its order's or request's when it has one. */
function thread_conversation(array $thread): array
{
    if (!empty($thread['custom_request_id']) && ($request = db_one('SELECT id, occasion FROM custom_requests WHERE id = ?', [(int) $thread['custom_request_id']]))) {
        return request_conversation($request);
    }
    if (!empty($thread['order_id']) && ($order = get_order((int) $thread['order_id']))) {
        return order_conversation($order);
    }
    return ['thread-' . (int) $thread['id'], (string) preg_replace('/^Email: /', '', (string) $thread['subject'])];
}

/** Subject of every customer email in a conversation, e.g. "Order CC-1001 - The Continental Cake Shop". */
function customer_subject(array $conversation): string
{
    return $conversation[1] . ' - ' . setting('store_short_name');
}

/**
 * The id all emails of a conversation point to. The customer's emails and the shop's alerts are
 * two conversations ('customer' and 'shop'), as they land in different inboxes.
 */
function conversation_reference(array $conversation, string $side): string
{
    return '<conv-' . $conversation[0] . '.' . $side . '@' . (parse_url((string) config('app_url'), PHP_URL_HOST) ?: 'localhost') . '>';
}

/** The order, request or chat an email reply belongs to, read from its References header: [type, id] or null. */
function conversation_in_references(string $references): ?array
{
    return preg_match('/<conv-(order|request|thread)-(\d+)\.customer@/', $references, $m) ? [$m[1], (int) $m[2]] : null;
}

// ---------------------------------------------------------------------------
// Emails the site sends
// ---------------------------------------------------------------------------

/** The "Thank you for your order" email with the items, date and a link to the order. */
function send_order_confirmation(array $order): bool
{
    $conversation = order_conversation($order);
    return with_branch((int) ($order['branch_id'] ?? 0) ?: null, static fn () => send_mail(
        $order['customer_email'],
        customer_subject($conversation),
        render_email('order-confirmation', ['order' => $order, 'items' => get_order_items((int) $order['id']), 'title' => 'Thank you for your order', 'preheader' => 'Order ' . $order['order_number'] . ' received, for ' . order_when($order), 'reply_url' => thread_url(order_thread($order))]),
        null,
        setting('store_email'),
        conversation_reference($conversation, 'customer')
    ));
}

/**
 * Subject of an email to the shop: the customer's name first, e.g. "Suzanna Pugh · Order CC-1009".
 * The alerts are sent from the shop's own address, so the inbox shows "me" as the sender;
 * the name at the start shows who it is about. What happened is at the top of the email.
 */
function alert_subject(string $customerName, string $subject): string
{
    $name = trim(preg_replace('/\s+/', ' ', $customerName));
    return $name !== '' ? $name . ' · ' . $subject : $subject;
}

/** Customer confirmation and shop notification for a new order. */
function send_order_emails(array $order): void
{
    $items = get_order_items((int) $order['id']);

    if (setting_bool('send_customer_emails')) {
        send_order_confirmation($order);
    }
    // The shop's copy goes to the branch that makes the order (never to the customer).
    $notify = shop_alert_address((int) ($order['branch_id'] ?? 0), (string) $order['customer_email']);
    if ($notify !== '') {
        $branch = multi_branch() ? branch_name((int) $order['branch_id']) : '';
        $conversation = order_conversation($order);
        send_branch_mail(
            (int) ($order['branch_id'] ?? 0),
            $notify,
            alert_subject($order['customer_name'], $conversation[1]),
            render_email('order-new-admin', ['order' => $order, 'items' => $items, 'title' => 'New online order' . ($branch !== '' ? ' for ' . $branch : ''), 'preheader' => 'New order ' . $order['order_number'] . ' · ' . money($order['total']) . ' · ' . order_when($order)]),
            $order['customer_email'],
            conversation_reference($conversation, 'shop')
        );
    }
}

/** Tells the customer about a change to their order (status or payment). */
function send_order_update_email(array $order, string $headline, string $message): void
{
    $conversation = order_conversation($order);
    with_branch((int) ($order['branch_id'] ?? 0) ?: null, static fn () => send_mail(
        $order['customer_email'],
        customer_subject($conversation),
        render_email('order-update', [
            'order' => $order, 'items' => get_order_items((int) $order['id']),
            'headline' => $headline, 'message' => $message,
            'title' => $headline, 'preheader' => rtrim($headline, '.!') . '. ' . $message,
            'reply_url' => thread_url(order_thread($order)),
        ]),
        null,
        setting('store_email'),
        conversation_reference($conversation, 'customer')
    ));
}

function send_custom_request_emails(array $request): void
{
    $previous = use_branch(get_branch((int) ($request['branch_id'] ?? 0)));
    $conversation = request_conversation($request);
    send_mail(
        $request['email'],
        customer_subject($conversation),
        render_email('custom-request-customer', ['request' => $request, 'title' => 'Thanks for your custom cake request', 'preheader' => 'We received your request and will be in touch with a quote soon.', 'reply_url' => thread_url(custom_request_thread($request))]),
        null,
        setting('store_email'),
        conversation_reference($conversation, 'customer')
    );
    $notify = shop_alert_address((int) ($request['branch_id'] ?? 0), (string) $request['email']);
    if ($notify !== '') {
        send_mail(
            $notify,
            alert_subject($request['name'], $conversation[1]),
            render_email('custom-request-admin', ['request' => $request, 'title' => 'New custom cake request', 'preheader' => 'New request: ' . $request['occasion'] . ' cake for ' . date('j M Y', strtotime($request['event_date'])) . (multi_branch() ? ' · ' . branch_name((int) $request['branch_id']) : '')]),
            null,
            $request['email'],
            conversation_reference($conversation, 'shop')
        );
    }
    use_branch($previous);
}

function send_custom_quote_email(array $request, string $message): bool
{
    $conversation = request_conversation($request);
    return with_branch((int) ($request['branch_id'] ?? 0) ?: null, static fn () => send_mail(
        $request['email'],
        customer_subject($conversation),
        render_email('custom-quote', ['request' => $request, 'message' => $message, 'title' => 'Your custom cake quote', 'preheader' => $request['quoted_price'] ? 'Quote: ' . money($request['quoted_price']) : 'A reply about your custom cake', 'reply_url' => thread_url(custom_request_thread($request))]),
        null,
        setting('store_email'),
        conversation_reference($conversation, 'customer')
    ));
}

/** Confirms a contact form message to the customer, with the link to follow the conversation. */
function send_contact_confirmation(array $thread, string $message): void
{
    $conversation = thread_conversation($thread);
    with_branch((int) ($thread['branch_id'] ?? 0) ?: null, static fn () => send_mail(
        $thread['customer_email'],
        customer_subject($conversation),
        render_email('contact-customer', ['thread' => $thread, 'message' => $message, 'title' => 'Thanks for your message', 'preheader' => 'We received your message and will reply soon.', 'reply_url' => thread_url($thread)]),
        null,
        setting('store_email'),
        conversation_reference($conversation, 'customer')
    ));
}

// ---------------------------------------------------------------------------
// Why emails may land in spam (Admin > System check and each shop's Email tab)
// ---------------------------------------------------------------------------

const FREE_MAIL_DOMAINS = ['gmail.com', 'googlemail.com', 'outlook.com', 'hotmail.com', 'live.com', 'msn.com', 'yahoo.com', 'yahoo.com.au',
    'icloud.com', 'me.com', 'bigpond.com', 'bigpond.net.au', 'optusnet.com.au'];

/**
 * Problems that send a shop's emails to spam, as [label, ok, how to fix] rows. Mail providers
 * trust an email only when it really comes from the address it says: a Gmail address sent by
 * the website's server is treated as fake. With $dns, the domain's SPF, DKIM and DMARC records
 * are looked up too (skipped where the server cannot look them up).
 */
function email_delivery_checks(int $branchId, bool $dns = true): array
{
    $branch = get_branch($branchId) ?? main_branch();
    $name = (string) ($branch['name'] ?? 'Main shop');
    $where = 'Branches > ' . $name . ' > Email';
    [$driver, $from, $host] = with_branch($branchId, static fn () => [(string) mail_config('driver', 'mail'), strtolower((string) mail_from()[0]), strtolower((string) mail_config('smtp.host', ''))]);
    $domain = (string) substr((string) strrchr($from, '@'), 1);
    $free = in_array($domain, FREE_MAIL_DOMAINS, true);
    $rows = [];
    if ($driver === 'log') {
        $rows[] = [$name . ': emails are really sent', false, 'They are only saved to a file. Set up the email account in ' . $where . '.'];
        return $rows;
    }
    if ($driver === 'mail') {
        $rows[] = [$name . ': sent through an email account', false, $free
            ? $from . ' is sent by the website server, so Gmail and Outlook treat it as fake and put it in spam. In ' . $where . ' choose ' . ($domain === 'gmail.com' || $domain === 'googlemail.com' ? 'Gmail' : 'the provider') . ' and add the account\'s password (for Gmail an app password).'
            : 'The website server sends the emails (PHP mail), which often lands in spam. In ' . $where . ' choose the email account (e.g. Hostinger or cPanel email) and add its password.'];
    } else {
        $rows[] = [$name . ': sent through an email account (' . $from . ')', true, ''];
        $gmailHost = str_contains($host, 'gmail.com') || str_contains($host, 'google');
        if ($free && ($domain === 'gmail.com' || $domain === 'googlemail.com') && !$gmailHost) {
            $rows[] = [$name . ': Gmail address sent by Gmail', false, $from . ' is sent through ' . $host . ', not Gmail, so it looks fake. In ' . $where . ' choose Gmail as the provider.'];
        }
    }
    if (!$free && $domain !== '' && $dns && function_exists('dns_get_record')) {
        $txt = static fn (string $name): array => array_column(@dns_get_record($name, DNS_TXT) ?: [], 'txt');
        $spf = $txt($domain);
        if ($spf || @dns_get_record($domain, DNS_A)) {
            $rows[] = [$domain . ': SPF record', (bool) preg_grep('/^v=spf1\b/i', $spf), 'Add an SPF record for ' . $domain . ': cPanel > Email Deliverability > ' . $domain . ' > Repair (or copy it to the DNS where the domain is managed).'];
            $rows[] = [$domain . ': DKIM record', (bool) ($txt('default._domainkey.' . $domain) ?: $txt('google._domainkey.' . $domain)), 'Add the DKIM record: cPanel > Email Deliverability > ' . $domain . ' > Repair.'];
            $rows[] = [$domain . ': DMARC record', (bool) preg_grep('/^v=DMARC1\b/i', $txt('_dmarc.' . $domain)), 'Add a TXT record named _dmarc.' . $domain . ' with: v=DMARC1; p=none; rua=mailto:' . $from];
        }
    }
    return $rows;
}

/** The checks for every shop that sends with its own account (others use the main shop's). */
function all_email_delivery_checks(bool $dns = true): array
{
    $rows = [];
    foreach (branches() as $branch) {
        if ((int) $branch['is_main'] || branch_uses_own((int) $branch['id'], 'email')) {
            $rows = array_merge($rows, email_delivery_checks((int) $branch['id'], $dns));
        }
    }
    return $rows;
}
