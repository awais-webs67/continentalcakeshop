<?php
declare(strict_types=1);

// Editable content: policy pages and FAQs. Their HTML is written by the shop in the
// admin panel, and tokens like {store_phone} keep them in step with the settings.

const POLICY_PAGES = ['privacy-policy', 'terms', 'refund-policy', 'allergens'];

function get_page(string $slug): ?array
{
    try {
        return db_one('SELECT * FROM pages WHERE slug = ? AND is_active = 1', [$slug]);
    } catch (PDOException $e) {
        // Table missing until database migrations have been run; show a 404 instead of a crash.
        error_log('Content pages unavailable: ' . $e->getMessage());
        return null;
    }
}

function get_faqs(): array
{
    try {
        return db_all('SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order, id');
    } catch (PDOException $e) {
        error_log('FAQs unavailable: ' . $e->getMessage());
        return [];
    }
}

/** Fills {token} placeholders in stored content with current settings. */
function render_content(string $html): string
{
    $tokens = [
        '{store_name}'       => e(setting('store_name')),
        '{store_phone}'      => '<a href="tel:' . e(setting('store_phone_e164')) . '">' . e(setting('store_phone')) . '</a>',
        '{store_email}'      => '<a href="mailto:' . e(setting('store_email')) . '">' . e(setting('store_email')) . '</a>',
        '{store_address}'    => e(store_address_line()),
        '{min_lead_hours}'   => e(setting('min_lead_hours', '24')),
        '{custom_lead_days}' => e(setting('custom_lead_days', '3')),
    ];
    $html = strtr($html, $tokens);
    return (string) preg_replace_callback('/\{url:([a-z0-9-]+)\}/', static fn ($m) => e(url($m[1])), $html);
}

/** Plain-text version of stored content, used for FAQ structured data. */
function content_text(string $html): string
{
    return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(render_content($html)), ENT_QUOTES, 'UTF-8')));
}

// ---------------------------------------------------------------------------
// Promo popup card (Admin > Promo popup)
// ---------------------------------------------------------------------------

// Pages where the popup never shows, so it cannot get in the way of paying or logging in.
const POPUP_SKIP_PATHS = ['/cart', '/checkout', '/order', '/login', '/register', '/account', '/forgot-password', '/reset-password'];

/** The popup to show on this page, or null. Includes the linked coupon when it can still be used. */
function promo_popup(): ?array
{
    if (!setting_bool('popup_enabled')) {
        return null;
    }
    $path = current_path();
    if (setting('popup_pages', 'all') === 'home' && $path !== '/') {
        return null;
    }
    foreach (POPUP_SKIP_PATHS as $skip) {
        if ($path === $skip || str_starts_with($path, $skip . '/')) {
            return null;
        }
    }
    $coupon = null;
    if (setting('popup_coupon_id') !== '' && coupons_enabled()) {
        $coupon = db_one('SELECT * FROM coupons WHERE id = ?', [(int) setting('popup_coupon_id')]);
        if ($coupon && coupon_error($coupon, PHP_FLOAT_MAX) !== null) {
            $coupon = null;
        }
    }
    return [
        'title'       => setting('popup_title'),
        'text'        => setting('popup_text'),
        'image'       => setting('popup_image'),
        'button_text' => setting('popup_button_text'),
        'button_url'  => setting('popup_button_url'),
        'delay'       => max(0, (int) setting('popup_delay_seconds', '3')),
        'frequency'   => setting('popup_frequency', 'once'),
        'version'     => setting('popup_version', '1'),
        'coupon'      => $coupon,
    ];
}

/** Turns "shop" or "/custom-cakes" into a site link and keeps full https:// links as they are. */
function popup_link(string $target): string
{
    $target = trim($target);
    if ($target === '') {
        return url('shop');
    }
    return preg_match('#^https?://#i', $target) ? $target : url(ltrim($target, '/'));
}
