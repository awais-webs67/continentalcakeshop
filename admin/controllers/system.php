<?php
$admin = require_admin('owner');

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
    ],
    'Configuration' => [
        ['Website address uses https', str_starts_with($appUrl, 'https://'), 'Set app_url in config/config.php to https://your-domain once SSL is active.'],
        ['Debug mode is off', !config('debug'), 'Set debug to false in config/config.php on the live site.'],
        ['Emails are really sent', mail_config('driver', 'mail') !== 'log', 'Emails are only saved to storage/logs/mail. Choose SMTP in Settings, Emails on the live site.'],
        ['Notification email is set', setting('order_notification_email') !== '', 'Add an email in Settings, Emails.'],
        ['At least one payment method is live', available_payment_methods() !== [], 'Switch on and fill in a method in Settings, Payments.'],
    ],
];
$pending = pending_migrations();
$mailDir = STORAGE_PATH . '/logs/mail';
$recentMail = is_dir($mailDir) ? array_slice(array_reverse(glob($mailDir . '/*.html') ?: []), 0, 5) : [];
admin_view('system', compact('checks', 'pending', 'recentMail', 'appUrl') + ['title' => 'System check']);
