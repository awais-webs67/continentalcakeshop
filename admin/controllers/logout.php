<?php
if (is_post()) {
    unset($_SESSION['admin_id']);
    session_regenerate_id(true);
    flash('success', 'You have been logged out.');
}
redirect(admin_url('login'));
