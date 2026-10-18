<?php
// Emails a one-time link to choose a new admin password. The reply is the same whether or
// not the email belongs to an account, so the page cannot be used to find staff emails.
if (current_admin()) {
    redirect(admin_url());
}
if (is_post()) {
    if (is_spam_submission('admin-forgot', 3, 15)) {
        flash('error', 'Too many requests. Please wait a few minutes and try again.');
        redirect(admin_url('forgot-password'));
    }
    $admin = db_one('SELECT * FROM admins WHERE email = ? AND is_active = 1', [strtolower(trim(input('email')))]);
    if ($admin) {
        $token = bin2hex(random_bytes(32));
        db_run('DELETE FROM admin_password_resets WHERE admin_id = ? OR expires_at < NOW()', [$admin['id']]);
        db_run('INSERT INTO admin_password_resets (admin_id, token_hash, expires_at) VALUES (?, ?, ?)', [$admin['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + 30 * 60)]);
        send_mail(
            $admin['email'],
            'Reset your admin password - ' . setting('store_short_name'),
            render_email('password-reset', ['user' => $admin, 'link' => admin_url('reset-password', ['token' => $token]), 'minutes' => 30, 'title' => 'Reset your admin password', 'preheader' => 'Choose a new password for the admin panel.'])
        );
    }
    flash('success', 'If that email belongs to an admin account, a reset link is on its way. It works for 30 minutes.');
    redirect(admin_url('login'));
}
admin_view('forgot-password', ['title' => 'Forgot password', 'layout' => 'auth-layout']);
