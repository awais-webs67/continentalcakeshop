<?php
declare(strict_types=1);

// Shared helpers for the admin panel. Loaded by public/admin/index.php after src/bootstrap.php.

const ADMIN_ROLES = ['owner' => 'Owner', 'staff' => 'Staff'];
const ADMIN_PER_PAGE = 20;
// Parts of the admin panel, and the pages in each, so the owner can choose what staff may open.
const ADMIN_SECTIONS = [
    'orders'    => ['Orders, pickups and deliveries', ['orders', 'orders/view', 'orders/new', 'orders/print', 'pickups']],
    'custom'    => ['Custom cake requests', ['custom-requests', 'custom-requests/view']],
    'messages'  => ['Customer messages', ['messages', 'messages/view']],
    'customers' => ['Customers', ['customers', 'customers/view']],
    'reports'   => ['Sales reports and exports', ['reports', 'reports/export']],
    'coupons'   => ['Coupons', ['coupons']],
    'products'  => ['Products, categories and extras', ['products', 'products/edit', 'products/import', 'categories', 'categories/edit', 'addons']],
    'gallery'   => ['Gallery and reviews', ['gallery', 'reviews']],
    'content'   => ['Homepage, popup, pages, FAQs and SEO', ['homepage', 'popup', 'pages', 'pages/edit', 'faqs', 'upload-image', 'seo']],
    'settings'  => ['Settings', ['settings']],
];
const OWNER_ONLY_ROUTES = ['admins', 'system'];

const CUSTOM_STATUSES = ['new' => 'New', 'quoted' => 'Quoted', 'confirmed' => 'Confirmed', 'closed' => 'Closed'];

// ---------------------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------------------

function current_admin(): ?array
{
    static $admin = false;
    if ($admin === false) {
        $id = (int) ($_SESSION['admin_id'] ?? 0);
        $admin = $id ? db_one('SELECT id, name, email, role, permissions FROM admins WHERE id = ? AND is_active = 1', [$id]) : null;
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

/** Sections a staff account may open. Owners can open everything. */
function admin_permissions(array $admin): array
{
    if ($admin['role'] === 'owner' || $admin['permissions'] === null) {
        return array_keys(ADMIN_SECTIONS);
    }
    $list = json_decode((string) $admin['permissions'], true);
    return is_array($list) ? array_values(array_intersect($list, array_keys(ADMIN_SECTIONS))) : [];
}

function admin_can(string $section, ?array $admin = null): bool
{
    $admin ??= current_admin();
    return $admin !== null && in_array($section, admin_permissions($admin), true);
}

/** The section a route belongs to, or null for pages every admin may open. */
function admin_route_section(string $route): ?string
{
    foreach (ADMIN_SECTIONS as $key => [, $routes]) {
        if (in_array($route, $routes, true)) {
            return $key;
        }
    }
    return null;
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
    return store_image_file($file['tmp_name'], $folder, $width, $height, $name);
}

/**
 * Crops a photo on disk to the target shape, scales it down and saves it as WebP in public/uploads/{folder}.
 * Used for uploads and for photos downloaded during a product import.
 *
 * @return array{0:?string,1:?string}
 */
function store_image_file(string $path, string $folder, int $width, int $height, string $name = ''): array
{
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: '';
    $source = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png'  => @imagecreatefrompng($path),
        'image/webp' => @imagecreatefromwebp($path),
        default      => false,
    };
    if (!$source) {
        return [null, 'Please use a JPG, PNG or WebP photo.'];
    }
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);
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

/**
 * Saves an uploaded photo for page content without cropping, scaled down to $maxWidth.
 * Returns [path relative to public/uploads, null] or [null, error].
 */
function save_content_image(string $field, int $maxWidth = 1600): array
{
    $file = $_FILES[$field] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return [null, 'The picture did not upload. Please try a smaller file (under 10 MB).'];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    $source = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
        'image/png'  => @imagecreatefrompng($file['tmp_name']),
        'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        default      => false,
    };
    if (!$source) {
        return [null, 'Please upload a JPG, PNG or WebP picture.'];
    }
    $w = imagesx($source);
    $h = imagesy($source);
    $tw = min($w, $maxWidth);
    $th = (int) round($h * $tw / $w);
    $canvas = imagecreatetruecolor($tw, $th);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $tw, $th, $w, $h);
    $dir = BASE_PATH . '/public/uploads/content';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return [null, 'The uploads folder is not writable.'];
    }
    $name = slugify(pathinfo((string) $file['name'], PATHINFO_FILENAME)) . '-' . bin2hex(random_bytes(3)) . '.webp';
    if (!imagewebp($canvas, $dir . '/' . $name, 82)) {
        return [null, 'The picture could not be saved.'];
    }
    return ['content/' . $name, null];
}

