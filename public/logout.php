<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

if (is_post()) {
    verify_csrf();
    logout_user();
    flash('success', 'You have been logged out.');
}
redirect('');
