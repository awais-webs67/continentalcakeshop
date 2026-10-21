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
// Errors are always written to storage/logs, which the browser can never open.
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-errors.log');
if (!config('debug') && PHP_SAPI !== 'cli') {
    set_exception_handler(static function (Throwable $e): void {
        error_log((string) $e);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Something went wrong</title>'
            . '<div style="max-width:480px;margin:15vh auto;padding:24px;font-family:system-ui,sans-serif;text-align:center;color:#33363B">'
            . '<h1 style="color:#1C8F77;font-size:24px">Sorry, something went wrong</h1>'
            . '<p>Please try again in a minute. If it keeps happening, call or message the shop and we will help with your order.</p>'
            . '<p><a href="/" style="color:#1C8F77;font-weight:600">Back to the homepage</a></p></div>';
    });
}

// On the live site (app_url starts with https://) every page is sent to the one official
// address: HTTPS and the same www or non-www host as app_url. This keeps logins working
// (the session cookie is HTTPS only) and gives Google a single version of each page.
if (PHP_SAPI !== 'cli' && str_starts_with((string) config('app_url', ''), 'https://')) {
    $siteHost = strtolower((string) parse_url(config('app_url'), PHP_URL_HOST));
    $requestHost = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    if ($requestHost !== '' && (!$isHttps || $requestHost !== $siteHost) && in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
        header('Location: https://' . $siteHost . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/catalog.php';
require __DIR__ . '/cart.php';
require __DIR__ . '/booking.php';
require __DIR__ . '/uploads.php';
require __DIR__ . '/payments.php';
require __DIR__ . '/coupons.php';
require __DIR__ . '/orders.php';
require __DIR__ . '/mail.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/messages.php';
require __DIR__ . '/daily.php';
require __DIR__ . '/content.php';
require __DIR__ . '/seo.php';
require __DIR__ . '/icons.php';
require __DIR__ . '/migrations.php';

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
