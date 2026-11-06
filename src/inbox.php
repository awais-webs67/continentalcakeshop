<?php
declare(strict_types=1);

// ---------------------------------------------------------------------------
// A shop's inbox in Admin > Messages
// ---------------------------------------------------------------------------
// Every few minutes (and with "Check inbox now") the website reads new emails from the shop's
// email account over IMAP and adds them to Messages: a reply to an order email joins that
// order's conversation, other emails start a conversation with the customer. The emails stay
// in the email app too; nothing is deleted or moved there. Replies written in the panel are
// emailed from the same account, so the customer's next reply comes back the same way.
// Only emails that arrive after the inbox is switched on are read.

const EMAIL_PROVIDERS = [
    'gmail'     => ['Gmail', 'smtp.gmail.com', 465, 'ssl', 'imap.gmail.com', 993, 'Use an app password: Google Account > Security > 2-Step Verification > App passwords.'],
    'hostinger' => ['Hostinger email', 'smtp.hostinger.com', 465, 'ssl', 'imap.hostinger.com', 993, 'The password of the mailbox in Hostinger > Emails.'],
    'outlook'   => ['Outlook / Microsoft 365', 'smtp.office365.com', 587, 'tls', 'outlook.office365.com', 993, 'Your Microsoft account password, or an app password if two-step sign in is on.'],
    'other'     => ['Other (cPanel and others)', '', 465, 'ssl', '', 993, 'The server names are in your email provider\'s set up guide.'],
    'server'    => ['Website server (PHP mail)', '', 0, '', '', 0, 'No password needed, but emails can land in spam and there is no inbox in Messages. Gmail or Hostinger email is better.'],
];
const INBOX_MAX_PER_RUN = 25;

/** The providers to choose from; "Save to a file" only while the site is in that test mode. */
function email_providers(): array
{
    $testing = with_branch(null, static fn () => mail_config('driver', 'mail')) === 'log';
    return EMAIL_PROVIDERS + ($testing ? ['log' => ['Save to a file (testing only)', '', 0, '', '', 0, 'Emails are not sent; they are saved in storage/logs/mail.']] : []);
}

/** Where a shop's inbox is read from: its own account, or (main shop) the main account. Null = not possible. */
function inbox_account(array $branch): ?array
{
    $id = (int) $branch['id'];
    if (branch_uses_own($id, 'email')) {
        $get = static fn (string $k): string => (string) branch_setting($id, $k);
    } elseif ((int) $branch['is_main']) {
        $get = static fn (string $k): string => setting_raw($k);
    } else {
        return null; // a shop on the shared account: the shared inbox belongs to the main shop
    }
    $user = $get('smtp_username') ?: $get('mail_from_address');
    $host = $get('imap_host');
    $password = secret_decrypt($get('smtp_password_encrypted'));
    if ($user === '' || $host === '' || $password === '') {
        return null;
    }
    return ['host' => $host, 'port' => (int) ($get('imap_port') ?: 993), 'user' => $user, 'password' => $password,
            'address' => strtolower($get('mail_from_address') ?: $user)];
}

function inbox_enabled(array $branch): bool
{
    return branch_setting((int) $branch['id'], 'inbox_enabled') === '1';
}

/** Reads new emails of every shop with the inbox switched on (the scheduled job). */
function inbox_task(DateTimeImmutable $now): string
{
    $report = [];
    foreach (branches() as $branch) {
        if (!inbox_enabled($branch)) {
            continue;
        }
        $result = inbox_check($branch);
        $report[] = $branch['name'] . ': ' . $result;
    }
    return $report ? 'Inbox ' . implode('; ', $report) : 'No inbox to check.';
}

