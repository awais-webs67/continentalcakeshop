<?php
declare(strict_types=1);

/**
 * Normalises the $page array every public page passes to the header template.
 * Keys: title, description, canonical, image, type, noindex, schema (list of JSON-LD arrays).
 */
function page_meta(array $page): array
{
    return $page + [
        'title'       => setting('seo_home_title', setting('store_name')),
        'description' => setting('seo_home_description'),
        'canonical'   => url(ltrim(current_path(), '/')),
        'image'       => url('assets/img/og-image.jpg'),
        'type'        => 'website',
        'noindex'     => false,
        'robots'      => '',
        'schema'      => [],
        'body_class'  => '',
    ];
}

function json_ld(array $data): string
{
    return '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)
        . '</script>';
}

/** Bakery (LocalBusiness) markup used on every page so Google can show hours, phone and address. */
function local_business_schema(): array
{
    $hours = [];
    foreach (opening_hours() as $day => $slot) {
        if ($slot) {
            $hours[] = [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => 'https://schema.org/' . DAY_NAMES[$day],
                'opens'     => $slot[0],
                'closes'    => $slot[1],
            ];
        }
    }
    $sameAs = array_values(array_filter([setting('social_facebook'), setting('social_instagram'), setting('social_pinterest')]));

    $schema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'Bakery',
        '@id'        => url('#bakery'),
        'name'       => setting('store_name'),
        'url'        => url(),
        'logo'       => url('assets/img/logo-email.png'),
        'image'      => url('assets/img/shop-front.jpg'),
        'telephone'  => setting('store_phone_e164'),
        'email'      => setting('store_email'),
        'priceRange' => '$$',
        'paymentAccepted' => 'Cash, Credit Card, Debit Card, Bank Transfer, PayID',
        'servesCuisine' => 'Cakes, Desserts',
        'currenciesAccepted' => 'AUD',
        'address'    => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => setting('store_street'),
            'addressLocality' => setting('store_suburb'),
            'addressRegion'   => setting('store_state'),
            'postalCode'      => setting('store_postcode'),
            'addressCountry'  => setting('store_country', 'AU'),
        ],
        'areaServed' => array_merge(
            [['@type' => 'City', 'name' => 'Canberra'], ['@type' => 'City', 'name' => 'Queanbeyan']],
            array_map(static fn ($n) => ['@type' => 'Place', 'name' => $n . ', ACT'], ['Tuggeranong', 'Greenway', 'Woden', 'Weston Creek', 'Belconnen', 'Gungahlin', 'Molonglo Valley', 'Inner North', 'Inner South'])
        ),
        'openingHoursSpecification' => $hours,
        'hasMap'     => store_maps_url(),
    ];
    if ($sameAs) {
        $schema['sameAs'] = $sameAs;
    }
    if (setting('store_latitude') !== '' && setting('store_longitude') !== '') {
        $schema['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) setting('store_latitude'), 'longitude' => (float) setting('store_longitude')];
    }
    if (setting('google_rating') !== '' && setting('google_reviews_url') !== '') {
        $schema['sameAs'][] = setting('google_reviews_url');
    }
    if (setting('store_abn') !== '') {
        $schema['taxID'] = 'ABN ' . setting('store_abn');
    }
    return $schema;
}

