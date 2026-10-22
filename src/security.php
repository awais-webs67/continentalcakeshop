<?php
declare(strict_types=1);

// Limits per internet address (and per email where it matters), kept in the rate_limits table so
// they hold across sessions: a bot that throws its cookie away still counts as the same visitor.

/** The visitor's IP address (cPanel hosting talks to visitors directly, so REMOTE_ADDR is right). */
function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli');
}

/**
 * Counts one try for $action by $key and says whether the limit is now passed: more than $max
 * tries within $seconds. The count is updated in one statement, so many requests sent at the same
 * moment are all counted. Returns false (allowed) if the table is missing, e.g. before the update.
 */
function rate_hit(string $action, string $key, int $max, int $seconds): bool
{
    $bucket = sha1($action . '|' . strtolower($key));
    try {
        db_run(
            'INSERT INTO rate_limits (bucket, hits, window_start) VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE
                 hits = IF(window_start < NOW() - INTERVAL ? SECOND, 1, hits + 1),
                 window_start = IF(window_start < NOW() - INTERVAL ? SECOND, NOW(), window_start)',
            [$bucket, $seconds, $seconds]
        );
        return (int) db_value('SELECT hits FROM rate_limits WHERE bucket = ?', [$bucket]) > $max;
    } catch (PDOException) {
        return false;
    }
}

/** How many tries are counted for $action by $key in the current window (without adding one). */
function rate_count(string $action, string $key, int $seconds): int
{
    try {
        return (int) db_value('SELECT hits FROM rate_limits WHERE bucket = ? AND window_start >= NOW() - INTERVAL ? SECOND', [sha1($action . '|' . strtolower($key)), $seconds]);
    } catch (PDOException) {
        return 0;
    }
}

/** Starts the count again, e.g. after a successful login. */
function rate_clear(string $action, string $key): void
{
    try {
        db_run('DELETE FROM rate_limits WHERE bucket = ?', [sha1($action . '|' . strtolower($key))]);
    } catch (PDOException) {
    }
}

/**
 * Login throttle shared by customer and staff logins:
 *  - every try for one email from one connection is counted at once (so many requests sent at the
 *    same moment are all counted) and cleared after a successful login;
 *  - wrong passwords are also counted per connection (30) and per email from anywhere (50), so a
 *    guesser is slowed down without one connection being able to lock a real person out.
 */
function login_throttled(string $area, string $email): bool
{
    $email = strtolower(trim($email));
    if (rate_count($area . '-ip', client_ip(), 900) >= 30 || rate_count($area . '-email', $email, 900) >= 50) {
        return true;
    }
    return rate_hit($area, client_ip() . '|' . $email, LOGIN_MAX_ATTEMPTS, LOGIN_LOCK_MINUTES * 60);
}

/** Counts a wrong password (or an unknown email) for the per-connection and per-email limits. */
function login_failed(string $area, string $email): void
{
    rate_hit($area . '-ip', client_ip(), PHP_INT_MAX, 900);
    rate_hit($area . '-email', strtolower(trim($email)), PHP_INT_MAX, 900);
}

function login_throttle_clear(string $area, string $email): void
{
    rate_clear($area, client_ip() . '|' . strtolower(trim($email)));
}

const LOGIN_THROTTLED_MESSAGE = 'Too many attempts. Please wait 15 minutes and try again, or reset your password.';

/** A full web address that is safe to use as a link: http(s) only, never javascript: or data:. */
function safe_web_url(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false && preg_match('#^https?://[^\s/$.?\#].#i', $url) === 1;
}

/** Signed link that proves the customer can read this email address. */
function email_verification_token(array $user): string
{
    return hash_hmac('sha256', $user['id'] . '|' . strtolower($user['email']) . '|verify', app_secret_key());
}

function email_verification_url(array $user): string
{
    return url('verify-email') . '?u=' . (int) $user['id'] . '&t=' . email_verification_token($user);
}

function send_email_verification(array $user): bool
{
    if (rate_hit('verify-mail', (string) $user['id'], 3, 3600)) {
        return false;
    }
    return send_mail(
        $user['email'],
        'Confirm your email - ' . setting('store_short_name'),
        render_email('verify-email', ['user' => $user, 'link' => email_verification_url($user), 'title' => 'Confirm your email', 'preheader' => 'One click to confirm your email address.'])
    );
}

/** An account whose owner has confirmed this email address (used for anything linked by email alone). */
function find_verified_user_by_email(string $email): ?array
{
    $email = strtolower(trim($email));
    return $email === '' ? null : db_one('SELECT * FROM users WHERE email = ? AND email_verified_at IS NOT NULL', [$email]);
}
