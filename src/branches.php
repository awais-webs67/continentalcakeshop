<?php
declare(strict_types=1);

// ---------------------------------------------------------------------------
// Branches: one website, more than one shop (Admin > Branches)
// ---------------------------------------------------------------------------
// The menu, prices, opening hours, coupons, payments and settings are shared. What belongs to a
// branch: its address, phone, the email its orders go to, and its orders, custom cake requests
// and conversations (branch_id on orders, custom_requests and threads).
//
// "Branch context": while a page or email is about one order, use_branch() makes the shop's
// address, phone and reply email (store_street, store_phone, store_email...) read as that
// branch's, so every existing template shows the right shop without being rewritten.

const BRANCH_SETTING_FIELDS = [
    'store_street'   => 'street',
    'store_suburb'   => 'suburb',
    'store_state'    => 'state',
    'store_postcode' => 'postcode',
    'store_phone'    => 'phone',
    'store_email'    => 'email',
    'store_latitude' => 'latitude',
    'store_longitude' => 'longitude',
    'google_reviews_url' => 'reviews_url',
];

/** Every branch, main shop first. */
function branches(bool $refresh = false): array
{
    static $cache = null;
    if ($cache === null || $refresh) {
        try {
            $cache = db_all('SELECT * FROM branches ORDER BY is_main DESC, sort_order, name');
        } catch (PDOException) {
            $cache = []; // before the database update has run
        }
        // The main shop's details live in Settings > Shop details; its row only adds the branch extras.
        foreach ($cache as $i => $branch) {
            if ((int) $branch['is_main'] === 1) {
                foreach (['street', 'suburb', 'state', 'postcode', 'phone', 'latitude', 'longitude'] as $field) {
                    $cache[$i][$field] = setting_raw('store_' . $field, (string) $branch[$field]);
                }
                if (trim((string) $branch['reviews_url']) === '') {
                    $cache[$i]['reviews_url'] = setting_raw('google_reviews_url');
                }
            }
        }
    }
    return $cache;
}

/** Branches customers can choose. Never empty: falls back to the main shop. */
function active_branches(): array
{
    $list = array_values(array_filter(branches(), static fn ($b) => (int) $b['is_active'] === 1));
    return $list ?: array_slice(branches(), 0, 1);
}

/** True when customers choose between two or more shops. */
function multi_branch(): bool
{
    return count(active_branches()) > 1;
}

function get_branch(?int $id): ?array
{
    foreach (branches() as $branch) {
        if ((int) $branch['id'] === $id) {
            return $branch;
        }
    }
    return null;
}

function main_branch(): ?array
{
    return branches()[0] ?? null;
}

function main_branch_id(): int
{
    return (int) (main_branch()['id'] ?? 1);
}

function branch_by_slug(string $slug): ?array
{
    foreach (branches() as $branch) {
        if ($branch['slug'] === $slug) {
            return $branch;
        }
    }
    return null;
}

/** An active branch id from user input, or null. */
function valid_branch_id(mixed $id): ?int
{
    $id = (int) $id;
    foreach (active_branches() as $branch) {
        if ((int) $branch['id'] === $id) {
            return $id;
        }
    }
    return null;
}

/** "Shop 4, 38 Reed St N, Greenway ACT 2900" */
function branch_address_line(array $branch): string
{
    $street = trim((string) $branch['street']);
    $place = trim($branch['suburb'] . ' ' . $branch['state'] . ' ' . $branch['postcode']);
    return $street !== '' ? $street . ', ' . $place : $place;
}

function branch_maps_url(array $branch): string
{
    if (safe_web_url((string) $branch['maps_url'])) {
        return $branch['maps_url'];
    }
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(setting_raw('store_name') . ', ' . branch_address_line($branch));
}

/** The branch phone, or the main shop's when the branch has none yet. */
function branch_phone(array $branch): string
{
    return trim((string) $branch['phone']) !== '' ? $branch['phone'] : setting_raw('store_phone');
}

/** "+61262939578" for tel: links. */
function phone_e164(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    return $digits !== '' ? '+61' . ltrim(str_starts_with($digits, '61') ? substr($digits, 2) : $digits, '0') : '';
}

/** Where the shop's copy of a new order, custom request or message goes for this branch. */
function branch_notify_email(?int $branchId): string
{
    $branch = $branchId ? get_branch($branchId) : null;
    $email = trim((string) ($branch['email'] ?? ''));
    return valid_email($email) ? $email : setting_raw('order_notification_email');
}

/** Name shown next to an order or request in the admin panel and emails, e.g. "Greenway". */
function branch_name(?int $id): string
{
    return (string) (get_branch($id)['name'] ?? '');
}

/** The branch page address, /locations/{slug}. */
function branch_url(array $branch): string
{
    return url('locations/' . $branch['slug']);
}

// ---------------------------------------------------------------------------
// Branch context
// ---------------------------------------------------------------------------

/** Makes shop details read as this branch's until use_branch(null). Returns the previous branch. */
function use_branch(?array $branch): ?array
{
    $previous = $GLOBALS['branch_context'] ?? null;
    $GLOBALS['branch_context'] = $branch;
    return $previous;
}

/** Runs $fn with the branch of an order or request as the shop, then puts things back. */
function with_branch(?int $branchId, callable $fn): mixed
{
    $previous = use_branch($branchId ? get_branch($branchId) : null);
    try {
        return $fn();
    } finally {
        use_branch($previous);
    }
}

/** Value of a shop setting for the branch in context, or null to use the normal setting. */
function branch_setting_override(string $key): ?string
{
    $branch = $GLOBALS['branch_context'] ?? null;
    if (!$branch) {
        return null;
    }
    if ($key === 'store_phone_e164') {
        return trim((string) $branch['phone']) !== '' ? phone_e164($branch['phone']) : null;
    }
    $field = BRANCH_SETTING_FIELDS[$key] ?? null;
    if ($field === null) {
        return null;
    }
    $value = trim((string) ($branch[$field] ?? ''));
    if ($field === 'email' && !valid_email($value)) {
        return null;
    }
    return $value !== '' ? $value : null;
}

// ---------------------------------------------------------------------------
// The customer's chosen branch (kept for the visit)
// ---------------------------------------------------------------------------

function chosen_branch_id(): ?int
{
    return valid_branch_id($_SESSION['branch_id'] ?? 0);
}

function remember_branch(?int $id): void
{
    if ($id && valid_branch_id($id)) {
        $_SESSION['branch_id'] = $id;
    }
}

/** Distance in km between two points (for "nearest to you"). */
function distance_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $r = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

/** "Greenway", or "Greenway and Wright" with two shops: for page text and descriptions. */
function shop_places(): string
{
    $places = array_values(array_unique(array_map(static fn ($b) => $b['suburb'], active_branches())));
    return $places ? implode(' and ', $places) : setting_raw('store_suburb', 'Canberra');
}

/** Branch data for the checkout and forms: what the cards and the "nearest" script need. */
function branch_choices(): array
{
    return array_map(static fn ($b) => [
        'id'      => (int) $b['id'],
        'name'    => $b['name'],
        'address' => branch_address_line($b),
        'lat'     => is_numeric($b['latitude']) ? (float) $b['latitude'] : null,
        'lng'     => is_numeric($b['longitude']) ? (float) $b['longitude'] : null,
        'maps'    => branch_maps_url($b),
        'phone'   => branch_phone($b),
    ], active_branches());
}