/** Reads one shop's new emails into Messages. Returns a short line for the log and the admin. */
function inbox_check(array $branch): string
{
    $id = (int) $branch['id'];
    $account = inbox_account($branch);
    if (!$account) {
        branch_setting_save($id, 'inbox_error', 'The email account, password or inbox server is missing.');
        return 'not set up';
    }
    try {
        $imap = new ImapClient($account['host'], $account['port']);
        $imap->login($account['user'], $account['password']);
        [$validity, $next] = $imap->select('INBOX');
        $last = (int) branch_setting($id, 'inbox_last_uid');
        // First check, or the mailbox was rebuilt: start from now instead of importing old mail.
        // (An empty inbox stores 0, so its first email is still read next time.)
        if (branch_setting($id, 'inbox_uidvalidity') !== (string) $validity || branch_setting($id, 'inbox_last_uid') === null) {
            branch_setting_save($id, 'inbox_uidvalidity', (string) $validity);
            branch_setting_save($id, 'inbox_last_uid', (string) max(0, $next - 1));
            branch_setting_save($id, 'inbox_checked_at', date('Y-m-d H:i:s'));
            branch_setting_save($id, 'inbox_error', null);
            $imap->logout();
            return 'connected, new emails from now on will show in Messages';
        }
        $uids = array_values(array_filter($imap->searchFrom($last + 1), static fn ($uid) => $uid > $last));
        sort($uids);
        $added = 0;
        foreach (array_slice($uids, 0, INBOX_MAX_PER_RUN) as $uid) {
            $raw = $imap->fetch($uid);
            if ($raw !== '' && inbox_import($branch, mail_parse($raw), $account['address'])) {
                $added++;
            }
            branch_setting_save($id, 'inbox_last_uid', (string) $uid);
        }
        $imap->logout();
        branch_setting_save($id, 'inbox_checked_at', date('Y-m-d H:i:s'));
        branch_setting_save($id, 'inbox_error', null);
        return $added ? $added . ' new email' . ($added === 1 ? '' : 's') . ' added to Messages' : 'no new emails';
    } catch (Throwable $e) {
        branch_setting_save($id, 'inbox_error', mb_substr($e->getMessage(), 0, 250));
        return 'could not connect: ' . $e->getMessage();
    }
}

/** Adds one email to Messages. False when it is skipped (our own mail, automatic replies, no sender). */
function inbox_import(array $branch, array $mail, string $ownAddress): bool
{
    $from = strtolower($mail['from_email']);
    if (!valid_email($from) || $mail['auto'] || $from === $ownAddress || $from === strtolower(setting_raw('mail_from_address'))
        || preg_match('/^(mailer-daemon|postmaster|no-?reply|donotreply)@/i', $from) || trim($mail['text']) === '') {
        return false;
    }
    $branchId = (int) $branch['id'];
    $thread = null;
    // A reply to an order email: the order number is in the subject, and it comes from that customer
    // (so nobody can write into someone else's order chat).
    if (preg_match('/\bCCS-\d{6}-[A-Z0-9]{4,6}\b/i', $mail['subject'], $m) && ($order = get_order_by_number(strtoupper($m[0])))
        && strcasecmp((string) $order['customer_email'], $from) === 0) {
        $thread = order_thread($order);
    }
    // Otherwise the customer's latest conversation with this shop, if it is recent.
    $thread ??= db_one(
        "SELECT * FROM threads WHERE customer_email = ? AND branch_id = ? AND last_message_at >= NOW() - INTERVAL 60 DAY ORDER BY last_message_at DESC LIMIT 1",
        [$from, $branchId]
    );
    if (!$thread) {
        $subject = trim((string) preg_replace('/^((re|fwd?|aw):\s*)+/i', '', $mail['subject']));
        $thread = create_thread('Email: ' . ($subject !== '' ? $subject : 'no subject'), $mail['from_name'] ?: $from, $from, null,
            ['branch_id' => $branchId, 'user_id' => find_verified_user_by_email($from)['id'] ?? null]);
    }
    if ($thread['status'] === 'closed') {
        db_run("UPDATE threads SET status = 'open' WHERE id = ?", [$thread['id']]);
    }
    // No "new message" email to the shop: it is already in the shop's email app.
    add_thread_message($thread, 'customer', str_limit($mail['text'], 10000));
    return true;
}

// ---------------------------------------------------------------------------
// Reading emails
// ---------------------------------------------------------------------------

/** The parts of an email the panel needs. */
function mail_parse(string $raw): array
{
    [$headers, $body] = mail_split($raw);
    $from = mail_decode_header($headers['from'] ?? '');
    preg_match('/<([^>]+)>/', $from, $m);
    $email = trim($m[1] ?? $from);
    $name = trim((string) preg_replace('/\s*<[^>]+>\s*/', '', $from), " \"'");
    $text = mail_body_text($headers, $body);
    return [
        'from_email' => $email,
        'from_name'  => $name !== $email ? str_limit($name, 120) : '',
        'subject'    => str_limit(mail_decode_header($headers['subject'] ?? ''), 160),
        'auto'       => isset($headers['auto-submitted']) && stripos($headers['auto-submitted'], 'no') !== 0
                        || isset($headers['x-autoreply']) || stripos($headers['precedence'] ?? '', 'bulk') !== false
                        || stripos($headers['precedence'] ?? '', 'auto_reply') !== false,
        'text'       => mail_strip_quote($text),
    ];
}

