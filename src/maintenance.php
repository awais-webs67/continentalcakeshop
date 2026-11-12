<?php
declare(strict_types=1);

// ---------------------------------------------------------------------------
// Maintenance mode (Admin > Settings > Business)
// ---------------------------------------------------------------------------
// While it is on, customers see one page: the website is being updated, with each shop's phone
// and email so they can still order. It answers "503, back soon" so Google keeps the pages and
// their rankings. Staff who are logged in to the admin panel see the website as usual (with a
// reminder bar), the admin panel and Stripe's payment messages keep working.

function maintenance_on(): bool
{
    return setting('maintenance_mode') === '1';
}

/** True for someone logged in to the admin panel (an active account). */
function viewer_is_staff(): bool
{
    static $staff = null;
    if ($staff === null) {
        $id = (int) ($_SESSION['admin_id'] ?? 0);
        $staff = $id > 0 && (bool) db_value('SELECT 1 FROM admins WHERE id = ? AND is_active = 1', [$id]);
    }
    return $staff;
}

/** Shows the maintenance page and stops, unless this request may go through. */
function maintenance_gate(): void
{
    if (PHP_SAPI === 'cli' || !maintenance_on()) {
        return;
    }
    $base = rtrim((string) parse_url((string) config('app_url', ''), PHP_URL_PATH), '/');
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base)) ?: '/';
    }
    // The admin panel, Stripe's payment messages and robots.txt always work. Staff see the
    // website, unless they ask to see the page customers get (?preview_maintenance=1).
    if (preg_match('#^/(admin(/|$)|stripe-webhook|robots\.txt)#', $path)) {
        return;
    }
    if (viewer_is_staff() && !isset($_GET['preview_maintenance'])) {
        return;
    }
    http_response_code(503);
    header('Retry-After: 3600');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
    require BASE_PATH . '/templates/maintenance.php';
    exit;
}
