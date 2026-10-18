<?php
declare(strict_types=1);

// Customer accounts: register, log in, log out and password reset.
// Accounts are optional; guests can still check out.

const PASSWORD_MIN_LENGTH = 8;
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_MINUTES = 15;
const RESET_TOKEN_MINUTES = 60;

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $id = (int) ($_SESSION['user_id'] ?? 0);
        $user = $id ? db_one('SELECT id, name, email, phone, created_at FROM users WHERE id = ?', [$id]) : null;
        if ($id && !$user) {
            unset($_SESSION['user_id']);
        }
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

/** Sends guests to the login page and brings them back afterwards. */
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        $_SESSION['after_login'] = current_path();
        redirect('login');
    }
    return $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    db_run('UPDATE users SET failed_logins = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?', [$userId]);
}

function logout_user(): void
{
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}

/** Only allow redirects back to pages on this site. */
function take_after_login(string $default = 'account'): string
{
    $path = (string) ($_SESSION['after_login'] ?? '');
    unset($_SESSION['after_login']);
    return preg_match('#^/[a-z0-9/_-]*$#i', $path) ? ltrim($path, '/') : $default;
}

function find_user_by_email(string $email): ?array
{
    return db_one('SELECT * FROM users WHERE email = ?', [strtolower(trim($email))]);
}

/**
 * Validates and creates an account. Returns [user id, errors].
 *
 * @return array{0:?int,1:array<string,string>}
 */
function register_user(string $name, string $email, string $phone, string $password): array
{
    $email = strtolower(trim($email));
    $errors = [];
    if (trim($name) === '') {
        $errors['name'] = 'Please enter your name.';
    }
    if (!valid_email($email)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (find_user_by_email($email)) {
        $errors['email'] = 'An account with this email already exists. Please log in instead.';
    }
    if ($phone !== '' && !valid_au_phone($phone)) {
        $errors['phone'] = 'Please enter an Australian phone number.';
    }
    if (mb_strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors['password'] = 'Please use at least ' . PASSWORD_MIN_LENGTH . ' characters for your password.';
    }
    if ($errors) {
        return [null, $errors];
    }
    db_run(
        'INSERT INTO users (name, email, phone, password_hash) VALUES (?, ?, ?, ?)',
        [str_limit(trim($name), 120), $email, $phone !== '' ? str_limit($phone, 40) : null, password_hash($password, PASSWORD_DEFAULT)]
    );
    return [(int) db()->lastInsertId(), []];
}

/** Checks credentials with a short lock after repeated failures. Returns [user, error]. */
function attempt_login(string $email, string $password): array
{
    $user = find_user_by_email($email);
    $generic = 'That email and password do not match. Please try again.';
    if (!$user) {
        password_verify($password, '$2y$10$YHBeJrKlV.IVtnhkuGstZOkPft3zghGztr7dRyafzhnzmREtFlpni'); // keeps timing similar
        return [null, $generic];
    }
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        return [null, 'Too many attempts. Please wait ' . LOGIN_LOCK_MINUTES . ' minutes or reset your password.'];
    }
    if (!password_verify($password, $user['password_hash'])) {
        $failed = (int) $user['failed_logins'] + 1;
        $lock = $failed >= LOGIN_MAX_ATTEMPTS ? date('Y-m-d H:i:s', time() + LOGIN_LOCK_MINUTES * 60) : null;
        db_run('UPDATE users SET failed_logins = ?, locked_until = ? WHERE id = ?', [$lock ? 0 : $failed, $lock, $user['id']]);
        return [null, $generic];
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        db_run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }
    return [$user, null];
}

/** Emails a one-time reset link. Silent when the email is unknown so accounts cannot be discovered. */
function send_password_reset(string $email): void
{
    $user = find_user_by_email($email);
    if (!$user) {
        return;
    }
    $token = bin2hex(random_bytes(32));
    db_run('DELETE FROM password_resets WHERE user_id = ? OR expires_at < NOW()', [$user['id']]);
    db_run(
        'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)',
        [$user['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + RESET_TOKEN_MINUTES * 60)]
    );
    $link = url('reset-password') . '?token=' . $token;
    send_mail(
        $user['email'],
        'Reset your password - ' . setting('store_short_name'),
        render_email('password-reset', ['user' => $user, 'link' => $link, 'minutes' => RESET_TOKEN_MINUTES, 'title' => 'Reset your password', 'preheader' => 'Choose a new password for your account.'])
    );
}

function find_reset(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    return db_one(
        'SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()',
        [hash('sha256', $token)]
    );
}

function get_user_orders(int $userId): array
{
    return db_all(
        'SELECT o.*, (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) AS item_count
         FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC, o.id DESC',
        [$userId]
    );
}
