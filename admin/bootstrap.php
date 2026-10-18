<?php
declare(strict_types=1);

// Shared helpers for the admin panel. Loaded by public/admin/index.php after src/bootstrap.php.

const ADMIN_ROLES = ['owner' => 'Owner', 'staff' => 'Staff'];
const ADMIN_PER_PAGE = 20;
const CUSTOM_STATUSES = ['new' => 'New', 'quoted' => 'Quoted', 'confirmed' => 'Confirmed', 'closed' => 'Closed'];

// ---------------------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------------------

function current_admin(): ?array
{
    static $admin = false;
    if ($admin === false) {
        $id = (int) ($_SESSION['admin_id'] ?? 0);
        $admin = $id ? db_one('SELECT id, name, email, role FROM admins WHERE id = ? AND is_active = 1', [$id]) : null;
        if ($id && !$admin) {
            unset($_SESSION['admin_id']);
        }
    }
    return $admin;
}

function require_admin(?string $role = null): array
{
    $admin = current_admin();
    if (!$admin) {
        $parts = parse_url((string) config('app_url'));
        $origin = ($parts['scheme'] ?? 'http') . '://' . ($parts['host'] ?? 'localhost') . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $_SESSION['admin_after_login'] = $origin . ($_SERVER['REQUEST_URI'] ?? '');
        redirect(admin_url('login'));
    }
    if ($role === 'owner' && $admin['role'] !== 'owner') {
        flash('error', 'Only the owner account can open that page.');
        redirect(admin_url());
    }
    return $admin;
}

function admin_count(): int
{
    return (int) db_value('SELECT COUNT(*) FROM admins');
}

function admin_attempt_login(string $email, string $password): array
{
    $admin = db_one('SELECT * FROM admins WHERE email = ? AND is_active = 1', [strtolower(trim($email))]);
    $generic = 'That email and password do not match.';
    if (!$admin) {
        password_verify($password, password_hash('timing', PASSWORD_DEFAULT));
        return [null, $generic];
    }
    if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
        return [null, 'Too many attempts. Please wait ' . LOGIN_LOCK_MINUTES . ' minutes.'];
    }
    if (!password_verify($password, $admin['password_hash'])) {
        $failed = (int) $admin['failed_logins'] + 1;
        $lock = $failed >= LOGIN_MAX_ATTEMPTS ? date('Y-m-d H:i:s', time() + LOGIN_LOCK_MINUTES * 60) : null;
        db_run('UPDATE admins SET failed_logins = ?, locked_until = ? WHERE id = ?', [$lock ? 0 : $failed, $lock, $admin['id']]);
        return [null, $generic];
    }
    return [$admin, null];
}

function admin_login(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $id;
    db_run('UPDATE admins SET failed_logins = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?', [$id]);
}

// ---------------------------------------------------------------------------
// URLs, views and responses
// ---------------------------------------------------------------------------

function admin_url(string $path = '', array $query = []): string
{
    $url = url('admin/' . ltrim($path, '/'));
    return $query ? $url . '?' . http_build_query($query) : $url;
}

/** Renders admin/views/{view}.php inside the admin layout. */
function admin_view(string $view, array $data = []): never
{
    $data['admin'] ??= current_admin();
    extract($data, EXTR_SKIP);
    ob_start();
    require BASE_PATH . '/admin/views/' . $view . '.php';
    $content = (string) ob_get_clean();
    require BASE_PATH . '/admin/views/' . ($data['layout'] ?? 'layout') . '.php';
    exit;
}

function admin_back(string $fallback): never
{
    $back = $_POST['_back'] ?? '';
    redirect(is_string($back) && str_starts_with($back, admin_url()) ? $back : admin_url($fallback));
}

function post_int(string $key): int
{
    return (int) ($_POST[$key] ?? 0);
}

function query_int(string $key, int $default = 0): int
{
    return isset($_GET[$key]) ? (int) $_GET[$key] : $default;
}

function checked(bool $value): string
{
    return $value ? 'checked' : '';
}

function selected(bool $value): string
{
    return $value ? 'selected' : '';
}

function status_badge(string $status, string $label): string
{
    return '<span class="badge badge--' . e($status) . '">' . e($label) . '</span>';
}

function time_ago(string $datetime): string
{
    $seconds = time() - strtotime($datetime);
    return match (true) {
        $seconds < 60     => 'just now',
        $seconds < 3600   => floor($seconds / 60) . ' min ago',
        $seconds < 86400  => floor($seconds / 3600) . ' h ago',
        $seconds < 604800 => floor($seconds / 86400) . ' d ago',
        default           => date('j M Y', strtotime($datetime)),
    };
}

/**
 * Runs a SELECT with LIMIT/OFFSET for the current ?page and returns rows plus paging info.
 * $sql must not contain LIMIT; $countSql returns the total number of rows.
 */
