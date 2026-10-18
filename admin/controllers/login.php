<?php
if (current_admin()) {
    redirect(admin_url());
}
if (is_post()) {
    [$admin, $error] = admin_attempt_login(input('email'), (string) ($_POST['password'] ?? ''));
    if (!$admin) {
        flash('error', $error);
        remember_input(['email' => input('email')]);
        redirect(admin_url('login'));
    }
    admin_login((int) $admin['id']);
    forget_input();
    $back = (string) ($_SESSION['admin_after_login'] ?? '');
    unset($_SESSION['admin_after_login']);
    redirect(str_starts_with($back, admin_url()) ? $back : admin_url());
}
admin_view('login', ['title' => 'Log in', 'layout' => 'auth-layout']);
