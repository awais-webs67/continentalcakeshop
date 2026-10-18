<?php
declare(strict_types=1);

// Branded HTML emails (with a plain-text part) sent through one of three drivers, set in config:
//   'mail' - PHP mail(), works on most cPanel hosts out of the box
//   'smtp' - any SMTP server (cPanel email account, Gmail, Microsoft 365...)
//   'log'  - writes each email to storage/logs/mail so you can read it during local testing

function mail_config(string $key, mixed $default = null): mixed
{
    return config('mail.' . $key, $default);
}

function mail_from(): array
{
    return [
        (string) mail_config('from_address', config('mail_from', setting('store_email'))),
        (string) mail_config('from_name', setting('store_short_name')),
    ];
}

/**
 * Sends one email. $html is the full HTML document; $text is generated from it when omitted.
 */
function send_mail(string $to, string $subject, string $html, ?string $text = null, ?string $replyTo = null): bool
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
    file_put_contents("{$dir}/{$name}.eml", "To: {$to}\r\nSubject: {$subject}\r\n" . implode("\r\n", $headers) . "\r\n\r\n" . $body);
    file_put_contents("{$dir}/{$name}.html", $html);
    return true;
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
function render_email(string $name, array $data): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require BASE_PATH . '/templates/emails/' . $name . '.php';
    $content = (string) ob_get_clean();
    $title = $data['title'] ?? setting('store_short_name');
    $preheader = $data['preheader'] ?? '';
    ob_start();
    require BASE_PATH . '/templates/emails/layout.php';
    return (string) ob_get_clean();
}

function email_button(string $href, string $label): string
{
    return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:999px;background:#1A8670">'
        . '<a href="' . e($href) . '" style="display:inline-block;padding:14px 28px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#FFFFFF;text-decoration:none;border-radius:999px">' . e($label) . '</a>'
        . '</td></tr></table>';
}

function email_admin_url(string $path = ''): string
{
    return url('admin/' . ltrim($path, '/'));
}

// ---------------------------------------------------------------------------
// Emails the site sends
// ---------------------------------------------------------------------------

/** Customer confirmation and shop notification for a new order. */
function send_order_emails(array $order): void
{
    $items = get_order_items((int) $order['id']);
    $shop = setting('store_short_name');

    if (setting_bool('send_customer_emails')) {
        send_mail(
            $order['customer_email'],
            "Order {$order['order_number']} received - {$shop}",
            render_email('order-confirmation', ['order' => $order, 'items' => $items, 'title' => 'Thank you for your order', 'preheader' => 'Order ' . $order['order_number'] . ' for ' . format_pickup($order['pickup_date'], $order['pickup_time'])])
        );
    }
    $notify = setting('order_notification_email');
    if ($notify !== '') {
        send_mail(
            $notify,
            "New order {$order['order_number']} - " . money($order['total']),
            render_email('order-new-admin', ['order' => $order, 'items' => $items, 'title' => 'New online order', 'preheader' => $order['customer_name'] . ', ' . format_pickup($order['pickup_date'], $order['pickup_time'])]),
            null,
            $order['customer_email']
        );
    }
}

/** Tells the customer about a change to their order (status or payment). */
function send_order_update_email(array $order, string $headline, string $message): void
{
    send_mail(
        $order['customer_email'],
        "Update on order {$order['order_number']} - " . setting('store_short_name'),
        render_email('order-update', [
            'order' => $order, 'items' => get_order_items((int) $order['id']),
            'headline' => $headline, 'message' => $message,
            'title' => $headline, 'preheader' => $message,
        ])
    );
}

function send_custom_request_emails(array $request): void
{
    send_mail(
        $request['email'],
        'We received your custom cake request - ' . setting('store_short_name'),
        render_email('custom-request-customer', ['request' => $request, 'title' => 'Thanks for your custom cake request', 'preheader' => 'We will be in touch with a quote soon.'])
    );
    $notify = setting('order_notification_email');
    if ($notify !== '') {
        send_mail(
            $notify,
            'Custom cake request: ' . $request['occasion'] . ' on ' . date('j M Y', strtotime($request['event_date'])),
            render_email('custom-request-admin', ['request' => $request, 'title' => 'New custom cake request', 'preheader' => $request['name'] . ', ' . $request['occasion']]),
            null,
            $request['email']
        );
    }
}

function send_custom_quote_email(array $request, string $message): bool
{
    return send_mail(
        $request['email'],
        'Your custom cake quote - ' . setting('store_short_name'),
        render_email('custom-quote', ['request' => $request, 'message' => $message, 'title' => 'Your custom cake quote', 'preheader' => $request['quoted_price'] ? 'Quote: ' . money($request['quoted_price']) : 'A reply about your custom cake']),
        null,
        setting('store_email')
    );
}

function send_contact_notification(array $data): void
{
    $notify = setting('order_notification_email');
    if ($notify !== '') {
        send_mail(
            $notify,
            'Website message from ' . $data['name'],
            render_email('contact-admin', ['data' => $data, 'title' => 'New message from the website', 'preheader' => mb_substr($data['message'], 0, 90)]),
            null,
            $data['email']
        );
    }
}
