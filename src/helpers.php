<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Absolute URL for a path inside the site, e.g. url('shop/tortes'). */
function url(string $path = ''): string
{
    return rtrim((string) config('app_url', ''), '/') . '/' . ltrim($path, '/');
}

/** Asset URL with a version stamp so browsers pick up changes after a deploy. */
/** Asset address without the ?v= cache-busting suffix (for sitemaps and structured data). */
function asset_url_plain(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? filemtime($file) : 1;
    return url('assets/' . ltrim($path, '/')) . '?v=' . $version;
}

function money(float|string|null $amount): string
{
    return '$' . number_format((float) $amount, 2);
}

function redirect(string $to, int $status = 303): never
{
    header('Location: ' . (preg_match('#^https?://#', $to) ? $to : url($to)), true, $status);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = rtrim((string) parse_url((string) config('app_url', ''), PHP_URL_PATH), '/');
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base)) ?: '/';
    }
    return '/' . trim($path, '/');
}

function is_active_path(string $path): bool
{
    $current = current_path();
    $path = '/' . trim($path, '/');
    return $path === '/' ? $current === '/' : str_starts_with($current, $path);
}

// ---------------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/** Sent by our own scripts (fetch with the X-Requested-With: fetch header). */
function is_fetch_request(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        if (is_fetch_request()) {
            json_out(['ok' => false, 'error' => 'Your session has expired. Please refresh the page.'], 419);
        }
        http_response_code(419);
        exit('Your session has expired. Please go back, refresh the page and try again.');
    }
}

// ---------------------------------------------------------------------------
// Flash messages and old form input
// ---------------------------------------------------------------------------

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function remember_input(array $data): void
{
    $_SESSION['old_input'] = $data;
}

function old(string $key, string $default = ''): string
{
    $value = $_SESSION['old_input'][$key] ?? $default;
    return is_string($value) ? $value : $default;
}

function forget_input(): void
{
    unset($_SESSION['old_input']);
}

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

function valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Accepts Australian landline and mobile numbers with or without +61. */
function valid_au_phone(string $phone): bool
{
    $digits = preg_replace('/\D+/', '', $phone);
    if (str_starts_with($digits, '61')) {
        $digits = '0' . substr($digits, 2);
    }
    return (bool) preg_match('/^0[2-478]\d{8}$/', $digits);
}

function str_limit(string $value, int $length): string
{
    return mb_strlen($value) > $length ? mb_substr($value, 0, $length) : $value;
}

function not_found(): never
{
    http_response_code(404);
    require BASE_PATH . '/public/404.php';
    exit;
}

function render(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require BASE_PATH . '/templates/' . $template . '.php';
}

/** Field errors stored by the previous POST, shown once. */
function take_form_errors(): array
{
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['form_errors']);
    return $errors;
}

function fail_form(array $errors, array $input, string $to): never
{
    remember_input($input);
    $_SESSION['form_errors'] = $errors;
    redirect($to);
}

function field_error(array $errors, string $field): string
{
    return isset($errors[$field]) ? '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field]) . '</p>' : '';
}

function invalid_attr(array $errors, string $field): string
{
    return isset($errors[$field]) ? 'aria-invalid="true" aria-describedby="' . e($field) . '-error"' : '';
}

// ---------------------------------------------------------------------------
// Spam protection for public forms: a hidden field people never fill, a minimum time
// to fill the form, and a limit on how often one visitor can send it.
// ---------------------------------------------------------------------------

function spam_trap_fields(): string
{
    $issued = (string) time();
    return '<div class="hp-field" aria-hidden="true"><label for="website">Website</label>'
        . '<input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>'
        . '<input type="hidden" name="_ts" value="' . $issued . '.' . hash_hmac('sha256', $issued, csrf_token()) . '">';
}

/**
 * True when the submission looks automated. $limit sends per $minutes are allowed per visitor.
 */
function is_spam_submission(string $form, int $limit = 5, int $minutes = 10): bool
{
    if (input('website') !== '') {
        return true;
    }
    [$issued, $signature] = array_pad(explode('.', input('_ts'), 2), 2, '');
    if (!hash_equals(hash_hmac('sha256', $issued, csrf_token()), $signature) || time() - (int) $issued < 3) {
        return true;
    }
    $recent = array_filter($_SESSION['form_sends'][$form] ?? [], static fn ($t) => $t > time() - $minutes * 60);
    if (count($recent) >= $limit) {
        return true;
    }
    $recent[] = time();
    $_SESSION['form_sends'][$form] = array_values($recent);
    return false;
}