function breadcrumb_schema(array $crumbs): array
{
    $items = [];
    foreach (array_values($crumbs) as $i => [$name, $link]) {
        $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => $link];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

function product_schema(array $product, array $sizes, array $images = []): array
{
    $images = array_values(array_unique(array_map('product_image_url', array_filter($images ?: [$product['image']]))));
    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        '@id'         => product_url($product) . '#product',
        'name'        => $product['name'],
        'description' => $product['meta_description'] ?: ($product['description'] ? content_text($product['description']) : ($product['short_description'] ?: product_default_description($product, $sizes))),
        'image'       => $images ?: [product_image_url(null)],
        'sku'         => 'CCS-' . $product['id'],
        'category'    => $product['category_name'],
        'brand'       => ['@type' => 'Brand', 'name' => setting('store_short_name')],
        'url'         => product_url($product),
    ];
    if ($sizes && empty($product['is_enquiry'])) {
        $availability = product_sold_out($product) ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock';
        $validUntil = ((int) date('Y') + 1) . '-12-31';
        // One offer per size, so Google can show the right price for each.
        $schema['offers'] = array_map(static fn ($s) => [
            '@type'           => 'Offer',
            'name'            => $s['label'] . (!empty($s['servings']) ? ' (' . $s['servings'] . ')' : ''),
            'price'           => number_format((float) $s['price'], 2, '.', ''),
            'priceCurrency'   => 'AUD',
            'priceValidUntil' => $validUntil,
            'availability'    => $availability,
            'itemCondition'   => 'https://schema.org/NewCondition',
            'url'             => product_url($product),
            'seller'          => ['@id' => url('#bakery')],
        ], $sizes);
        $servings = array_values(array_filter(array_map(static fn ($s) => trim((string) ($s['servings'] ?? '')), $sizes)));
        if ($servings) {
            $schema['additionalProperty'] = [['@type' => 'PropertyValue', 'name' => 'Serves', 'value' => implode(', ', $servings)]];
        }
    }
    return $schema;
}

// ---------------------------------------------------------------------------
// Old website addresses. The shop's previous site ranks well in Google, so its pages
// send visitors (and search engines) to the matching new page with a permanent redirect.
// More can be added in Admin > Settings > Social & SEO.
// ---------------------------------------------------------------------------

const LEGACY_REDIRECTS = [
    'allcakes'                           => 'shop',
    'plans-pricing'                      => 'shop',
    'copy-of-tortes-specialty-cakes-des' => 'shop',
    'tortes'                             => 'shop/tortes',
    'velvet-cakes'                       => 'shop/velvet-cakes',
    'mud-cakes'                          => 'shop/mud-cakes',
    'baked-cheesecakes'                  => 'shop/baked-cheesecakes',
    'continental-cheesecakes'            => 'shop/continental-cheesecakes',
    'tea-cakes-desserts'                 => 'shop/tea-cakes-desserts',
    'celebration-cakes'                  => 'custom-cakes',
    // The old gallery pages go to the matching landing page (or Custom Cakes if it is switched off).
    'copy-of-birthday-gallery'           => 'baby-shower-cakes-canberra',
    'copy-of-wedding-baby-gallery'       => 'wedding-cakes-canberra',
    'gallery-06'                         => 'birthday-cakes-canberra',
    'gallery'                            => 'custom-cakes',
    'about-us'                           => 'about',
    'contact-us'                         => 'contact',
    'home'                               => '',
];

/** Extra redirects from the admin panel, one "old-path new-path" pair per line. */
function admin_redirects(): array
{
    $map = [];
    foreach (preg_split('/\R/', setting('seo_redirects')) as $line) {
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) === 2) {
            $map[strtolower(trim($parts[0], '/'))] = trim($parts[1], '/');
        }
    }
    return $map;
}

/** Where an unknown address should go, or null. */
function legacy_redirect_target(string $path): ?string
{
    $key = strtolower(trim($path, '/'));
    $map = admin_redirects() + LEGACY_REDIRECTS;
    if (!array_key_exists($key, $map)) {
        return null;
    }
    $target = $map[$key];
    if (preg_match('#^https?://#i', $target)) {
        return $target;
    }
    // A landing page the shop has switched off or deleted: send people to Custom Cakes instead.
    if (preg_match('/^[a-z0-9-]+-canberra$/', $target) && !get_page($target)) {
        $target = 'custom-cakes';
    }
    return url($target);
}

/** Remembers an address that was not found, for Admin > SEO (skips files like .png and .js). */
function log_not_found(string $path): void
{
    $path = str_limit('/' . ltrim($path, '/'), 255);
    if (preg_match('/\.(png|jpe?g|gif|webp|svg|ico|css|js|map|txt|xml|woff2?|php)$/i', $path) || str_starts_with($path, '/admin')) {
        return;
    }
    $referrer = str_limit((string) ($_SERVER['HTTP_REFERER'] ?? ''), 255);
    try {
        db_run(
            'INSERT INTO not_found_log (path, hits, referrer, first_seen, last_seen) VALUES (?, 1, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = NOW(), referrer = COALESCE(NULLIF(VALUES(referrer), \'\'), referrer)',
            [$path, $referrer !== '' ? $referrer : null]
        );
    } catch (Throwable) {
        // The log is a convenience; a missing table must never break the 404 page.
    }
}

