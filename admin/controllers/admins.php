<?php
$admin = require_admin('owner');

/** Sections ticked in the form, stored as JSON. */
$postedPermissions = static fn (): string => json_encode(array_values(array_intersect((array) ($_POST['permissions'] ?? []), array_keys(ADMIN_SECTIONS))));

if (is_post()) {
    $action = input('action');
    $id = post_int('id');
    $target = $id ? db_one('SELECT * FROM admins WHERE id = ?', [$id]) : null;

    if ($action === 'create') {
        $name = str_limit(trim(input('name')), 120);
        $email = strtolower(str_limit(trim(input('email')), 190));
        $password = (string) ($_POST['password'] ?? '');
        $role = isset(ADMIN_ROLES[input('role')]) ? input('role') : 'staff';
        if ($name === '' || !valid_email($email)) {
            flash('error', 'Please enter a name and a valid email.');
        } elseif (mb_strlen($password) < 10) {
            flash('error', 'Use at least 10 characters for the password.');
        } elseif (db_value('SELECT 1 FROM admins WHERE email = ?', [$email])) {
            flash('error', 'There is already an account with that email.');
        } else {
            db_run('INSERT INTO admins (name, email, password_hash, role, permissions) VALUES (?, ?, ?, ?, ?)', [$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $role === 'staff' ? $postedPermissions() : null]);
            flash('success', $name . ' can now log in at ' . admin_url('login') . '. Share the password with them in person.');
        }
    } elseif ($target && (int) $target['id'] === (int) $admin['id']) {
        flash('error', 'Use "My account" to change your own login.');
    } elseif ($target && $action === 'toggle') {
        if ($target['is_active'] && $target['role'] === 'owner' && (int) db_value("SELECT COUNT(*) FROM admins WHERE role = 'owner' AND is_active = 1") <= 1) {
            flash('error', 'Keep at least one active owner account.');
        } else {
            db_run('UPDATE admins SET is_active = 1 - is_active WHERE id = ?', [$target['id']]);
            flash('success', $target['name'] . ($target['is_active'] ? ' can no longer log in.' : ' can log in again.'));
        }
    } elseif ($target && $action === 'role' && isset(ADMIN_ROLES[input('role')])) {
        if ($target['role'] === 'owner' && input('role') !== 'owner' && (int) db_value("SELECT COUNT(*) FROM admins WHERE role = 'owner' AND is_active = 1") <= 1) {
            flash('error', 'Keep at least one active owner account.');
        } else {
            db_run('UPDATE admins SET role = ? WHERE id = ?', [input('role'), $target['id']]);
            flash('success', $target['name'] . ' is now ' . strtolower(ADMIN_ROLES[input('role')]) . '.');
        }
    } elseif ($target && $action === 'permissions') {
        db_run('UPDATE admins SET permissions = ? WHERE id = ?', [$postedPermissions(), $target['id']]);
        flash('success', 'Access for ' . $target['name'] . ' saved.');
    } elseif ($target && $action === 'password') {
        $password = (string) ($_POST['password'] ?? '');
        if (mb_strlen($password) < 10) {
            flash('error', 'Use at least 10 characters for the password.');
        } else {
            db_run('UPDATE admins SET password_hash = ?, failed_logins = 0, locked_until = NULL WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $target['id']]);
            flash('success', 'Password changed for ' . $target['name'] . '.');
        }
    }
    redirect(admin_url('admins'));
}

$admins = db_all('SELECT id, name, email, role, permissions, is_active, last_login_at, created_at FROM admins ORDER BY role, name');
admin_view('admins', compact('admins') + ['title' => 'Staff accounts']);
