<?php
if (is_post()) {
    unset($_SESSION['admin_id'], $_SESSION['admin_auth'], $_SESSION['csrf_token']);
    session_regenerate_id(true);
    flash('success', 'You have been logged out.');
}
redirect(admin_url('login'));