/**
 * Deletes a file in public/uploads that the panel created, but only when nothing uses it any more.
 * Call it after the row that used the photo has been changed or deleted.
 */
function delete_public_image(?string $path): void
{
    if (!$path || str_contains($path, '..')) {
        return;
    }
    $inUse = db_value('SELECT 1 FROM products WHERE image = ? UNION SELECT 1 FROM categories WHERE image = ? UNION SELECT 1 FROM gallery_images WHERE image = ? UNION SELECT 1 FROM product_images WHERE image = ?', [$path, $path, $path, $path])
        || in_array($path, [setting('hero_image'), setting('popup_image')], true);
    if ($inUse) {
        return;
    }
    $full = BASE_PATH . '/public/uploads/' . $path;
    if (is_file($full)) {
        @unlink($full);
    }
}

// ---------------------------------------------------------------------------
// Order lines typed by staff (phone orders and order changes)
// ---------------------------------------------------------------------------

/**
 * Everything staff can put on an order, for the item picker: every size of every cake
 * (hidden cakes too, marked), then the extras. Keyed "size:ID" or "addon:ID".
 */
function admin_size_choices(): array
{
    $choices = [];
    foreach (db_all(
        'SELECT s.id, s.label, s.price, p.name, p.is_active, c.name AS category
         FROM product_sizes s JOIN products p ON p.id = s.product_id JOIN categories c ON c.id = p.category_id
         WHERE s.is_active = 1 ORDER BY c.sort_order, p.sort_order, p.name, s.sort_order, s.price'
    ) as $s) {
        $choices[$s['category']]['size:' . $s['id']] = [
            'label' => $s['name'] . ', ' . $s['label'] . ' (' . money($s['price']) . ')' . ($s['is_active'] ? '' : ' [hidden]'),
            'price' => (float) $s['price'],
        ];
    }
    foreach (db_all('SELECT id, name, price, is_active FROM addons ORDER BY sort_order, name') as $a) {
        $choices['Extras']['addon:' . $a['id']] = ['label' => $a['name'] . ' (' . money($a['price']) . ')' . ($a['is_active'] ? '' : ' [off]'), 'price' => (float) $a['price']];
    }
    return $choices;
}

/**
 * Turns the "add a line" rows of a staff form into order lines.
 * Each row: item ("size:ID", "addon:ID" or "custom"), qty, price (optional override),
 * name (custom lines), options (free text, e.g. Eggless) and message.
 */
function admin_order_lines_from_post(mixed $rows): array
{
    $lines = [];
    foreach (is_array($rows) ? $rows : [] as $row) {
        $item = (string) ($row['item'] ?? '');
        $qty = min(99, max(0, (int) ($row['qty'] ?? 1)));
        if ($item === '' || $qty === 0) {
            continue;
        }
        $priceText = trim(str_replace(['$', ','], '', (string) ($row['price'] ?? '')));
        $override = $priceText !== '' && is_numeric($priceText) ? max(0, round((float) $priceText, 2)) : null;
        $line = ['product_id' => null, 'size_id' => null, 'addon_id' => null, 'options' => str_limit(trim((string) ($row['options'] ?? '')), 255),
                 'message' => str_limit(trim((string) ($row['message'] ?? '')), 60), 'qty' => $qty];
        if (str_starts_with($item, 'size:')) {
            $size = db_one('SELECT s.id, s.label, s.price, p.id AS product_id, p.name FROM product_sizes s JOIN products p ON p.id = s.product_id WHERE s.id = ?', [(int) substr($item, 5)]);
            if (!$size) {
                continue;
            }
            $line += ['name' => $size['name'], 'size_label' => $size['label'], 'price' => $override ?? (float) $size['price']];
            $line['product_id'] = (int) $size['product_id'];
            $line['size_id'] = (int) $size['id'];
        } elseif (str_starts_with($item, 'addon:')) {
            $addon = db_one('SELECT id, name, price FROM addons WHERE id = ?', [(int) substr($item, 6)]);
            if (!$addon) {
                continue;
            }
            $line += ['name' => $addon['name'], 'size_label' => 'Extra', 'price' => $override ?? (float) $addon['price']];
            $line['addon_id'] = (int) $addon['id'];
        } elseif ($item === 'custom') {
            $name = str_limit(trim((string) ($row['name'] ?? '')), 160);
            if ($name === '' || $override === null) {
                continue;
            }
            $line += ['name' => $name, 'size_label' => str_limit(trim((string) ($row['size'] ?? '')), 60), 'price' => $override];
        } else {
            continue;
        }
        $line['line_total'] = round($line['price'] * $qty, 2);
        $lines[] = $line;
    }
    return $lines;
}