/** Tags for Google Analytics and site verification, from Admin > Settings > Social & SEO. */
function analytics_tags(): string
{
    $html = '';
    if (($v = setting('seo_google_verification')) !== '') {
        $html .= '<meta name="google-site-verification" content="' . e($v) . '">' . "\n";
    }
    if (($v = setting('seo_bing_verification')) !== '') {
        $html .= '<meta name="msvalidate.01" content="' . e($v) . '">' . "\n";
    }
    $ga = setting('seo_ga4_id');
    if (preg_match('/^G-[A-Z0-9]+$/', $ga)) {
        $html .= '<script async src="https://www.googletagmanager.com/gtag/js?id=' . e($ga) . '"></script>' . "\n"
            . "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','" . e($ga) . "');</script>\n";
    }
    return $html;
}

// ---------------------------------------------------------------------------
// Product SEO: defaults used when the shop leaves a field empty, photo alt text,
// and the checklist shown in the product editor and the product list.
// ---------------------------------------------------------------------------

/** Title patterns for a cake page, best first; the first one that fits in 60 characters is used. */
function product_title_patterns(): array
{
    return ['%s Canberra | ' . setting('store_short_name'), '%s | Order Online in Canberra', '%s Canberra', '%s'];
}

/** Search title used when the product has none, e.g. "Red Velvet Cake Canberra | The Continental Cake Shop". */
function product_default_title(array $product): string
{
    foreach (product_title_patterns() as $pattern) {
        $title = sprintf($pattern, $product['name']);
        if (mb_strlen($title) <= 60) {
            return $title;
        }
    }
    return (string) $product['name'];
}

/** Search description used when the product has none. */
function product_default_description(array $product, array $sizes): string
{
    if (!empty($product['is_enquiry']) || !$sizes) {
        return sprintf('%s made to order in Greenway, Canberra. 100%% Halal. Send us your details for a quote.', $product['name']);
    }
    $sizeText = implode(', ', array_map(static fn ($s) => $s['label'] . (!empty($s['servings']) ? ' (' . strtolower((string) $s['servings']) . ')' : ''), $sizes));
    return sprintf(
        '%s in %s from %s. 100%% Halal. Order online for pickup in Greenway%s.',
        $product['name'], $sizeText, money(min(array_map(static fn ($s) => (float) $s['price'], $sizes))),
        setting_bool('delivery_enabled') ? ' or delivery across Canberra' : ''
    );
}

/** Alt text of a cake's main photo: what the shop typed, or the name and shop. */
function product_image_alt(array $product): string
{
    $alt = trim((string) ($product['image_alt'] ?? ''));
    return $alt !== '' ? $alt : $product['name'] . ' from ' . setting('store_short_name') . ', Canberra';
}

/** Extra photos of a cake (the main photo is products.image). */
/**
 * Adds 'photos' (main photo first, then up to three extra) to each product for the product
 * cards, using one query for the whole list.
 */
function with_card_photos(array $products): array
{
    $ids = array_values(array_filter(array_map(static fn ($p) => (int) ($p['id'] ?? 0), $products)));
    $extra = [];
    if ($ids) {
        try {
            foreach (db_all('SELECT product_id, image FROM product_images WHERE product_id IN (' . implode(',', $ids) . ') ORDER BY product_id, sort_order, id') as $row) {
                $extra[(int) $row['product_id']][] = $row['image'];
            }
        } catch (PDOException) {
        }
    }
    foreach ($products as &$p) {
        $p['photos'] = array_slice(array_values(array_filter(array_merge([$p['image'] ?? null], $extra[(int) ($p['id'] ?? 0)] ?? []))), 0, 4);
    }
    return $products;
}

