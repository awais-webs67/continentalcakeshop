<?php
declare(strict_types=1);

// Editable content: policy pages and FAQs. Their HTML is written by the shop in the
// admin panel, and tokens like {store_phone} keep them in step with the settings.

const POLICY_PAGES = ['privacy-policy', 'terms', 'refund-policy', 'allergens'];

// Occasions on the custom cake form (also used to prefill it from landing pages).
const CAKE_OCCASIONS = ['Birthday', 'Kids birthday', 'Wedding', 'Engagement', 'Baby shower', 'Gender reveal', 'Anniversary', 'Graduation', 'Corporate event', 'Other'];

// Addresses the website already uses, so a page can never take one of them.
const RESERVED_SLUGS = ['shop', 'cakes', 'order', 'messages', 'quote', 'cart', 'checkout', 'custom-cakes', 'about', 'contact', 'login', 'register',
    'logout', 'account', 'forgot-password', 'reset-password', 'verify-email', 'faq', 'api', 'admin', 'stripe-webhook', 'sitemap', 'robots', 'uploads', 'assets', '404', 'index', 'page'];

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

/** Landing pages (Birthday cakes Canberra...), for the footer links and the sitemap. */
function get_landing_pages(): array
{
    try {
        return db_all("SELECT slug, title FROM pages WHERE type = 'landing' AND is_active = 1 ORDER BY sort_order, title");
    } catch (PDOException) {
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
    // "{min_lead_hours} hours" in page text becomes "1 hour", "3 hours" or "2 days".
    $html = str_replace('{min_lead_hours} hours', e(notice_label((int) setting('min_lead_hours', '24'))), $html);
    $html = strtr($html, $tokens);
    return (string) preg_replace_callback('/\{url:([a-z0-9-]+)\}/', static fn ($m) => e(url($m[1])), $html);
}

/**
 * Keeps the formatting the admin editor produces and removes everything else. The HTML is parsed
 * (like a browser does) and rebuilt from an allowlist: only the tags and attributes below survive,
 * links may only be web, email, phone or on-site addresses, and style keeps text-align only.
 * Anything not on the list is dropped, so tricks like entity-encoded "javascript:" or
 * "<img/onerror=...>" cannot get through.
 */
const CLEAN_HTML_TAGS = ['h2', 'h3', 'h4', 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'a', 'blockquote', 'hr', 'img',
    'table', 'thead', 'tbody', 'tr', 'th', 'td', 'span'];
const CLEAN_HTML_DROP = ['script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'form', 'input', 'button', 'select', 'textarea',
    'svg', 'math', 'template', 'noscript', 'link', 'meta', 'base', 'audio', 'video', 'source', 'track', 'canvas', 'title', 'head'];
const CLEAN_HTML_ATTRS = ['a' => ['href', 'title', 'target'], 'img' => ['src', 'alt', 'width', 'height', 'title'], 'td' => ['colspan', 'rowspan'], 'th' => ['colspan', 'rowspan']];

/** A link or image address that is safe to keep: http(s), mailto, tel, or a path on this site. */
function clean_url(string $url, bool $image = false): ?string
{
    $url = trim($url);
    // Decide on the decoded form too, so "javascript&colon;" or "jav&#x09;ascript:" cannot hide a scheme.
    $check = $url;
    for ($i = 0; $i < 3; $i++) {
        $check = html_entity_decode($check, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $check = (string) preg_replace('/[\x00-\x20\x7F]+/', '', $check);
    if ($check === '') {
        return null;
    }
    if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $check, $m) && !str_starts_with($check, '{url:')) {
        $scheme = strtolower($m[1]);
        $allowed = $image ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel'];
        return in_array($scheme, $allowed, true) ? $url : null;
    }
    return $url; // relative: /path, #anchor, ?query or a {url:page} placeholder
}

function clean_html(string $html): string
{
    if (trim($html) === '') {
        return '';
    }
    if (!class_exists('DOMDocument')) {
        return nl2br(e(trim(strip_tags($html)))); // no DOM extension: keep the text only
    }
    $doc = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $doc->loadHTML('<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="clean-root">' . $html . '</div></body></html>', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $root = $doc->getElementById('clean-root');
    if (!$root) {
        return '';
    }

    $walk = static function (DOMNode $node) use (&$walk): void {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction || $child instanceof DOMCdataSection) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, CLEAN_HTML_DROP, true)) {
                $node->removeChild($child);
                continue;
            }
            $walk($child);
            if (!in_array($tag, CLEAN_HTML_TAGS, true)) {
                // Unknown tag (div, font, h1...): keep what is inside it.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            $keep = CLEAN_HTML_ATTRS[$tag] ?? [];
            $align = null;
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                if ($name === 'style' && preg_match('/text-align\s*:\s*(left|right|center|justify)/i', $attr->value, $m)) {
                    $align = strtolower($m[1]);
                }
                if (!in_array($name, $keep, true)) {
                    $child->removeAttribute($attr->name);
                }
            }
            if ($align !== null && $tag !== 'img') {
                $child->setAttribute('style', 'text-align: ' . $align);
            }
            if ($tag === 'a') {
                $href = $child->hasAttribute('href') ? clean_url($child->getAttribute('href')) : null;
                $href === null ? $child->removeAttribute('href') : $child->setAttribute('href', $href);
                if ($child->getAttribute('target') !== '') {
                    $child->setAttribute('target', '_blank');
                    $child->setAttribute('rel', 'noopener noreferrer');
                }
            }
            if ($tag === 'img') {
                $src = clean_url($child->getAttribute('src'), true);
                if ($src === null) {
                    $node->removeChild($child);
                    continue;
                }
                $child->setAttribute('src', $src);
                foreach (['width', 'height'] as $size) {
                    if ($child->hasAttribute($size) && !ctype_digit($child->getAttribute($size))) {
                        $child->removeAttribute($size);
                    }
                }
            }
            foreach (['colspan', 'rowspan'] as $span) {
                if ($child->hasAttribute($span) && !ctype_digit($child->getAttribute($span))) {
                    $child->removeAttribute($span);
                }
            }
        }
    };
    $walk($root);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    // saveHTML() escapes the braces of {url:page} placeholders inside links; put them back.
    $out = (string) preg_replace('/%7Burl:([a-z0-9-]+)%7D/i', '{url:$1}', $out);
    $out = (string) preg_replace('#<p[^>]*>(\s|&nbsp;|\xC2\xA0|<br\s*/?>)*</p>#i', '', $out);
    return trim($out);
}

