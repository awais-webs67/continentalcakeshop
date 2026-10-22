<?php
// Creates the first owner account. Only available while no admin accounts exist.
if (admin_count() > 0) {
    redirect(admin_url('login'));
}
$errors = [];
if (is_post()) {
    $name = str_limit(input('name'), 120);
    $email = strtolower(str_limit(input('email'), 190));
    $password = (string) ($_POST['password'] ?? '');
    if ($name === '') {
        $errors['name'] = 'Please enter your name.';
    }
    if (!valid_email($email)) {
        $errors['email'] = 'Please enter a valid email.';
    } elseif (email_used_by_customer($email)) {
        $errors['email'] = CUSTOMER_EMAIL_TAKEN;
    }
    if (mb_strlen($password) < 10) {
        $errors['password'] = 'Use at least 10 characters for the owner password.';
    }
    if (!$errors) {
        db_run("INSERT INTO admins (name, email, password_hash, role) VALUES (?, ?, ?, 'owner')", [$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        admin_login((int) db()->lastInsertId());
        flash('success', 'Welcome! Your owner account is ready.');
        redirect(admin_url());
    }
}
admin_view('setup', ['title' => 'Set up', 'layout' => 'auth-layout', 'errors' => $errors]);
