<?php
$admin = require_admin();
$errors = [];

if (is_post()) {
    $row = db_one('SELECT * FROM admins WHERE id = ?', [$admin['id']]);
    if (!password_verify((string) ($_POST['current_password'] ?? ''), $row['password_hash'])) {
        $errors['current_password'] = 'Your current password is not correct.';
    }
    $name = str_limit(trim(input('name')), 120);
    $email = strtolower(str_limit(trim(input('email')), 190));
    $password = (string) ($_POST['new_password'] ?? '');
    if ($name === '') {
        $errors['name'] = 'Please enter your name.';
    }
    if (!valid_email($email)) {
        $errors['email'] = 'Please enter a valid email.';
    } elseif (db_value('SELECT 1 FROM admins WHERE email = ? AND id <> ?', [$email, $admin['id']])) {
        $errors['email'] = 'Another account already uses that email.';
    } elseif ($email !== strtolower((string) $admin['email']) && email_used_by_customer($email)) {
        $errors['email'] = CUSTOMER_EMAIL_TAKEN;
    }
    if ($password !== '' && mb_strlen($password) < 10) {
        $errors['new_password'] = 'Use at least 10 characters.';
    } elseif ($password !== '' && $password !== (string) ($_POST['confirm_password'] ?? '')) {
        $errors['confirm_password'] = 'The two new passwords do not match.';
    }
    if (!$errors) {
        db_run('UPDATE admins SET name = ?, email = ? WHERE id = ?', [$name, $email, $admin['id']]);
        if ($password !== '') {
            db_run('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
            session_regenerate_id(true);
        }
        flash('success', $password !== '' ? 'Your details and password were updated.' : 'Your details were updated.');
        redirect(admin_url('account'));
    }
    $admin = array_merge($admin, ['name' => $name, 'email' => $email]);
}

admin_view('account', ['title' => 'My account', 'errors' => $errors, 'me' => $admin]);
