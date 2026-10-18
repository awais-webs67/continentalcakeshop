<?php
declare(strict_types=1);

// Single entry point for every page, API endpoint and (later) the admin panel.

define('BASE_PATH', dirname(__DIR__));
define('STORAGE_PATH', BASE_PATH . '/storage');

$configFile = BASE_PATH . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Missing config/config.php. Copy config/config.example.php and fill in your database details.');
}
$GLOBALS['config'] = require $configFile;

date_default_timezone_set(config('timezone', 'Australia/Sydney'));

if (config('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/catalog.php';
require __DIR__ . '/cart.php';
require __DIR__ . '/booking.php';
require __DIR__ . '/uploads.php';
require __DIR__ . '/payments.php';
require __DIR__ . '/orders.php';
require __DIR__ . '/mail.php';
require __DIR__ . '/seo.php';
require __DIR__ . '/icons.php';

if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    session_name('ccs_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => str_starts_with(config('app_url', ''), 'https://'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}