/**
 * Extra photos in the product editor: uploads (several at once), alt text, order,
 * removing photos and making one the main photo. Returns a note about any failed upload.
 */
function save_product_photos(int $productId, string $name): string
{
    $notes = [];
    // Changes to the photos already there.
    foreach ((array) ($_POST['photos'] ?? []) as $photoId => $row) {
        $photo = db_one('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [(int) $photoId, $productId]);
        if (!$photo || !is_array($row)) {
            continue;
        }
        if (!empty($row['remove'])) {
            db_run('DELETE FROM product_images WHERE id = ?', [$photo['id']]);
            delete_public_image($photo['image']);
            continue;
        }
        db_run('UPDATE product_images SET alt = ?, sort_order = ? WHERE id = ?', [str_limit(trim((string) ($row['alt'] ?? '')), 160) ?: null, (int) ($row['sort'] ?? 0), $photo['id']]);
    }
    // New photos, several at once.
    $files = $_FILES['gallery'] ?? null;
    if ($files && is_array($files['name'] ?? null)) {
        $next = (int) db_value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM product_images WHERE product_id = ?', [$productId]);
        foreach ($files['name'] as $i => $original) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($files['error'][$i] !== UPLOAD_ERR_OK || !is_uploaded_file($files['tmp_name'][$i]) || $files['size'][$i] > 10 * 1024 * 1024) {
                $notes[] = $original . ' did not upload (under 10 MB please)';
                continue;
            }
            [$path, $error] = store_image_file($files['tmp_name'][$i], 'products', 900, 900, $name);
            if ($error) {
                $notes[] = $original . ': ' . $error;
                continue;
            }
            db_run('INSERT INTO product_images (product_id, image, alt, sort_order) VALUES (?, ?, ?, ?)', [$productId, $path, null, $next++]);
        }
    }
    // One of the extra photos becomes the main photo; the old main photo moves to the extras.
    $makeMain = (int) ($_POST['make_main'] ?? 0);
    if ($makeMain && ($photo = db_one('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [$makeMain, $productId]))) {
        $product = db_one('SELECT image, image_alt FROM products WHERE id = ?', [$productId]);
        db_run('UPDATE products SET image = ?, image_alt = ? WHERE id = ?', [$photo['image'], $photo['alt'], $productId]);
        if ($product['image']) {
            db_run('UPDATE product_images SET image = ?, alt = ? WHERE id = ?', [$product['image'], $product['image_alt'], $photo['id']]);
        } else {
            db_run('DELETE FROM product_images WHERE id = ?', [$photo['id']]);
        }
    }
    return $notes ? 'Some photos were not added: ' . implode('; ', $notes) . '.' : '';
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
        'messages' => unread_thread_count(),
    ];
}
/**
 * What the live check sends to open admin pages: badge counts, a marker that changes whenever
 * an order, payment, message or custom request is added or changed, and the newest of each.
 */
function admin_live_state(array $admin): array
{
    $can = static fn (string $section): bool => admin_can($section, $admin);
    $badges = admin_badges();
    $counts = [
        'orders'   => $can('orders') ? $badges['orders'] + $badges['payments'] : 0,
        'messages' => $can('messages') ? $badges['messages'] : 0,
        'custom'   => $can('custom') ? $badges['custom'] : 0,
    ];
    $counts['alerts'] = array_sum($counts);
    $stamp = md5(json_encode([
        $counts,
        db_one('SELECT COUNT(*) AS n, MAX(id) AS id, MAX(updated_at) AS changed FROM orders'),
        db_value('SELECT MAX(id) FROM payments'),
        db_one('SELECT MAX(last_message_at) AS last, SUM(unread_admin) AS unread, SUM(status = \'open\') AS open FROM threads'),
        db_one('SELECT COUNT(*) AS n, MAX(id) AS id, SUM(status = \'new\') AS fresh FROM custom_requests'),
    ]));
    return [
        'counts'  => $counts,
        'stamp'   => $stamp,
        'order'   => $can('orders') ? db_one('SELECT id, order_number, customer_name, total, channel FROM orders ORDER BY id DESC LIMIT 1') : null,
        'message' => $can('messages') ? db_one("SELECT m.id, m.thread_id, t.customer_name, LEFT(m.body, 140) AS body FROM thread_messages m JOIN threads t ON t.id = m.thread_id WHERE m.sender = 'customer' ORDER BY m.id DESC LIMIT 1") : null,
        'request' => $can('custom') ? db_one('SELECT id, name, occasion FROM custom_requests ORDER BY id DESC LIMIT 1') : null,
    ];
}

require __DIR__ . '/reports.php';
require __DIR__ . '/product-import.php';