/** [lowercase header names => values, body] */
function mail_split(string $raw): array
{
    $raw = str_replace("\r\n", "\n", $raw);
    $pos = strpos($raw, "\n\n");
    $head = $pos === false ? $raw : substr($raw, 0, $pos);
    $body = $pos === false ? '' : substr($raw, $pos + 2);
    $headers = [];
    foreach (explode("\n", (string) preg_replace("/\n[ \t]+/", ' ', $head)) as $line) {
        if (str_contains($line, ':')) {
            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))] ??= trim($value);
        }
    }
    return [$headers, $body];
}

function mail_decode_header(string $value): string
{
    $decoded = function_exists('iconv_mime_decode') ? @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') : false;
    return trim($decoded !== false ? $decoded : mb_decode_mimeheader($value));
}

/** The plain text of a message: the text part, or the HTML part without its tags. */
function mail_body_text(array $headers, string $body, int $depth = 0): string
{
    $type = strtolower($headers['content-type'] ?? 'text/plain');
    if ($depth < 5 && str_starts_with($type, 'multipart/') && preg_match('/boundary="?([^";]+)"?/i', $headers['content-type'], $b)) {
        $parts = explode('--' . $b[1], $body);
        $html = '';
        foreach (array_slice($parts, 1) as $part) {
            if (str_starts_with($part, '--')) {
                break;
            }
            [$h, $content] = mail_split(ltrim($part, "\r\n"));
            $ptype = strtolower($h['content-type'] ?? 'text/plain');
            if (str_contains(strtolower($h['content-disposition'] ?? ''), 'attachment')) {
                continue;
            }
            if (str_starts_with($ptype, 'multipart/')) {
                $inner = mail_body_text($h, $content, $depth + 1);
                if ($inner !== '') {
                    return $inner;
                }
            } elseif (str_starts_with($ptype, 'text/plain')) {
                return mail_decode_part($h, $content);
            } elseif (str_starts_with($ptype, 'text/html') && $html === '') {
                $html = mail_decode_part($h, $content);
            }
        }
        return $html !== '' ? mail_html_to_text($html) : '';
    }
    $text = mail_decode_part($headers, $body);
    return str_starts_with($type, 'text/html') ? mail_html_to_text($text) : $text;
}

function mail_decode_part(array $headers, string $content): string
{
    $encoding = strtolower($headers['content-transfer-encoding'] ?? '');
    $content = match ($encoding) {
        'base64'           => (string) base64_decode((string) preg_replace('/\s+/', '', $content)),
        'quoted-printable' => quoted_printable_decode($content),
        default            => $content,
    };
    $charset = preg_match('/charset="?([\w-]+)"?/i', $headers['content-type'] ?? '', $c) ? strtoupper($c[1]) : 'UTF-8';
    if ($charset !== 'UTF-8' && $charset !== 'US-ASCII') {
        $converted = @mb_convert_encoding($content, 'UTF-8', $charset);
        $content = $converted !== false ? $converted : $content;
    }
    return trim(str_replace("\r", '', $content));
}

function mail_html_to_text(string $html): string
{
    $html = (string) preg_replace('#<(style|script|head)[^>]*>.*?</\1>#is', '', $html);
    $html = (string) preg_replace('#<br\s*/?>|</(p|div|li|tr|h\d)>#i', "\n", $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string) preg_replace("/\n{3,}/", "\n\n", (string) preg_replace('/[ \t]+/', ' ', $text)));
}