function rich_text(?string $text): string
{
    $text = (string) $text;
    if ($text === '') {
        return '';
    }
    if (preg_match('/<(p|h[2-4]|ul|ol|blockquote|img|strong|em|a)\b/i', $text)) {
        return render_content(clean_html($text));
    }
    return '<p>' . nl2br(e($text)) . '</p>';
}

/** Plain-text version of stored content, used for structured data (paragraphs and list items keep a space between them). */
function content_text(string $html): string
{
    $html = preg_replace('#<(/?(?:p|h[1-6]|li|ul|ol|br|div|blockquote|tr|td|th)\b)#i', ' <$1', render_content($html));
    return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8')));
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

// ---------------------------------------------------------------------------
// Homepage sections (Admin > Homepage)
// ---------------------------------------------------------------------------

const HOME_SECTIONS = [
    'hero'       => ['Hero banner', 'Big title, photo and buttons at the top'],
    'usp'        => ['Feature strip', 'Four short selling points under the hero'],
    'categories' => ['Shop by category', 'Photo tiles for each category'],
    'featured'   => ['Customer favourites', 'Products marked "Feature on the homepage"'],
    'custom'     => ['Custom cakes', 'How custom orders work, with gallery photos'],
    'text'       => ['Text block', 'Your own heading and text, for news or seasonal notes'],
    'reviews'    => ['Google reviews', 'Rating and the reviews from Admin > Reviews'],
    'visit'      => ['Visit us', 'Address, opening hours and map'],
];

/** Sections in the order the admin chose, each with an on/off flag. New sections are added at the end. */
function home_sections(): array
{
    $saved = json_decode(setting('home_sections', '[]'), true);
    $list = [];
    foreach (is_array($saved) ? $saved : [] as $row) {
        if (isset($row['key'], HOME_SECTIONS[$row['key']]) && !isset($list[$row['key']])) {
            $list[$row['key']] = !empty($row['on']);
        }
    }
    foreach (array_keys(HOME_SECTIONS) as $key) {
        $list[$key] ??= $key !== 'text';
    }
    return $list;
}

/** A JSON list stored in a setting, e.g. the feature strip items. */
function setting_list(string $key): array
{
    $value = json_decode(setting($key, '[]'), true);
    return is_array($value) ? $value : [];
}
