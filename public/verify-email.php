<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// The link from the "Confirm your email" message. It is signed with the site's secret key, so only
// someone who received the email at that address can confirm it.
$user = db_one('SELECT id, name, email, email_verified_at FROM users WHERE id = ?', [(int) input('u')]);
$token = (string) input('t');
if (!$user || !preg_match('/^[a-f0-9]{64}$/', $token) || !hash_equals(email_verification_token($user), $token)) {
    flash('error', 'That confirmation link is not valid any more. Log in and send a new one from your account.');
    redirect(is_logged_in() ? 'account' : 'login');
}
if (!$user['email_verified_at']) {
    db_run('UPDATE users SET email_verified_at = NOW() WHERE id = ?', [$user['id']]);
}
flash('success', 'Thank you, your email is confirmed.');
redirect(is_logged_in() ? 'account' : 'login');
