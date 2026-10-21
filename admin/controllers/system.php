<?php
$admin = require_admin('owner');

// Download a backup (owner only, like the rest of this page).
if (isset($_GET['download'])) {
    $file = backup_path((string) $_GET['download']);
    if (!$file) {
        http_response_code(404);
        exit('Backup not found');
    }
    header('Content-Type: application/gzip');
    header('Content-Length: ' . filesize($file));
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    readfile($file);
    exit;
}

if (is_post() && input('action') === 'backup') {
    try {
        flash('success', 'Backup saved: ' . backup_database() . '. Download it below to keep a copy off the server.');
    } catch (Throwable $e) {
        flash('error', 'The backup could not be made: ' . $e->getMessage());
    }
    redirect(admin_url('system'));
}

if (is_post() && input('action') === 'migrate') {
    try {
        $ran = run_migrations();
        flash('success', $ran ? 'Database updated: ' . implode(', ', $ran) . '.' : 'The database was already up to date.');
    } catch (RuntimeException $e) {
        flash('error', 'Update stopped. ' . $e->getMessage());
    }
    redirect(admin_url('system'));
}

$writable = static fn (string $path): bool => is_dir($path) && is_writable($path);
$appUrl = (string) config('app_url');
$checks = [
    'Server' => [
        ['PHP version ' . PHP_VERSION, version_compare(PHP_VERSION, '8.1.0', '>='), 'Version 8.1 or newer is needed. Change it in cPanel, Select PHP Version.'],
        ['PDO MySQL extension', extension_loaded('pdo_mysql'), 'Turn on pdo_mysql in cPanel, Select PHP Version, Extensions.'],
        ['GD with WebP (photo uploads)', function_exists('imagewebp'), 'Turn on the gd extension so photos can be resized.'],
        ['cURL (Stripe payments)', function_exists('curl_init'), 'Turn on the curl extension for card payments.'],
        ['Fileinfo (upload checks)', class_exists('finfo'), 'Turn on the fileinfo extension.'],
        ['mbstring', extension_loaded('mbstring'), 'Turn on the mbstring extension.'],
    ],
    'Folders' => [
        ['public/uploads is writable', $writable(BASE_PATH . '/public/uploads'), 'Set the folder permission to 755 (or 775) in File Manager.'],
        ['storage is writable (keeps the key for saved passwords)', $writable(STORAGE_PATH), 'Set the storage folder permission to 755 (or 775).'],
        ['storage/uploads is writable', $writable(STORAGE_PATH . '/uploads'), 'Set the folder permission to 755 (or 775) in File Manager.'],
        ['storage/logs is writable', $writable(STORAGE_PATH . '/logs') || (!is_dir(STORAGE_PATH . '/logs') && $writable(STORAGE_PATH)), 'Create storage/logs and make it writable.'],
        ['Backup made in the last 2 days', ($last = list_backups()[0]['time'] ?? 0) > time() - 2 * 86400, 'Backups are made every night by the scheduled job. Press "Back up now" below and check the cron job.'],
    ],
    'Configuration' => [
        ['Website address uses https', str_starts_with($appUrl, 'https://'), 'Set app_url in config/config.php to https://your-domain once SSL is active.'],
        ['Debug mode is off', !config('debug'), 'Set debug to false in config/config.php on the live site.'],
        ['Emails are really sent', mail_config('driver', 'mail') !== 'log', 'Emails are only saved to storage/logs/mail. Choose SMTP in Settings, Emails on the live site.'],
        ['Scheduled job (cron) is running', setting('cron_last_run') !== '' && strtotime(setting('cron_last_run')) > time() - 3600, 'Add the cron job from DEPLOY.md in cPanel > Cron Jobs so the morning email is sent.'],
        ['Notification email is set', setting('order_notification_email') !== '', 'Add an email in Settings, Emails.'],
        ['At least one payment method is live', available_payment_methods() !== [], 'Switch on and fill in a method in Settings, Payments.'],
    ],
];
$pending = pending_migrations();
$mailDir = STORAGE_PATH . '/logs/mail';
$recentMail = is_dir($mailDir) ? array_slice(array_reverse(glob($mailDir . '/*.html') ?: []), 0, 5) : [];
$backups = list_backups();
admin_view('system', compact('checks', 'pending', 'recentMail', 'appUrl', 'backups') + ['title' => 'System check']);