function get_product_images(int $productId): array
{
    try {
        return db_all('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$productId]);
    } catch (PDOException) {
        return [];
    }
}

/**
 * The SEO checklist for a product. Each row: [label, passed, tip].
 * The same rules run live in the editor (admin.js) while staff type.
 */
function product_seo_checks(array $p, int $extraPhotos = 0, ?array $sizes = null): array
{
    // What Google is shown: the typed title and description, or the automatic ones.
    $sizes ??= !empty($p['id']) ? get_product_sizes((int) $p['id']) : [];
    $title = trim((string) ($p['meta_title'] ?? '')) ?: product_default_title($p);
    $desc = trim((string) ($p['meta_description'] ?? '')) ?: product_default_description($p, $sizes);
    $keyword = mb_strtolower(trim((string) ($p['focus_keyword'] ?? '')));
    $body = mb_strtolower(content_text((string) ($p['description'] ?? '')) . ' ' . ($p['short_description'] ?? ''));
    $has = static fn (string $text): bool => $keyword !== '' && str_contains(mb_strtolower($text), $keyword);
    $slugWords = str_replace('-', ' ', (string) ($p['slug'] ?? ''));
    $words = str_word_count(content_text((string) ($p['description'] ?? '')));
    return [
        ['Focus keyword chosen', $keyword !== '', 'Type the words people search for, e.g. "red velvet cake canberra".'],
        ['Keyword in the page title', $has($title), 'Start the page title with the keyword.'],
        ['Keyword in the search description', $has($desc), 'Use the keyword once in the description.'],
        ['Keyword in the web address', $keyword !== '' && count(array_filter(explode(' ', $keyword), static fn ($w) => mb_strlen($w) > 2 && !str_contains($slugWords, $w))) <= 1, 'Use the main words of the keyword in the web address.'],
        ['Keyword in the description text', $has($body), 'Mention the keyword in the full description.'],
        ['Page title 30 to 60 characters', mb_strlen($title) >= 30 && mb_strlen($title) <= 60, 'Google cuts titles longer than about 60 characters.'],
        ['Search description 120 to 160 characters', mb_strlen($desc) >= 120 && mb_strlen($desc) <= 160, 'A full description gets more clicks. Google shows about 155 characters.'],
        ['Mentions Canberra or the suburb', (bool) preg_match('/canberra|greenway|tuggeranong|act\b/i', $title . ' ' . $desc), 'Local words help you rank for "cakes Canberra".'],
        ['Description of 60 words or more', $words >= 60, 'Write a few sentences: flavours, layers, sizes, occasions, storage.'],
        ['Main photo', !empty($p['image']), 'Add a photo. Describe it in the photo text, e.g. "Red velvet cake with cream cheese frosting".'],
        ['Two or more photos', !empty($p['image']) && $extraPhotos >= 1, 'Extra photos (slice, side view, sizes) help customers and Google Images.'],
    ];
}

/** "8/11" style score and a word for it. */
function seo_score(array $checks): array
{
    $passed = count(array_filter($checks, static fn ($c) => $c[1]));
    $ratio = $checks ? $passed / count($checks) : 0;
    return [$passed, count($checks), $ratio >= .8 ? 'good' : ($ratio >= .5 ? 'ok' : 'poor')];
}

/**
 * Keeps an old address working after a cake or category is renamed: adds "old new" to the
 * redirects list (Settings > Social & SEO) and points older redirects at the new address.
 */
function add_seo_redirect(string $from, string $to): void
{
    $from = '/' . trim($from, '/');
    $to = '/' . trim($to, '/');
    if ($from === $to) {
        return;
    }
    $lines = [];
    foreach (preg_split('/\R/', setting('seo_redirects')) as $line) {
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) !== 2 || '/' . trim($parts[0], '/') === $from || '/' . trim($parts[0], '/') === $to) {
            continue;
        }
        // An older redirect that pointed at the old address now goes straight to the new one.
        $lines[] = $parts[0] . ' ' . ('/' . trim($parts[1], '/') === $from ? $to : $parts[1]);
    }
    $lines[] = $from . ' ' . $to;
    setting_save('seo_redirects', implode("\n", $lines));
}