/** Leaves out the earlier emails quoted under a reply ("On ... wrote:", "> ..."). */
function mail_strip_quote(string $text): string
{
    $lines = explode("\n", $text);
    $keep = [];
    foreach ($lines as $line) {
        $t = trim($line);
        if (preg_match('/^On .{6,200} wrote:$/i', $t) || preg_match('/^-{2,}\s*(Original Message|Forwarded message)/i', $t)
            || preg_match('/^From: .+/i', $t) && count($keep) > 0 || preg_match('/^Sent from my (iPhone|iPad|Android|Samsung)/i', $t)) {
            break;
        }
        if (str_starts_with($t, '>')) {
            continue;
        }
        $keep[] = rtrim($line);
    }
    return mb_substr(trim(implode("\n", $keep)), 0, 5000);
}

// ---------------------------------------------------------------------------
// A small IMAP client: just enough to log in, find new emails and read them.
// ---------------------------------------------------------------------------

final class ImapClient
{
    /** @var resource */
    private $socket;
    private int $tag = 0;

    public function __construct(string $host, int $port)
    {
        // The certificate is always checked; only a local test setup may switch that off (config imap_verify_peer).
        $verify = (bool) config('imap_verify_peer', true);
        $context = stream_context_create(['ssl' => ['verify_peer' => $verify, 'verify_peer_name' => $verify, 'allow_self_signed' => !$verify, 'SNI_enabled' => true]]);
        $socket = @stream_socket_client('ssl://' . $host . ':' . $port, $errno, $error, 15, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new RuntimeException('Cannot reach ' . $host . ':' . $port . ($error ? ' (' . $error . ')' : ''));
        }
        stream_set_timeout($socket, 30);
        $this->socket = $socket;
        $this->line(); // greeting
    }

    public function login(string $user, string $password): void
    {
        $this->command('LOGIN ' . $this->quote($user) . ' ' . $this->quote($password), 'The email or password was not accepted.');
    }

    /** Opens a mailbox read-only. Returns [UIDVALIDITY, UIDNEXT]. */
    public function select(string $mailbox): array
    {
        $response = $this->command('EXAMINE ' . $this->quote($mailbox));
        $validity = preg_match('/UIDVALIDITY (\d+)/', $response, $v) ? (int) $v[1] : 0;
        $next = preg_match('/UIDNEXT (\d+)/', $response, $n) ? (int) $n[1] : 1;
        return [$validity, $next];
    }

    /** UIDs of messages from this UID on. */
    public function searchFrom(int $uid): array
    {
        $response = $this->command('UID SEARCH UID ' . $uid . ':*');
        return preg_match('/\* SEARCH([\d ]*)/', $response, $m) ? array_map('intval', array_filter(explode(' ', trim($m[1])))) : [];
    }

    /** The whole message, without marking it as read. */
    public function fetch(int $uid): string
    {
        $response = $this->command('UID FETCH ' . $uid . ' (BODY.PEEK[])', null, true);
        return $response;
    }

    public function logout(): void
    {
        try {
            $this->command('LOGOUT');
        } catch (Throwable) {
        }
        fclose($this->socket);
    }

    private function quote(string $value): string
    {
        return '"' . addcslashes($value, '"\\') . '"';
    }

    private function line(): string
    {
        $line = fgets($this->socket);
        if ($line === false) {
            throw new RuntimeException('The email server closed the connection.');
        }
        return $line;
    }

    /**
     * Sends a command and reads until its tagged reply. With $literal, returns only the message
     * text the server sends as a literal ({123}); otherwise the whole reply.
     */
    private function command(string $command, ?string $failure = null, bool $literal = false): string
    {
        $tag = 'A' . (++$this->tag);
        fwrite($this->socket, $tag . ' ' . $command . "\r\n");
        $out = '';
        $data = '';
        while (true) {
            $line = $this->line();
            if (preg_match('/\{(\d+)\}\r\n$/', $line, $m)) {
                $length = (int) $m[1];
                $chunk = '';
                while (strlen($chunk) < $length) {
                    $part = fread($this->socket, $length - strlen($chunk));
                    if ($part === false || $part === '') {
                        throw new RuntimeException('The email server stopped while sending a message.');
                    }
                    $chunk .= $part;
                }
                $data .= $chunk;
                $out .= $line;
                continue;
            }
            $out .= $line;
            if (str_starts_with($line, $tag . ' ')) {
                if (!preg_match('/^' . $tag . ' OK/i', $line)) {
                    throw new RuntimeException($failure ?? trim(substr($line, strlen($tag) + 1)));
                }
                break;
            }
        }
        return $literal ? $data : $out;
    }
}
