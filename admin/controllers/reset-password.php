<?php
// Sets a new admin password from an emailed one-time link.
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$reset = preg_match('/^[a-f0-9]{64}$/', $token)
    ? db_one('SELECT r.*, a.email FROM admin_password_resets r JOIN admins a ON a.id = r.admin_id AND a.is_active = 1 WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW()', [hash('sha256', $token)])
    : null;
if (!$reset) {
    flash('error', 'That reset link has expired or was already used. Please ask for a new one.');
    redirect(admin_url('forgot-password'));
}
$error = null;
if (is_post()) {
    $password = (string) ($_POST['password'] ?? '');
    if (mb_strlen($password) < 10) {
        $error = 'Use at least 10 characters.';
    } elseif ($password !== (string) ($_POST['confirm'] ?? '')) {
        $error = 'The two passwords do not match.';
    } else {
        db_run('UPDATE admins SET password_hash = ?, failed_logins = 0, locked_until = NULL WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $reset['admin_id']]);
        db_run('UPDATE admin_password_resets SET used_at = NOW() WHERE id = ?', [$reset['id']]);
        flash('success', 'Your password was changed. Please log in.');
        redirect(admin_url('login'));
    }
}
admin_view('reset-password', ['title' => 'Choose a new password', 'layout' => 'auth-layout', 'token' => $token, 'email' => $reset['email'], 'error' => $error]);