function paginate(string $sql, string $countSql, array $params = [], int $perPage = ADMIN_PER_PAGE): array
{
    $total = (int) db_value($countSql, $params);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($pages, max(1, query_int('page', 1)));
    $rows = db_all($sql . ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage), $params);
    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
}

function page_link(int $page): string
{
    $query = $_GET;
    unset($query['route']);
    $query['page'] = $page;
    return '?' . http_build_query($query);
}

function slugify(string $text): string
{
    $slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    return $slug !== '' ? $slug : 'item';
}

/** Makes a slug unique within a table by adding -2, -3... */
function unique_slug(string $table, string $slug, int $ignoreId = 0): string
{
    $base = $slug;
    for ($i = 2; db_value("SELECT 1 FROM {$table} WHERE slug = ? AND id <> ?", [$slug, $ignoreId]); $i++) {
        $slug = $base . '-' . $i;
    }
    return $slug;
}

// ---------------------------------------------------------------------------
// Image uploads (products, categories, gallery)
// ---------------------------------------------------------------------------

/**
 * Validates an uploaded photo, resizes it and saves it as WebP inside public/uploads/{folder}.
 * Returns [path relative to public/uploads, null] or [null, error].
 *
 * @return array{0:?string,1:?string}
 */
function save_public_image(string $field, string $folder, int $width, int $height, string $name = ''): array
{
    $file = $_FILES[$field] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return [null, 'The photo did not upload. Please try a smaller file (under 10 MB).'];
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        return [null, 'Please upload a photo under 10 MB.'];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    $source = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
        'image/png'  => @imagecreatefrompng($file['tmp_name']),
        'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        default      => false,
    };
    if (!$source) {
        return [null, 'Please upload a JPG, PNG or WebP photo.'];
    }
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $orientation = (int) (@exif_read_data($file['tmp_name'])['Orientation'] ?? 1);
        $source = match ($orientation) {
            3 => imagerotate($source, 180, 0),
            6 => imagerotate($source, -90, 0),
            8 => imagerotate($source, 90, 0),
            default => $source,
        };
    }

    // Centre crop to the target shape, then scale down.
    $sw = imagesx($source);
    $sh = imagesy($source);
    $ratio = $width / $height;
    if ($sw / $sh > $ratio) {
        $cw = (int) round($sh * $ratio);
        $ch = $sh;
    } else {
        $cw = $sw;
        $ch = (int) round($sw / $ratio);
    }
    $cx = (int) (($sw - $cw) / 2);
    $cy = (int) (($sh - $ch) / 2);
    $tw = min($width, $cw);
    $th = (int) round($tw / $ratio);
    $canvas = imagecreatetruecolor($tw, $th);
    imagecopyresampled($canvas, $source, 0, 0, $cx, $cy, $tw, $th, $cw, $ch);

    $dir = BASE_PATH . '/public/uploads/' . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return [null, 'The uploads folder is not writable.'];
    }
    $filename = ($name !== '' ? slugify($name) . '-' : '') . bin2hex(random_bytes(4)) . '.webp';
    if (!imagewebp($canvas, $dir . '/' . $filename, 82)) {
        return [null, 'The photo could not be saved.'];
    }
    return [$folder . '/' . $filename, null];
}

/** Deletes a file in public/uploads that the panel created. */
function delete_public_image(?string $path): void
{
    if (!$path || str_contains($path, '..')) {
        return;
    }
    $full = BASE_PATH . '/public/uploads/' . $path;
    if (is_file($full)) {
        @unlink($full);
    }
}

// ---------------------------------------------------------------------------
// Dashboard numbers
// ---------------------------------------------------------------------------

function admin_badges(): array
{
    static $badges = null;
    return $badges ??= [
        'orders'   => (int) db_value("SELECT COUNT(*) FROM orders WHERE order_status = 'new' OR (fulfilment = 'delivery' AND pickup_time IS NULL AND order_status NOT IN ('cancelled', 'completed'))"),
        'payments' => (int) db_value("SELECT COUNT(*) FROM orders WHERE payment_status = 'awaiting_verification'"),
        'custom'   => (int) db_value("SELECT COUNT(*) FROM custom_requests WHERE status = 'new'"),
        'messages' => (int) db_value('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0'),
    ];
}

// ---------------------------------------------------------------------------
// Content
// ---------------------------------------------------------------------------

/** Keeps simple formatting tags in page content and removes scripts, styles and event attributes. */
function clean_page_html(string $html): string
{
    $html = (string) preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html);
    $html = strip_tags($html, '<h2><h3><h4><p><br><strong><b><em><i><u><ul><ol><li><a><blockquote><hr><table><thead><tbody><tr><th><td>');
    $html = (string) preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = (string) preg_replace('/\s+(href)\s*=\s*(["\']?)\s*(javascript|data|vbscript):[^"\'>\s]*\2/i', ' $1="#"', $html);
    $html = (string) preg_replace('/\s+style\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html);
    return trim($html);
}
