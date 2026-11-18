<?php
declare(strict_types=1);

/**
 * Normalises the $page array every public page passes to the header template.
 * Keys: title, description, canonical, image, type, noindex, schema (list of JSON-LD arrays),
 * meta (extra Open Graph tags, property => content).
 */
function page_meta(array $page): array
{
    return $page + [
        'title'       => setting('seo_home_title', setting('store_name')),
        'description' => setting('seo_home_description'),
        'canonical'     => url(ltrim(current_path(), '/')),
        'image'         => url('assets/img/og-image.jpg'),
        'preload_image' => '',
        'type'          => 'website',
        'noindex'     => false,
        'robots'      => '',
        'schema'      => [],
        'meta'        => [],
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
    $sameAs = array_values(array_filter([
        setting('social_facebook'),
        setting('social_instagram'),
        setting('social_pinterest'),
        setting('social_youtube'),
        setting('google_reviews_url'),
    ], static fn (string $u): bool => $u !== '' && !str_contains($u, 'maps/search/?api=1&query=')));
    $sameAs = array_values(array_unique($sameAs));
    $founded = setting('store_founded');

    $schema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'Bakery',
        '@id'        => url('#bakery'),
        'name'       => setting('store_name'),
        'url'        => url(),
        'logo'       => url('assets/img/logo-email.png'),
        'image'      => shop_photo()['absolute'],
        'telephone'  => setting('store_phone_e164'),
        'priceRange' => '$$',
        'paymentAccepted' => 'Cash, Credit Card, Debit Card, Bank Transfer, PayID',
        'servesCuisine' => 'Cakes, Desserts, Halal Bakery',
        'menu'       => url('shop'),
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
        // What the shop is known for, so search engines and AI assistants connect it with these searches.
        'knowsAbout' => array_values(array_unique(array_merge(['Custom cakes', 'Birthday cakes', 'Wedding cakes', 'Halal cakes'], array_column(get_categories(), 'name')))),
    ];
    if ($founded !== '') {
        $schema['foundingDate'] = $founded === '2015' ? '2015-03' : $founded;
    }
    if (setting('seo_home_description') !== '') {
        $schema['description'] = setting('seo_home_description');
    }
    if (shop_email() !== '') {
        $schema['email'] = shop_email(); // only when the shop shows its email on the website
    }
    if ($sameAs) {
        $schema['sameAs'] = $sameAs;
    }
    if (setting('store_latitude') !== '' && setting('store_longitude') !== '') {
        $schema['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) setting('store_latitude'), 'longitude' => (float) setting('store_longitude')];
    }
    if (setting('store_abn') !== '') {
        $schema['taxID'] = 'ABN ' . setting('store_abn');
    }
    // The other shops are their own Bakery entries (on their branch pages), linked to this one.
    if (empty($GLOBALS['branch_context']) && count(active_branches()) > 1) {
        $schema['subOrganization'] = array_values(array_map(
            static fn ($b) => ['@id' => branch_url($b) . '#bakery'],
            array_filter(active_branches(), static fn ($b) => !(int) $b['is_main'])
        ));
    }
    return $schema;
}

/** The Bakery entry for one branch page: the shop's own address, phone and map, part of the main business. */
function branch_business_schema(array $branch): array
{
    if ((int) $branch['is_main']) {
        return local_business_schema();
    }
    $schema = with_branch((int) $branch['id'], static fn () => local_business_schema());
    $schema['@id'] = branch_url($branch) . '#bakery';
    $schema['name'] = setting('store_short_name') . ' ' . $branch['name'];
    $schema['url'] = branch_url($branch);
    $schema['hasMap'] = branch_maps_url($branch);
    if (!empty($branch['photo'])) {
        $schema['image'] = product_image_url($branch['photo']);
    }
    $schema['parentOrganization'] = ['@id' => url('#bakery')];
    unset($schema['subOrganization']);
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

function webpage_schema(string $name, string $link, string $description = '', ?string $updatedAt = null, string $type = 'WebPage'): array
{
    $schema = [
        '@context'   => 'https://schema.org',
        '@type'      => $type,
        '@id'        => $link . '#webpage',
        'name'       => $name,
        'url'        => $link,
        'inLanguage' => 'en-AU',
        'isPartOf'   => ['@type' => 'WebSite', 'url' => url()],
        'about'      => ['@id' => url('#bakery')],
    ];
    if ($description !== '') {
        $schema['description'] = $description;
    }
    if ($updatedAt !== null && $updatedAt !== '' && ($ts = strtotime($updatedAt)) !== false) {
        $schema['dateModified'] = date('c', $ts);
    }
    return $schema;
}

function product_schema(array $product, array $sizes, array $images = []): array
{
    $images = array_values(array_unique(array_map('product_image_url', array_filter($images ?: [$product['image']]))));
    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        '@id'         => product_url($product) . '#product',
        'name'        => $product['name'],
        'description' => ($product['description'] ? content_text($product['description']) : '') ?: ($product['short_description'] ?: ($product['meta_description'] ?: product_default_description($product, $sizes))),
        'image'       => $images ?: [product_image_url(null)],
        'sku'         => 'CCS-' . $product['id'],
        'category'    => $product['category_name'],
        'brand'       => ['@type' => 'Brand', 'name' => setting('store_short_name')],
        'url'         => product_url($product),
        'keywords'    => implode(', ', product_search_words($product)),
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
        ] + offer_policies($product), $sizes);
        $servings = array_values(array_filter(array_map(static fn ($s) => trim((string) ($s['servings'] ?? '')), $sizes)));
        if ($servings) {
            $schema['additionalProperty'] = [['@type' => 'PropertyValue', 'name' => 'Serves', 'value' => implode(', ', $servings)]];
        }
    }
    return $schema;
}

/**
 * Return policy and delivery for each price Google is given (it asks for both on product pages).
 * Cakes are fresh food, so they are not taken back for a change of mind; the refund page explains
 * what happens when something is wrong. Delivery: the fee, the postcodes and the notice needed.
 */
function offer_policies(array $product): array
{
    $extra = [];
    if (get_page('refund-policy')) {
        $extra['hasMerchantReturnPolicy'] = [
            '@type'                => 'MerchantReturnPolicy',
            'applicableCountry'    => 'AU',
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnNotPermitted',
            'merchantReturnLink'   => url('refund-policy'),
        ];
    }
    if (setting_bool('delivery_enabled')) {
        $region = ['@type' => 'DefinedRegion', 'addressCountry' => 'AU'];
        foreach (array_filter(preg_split('/\s*,\s*/', trim(setting('delivery_postcodes')))) as $range) {
            [$from, $to] = array_pad(array_map('trim', explode('-', $range, 2)), 2, null);
            if (preg_match('/^\d{4}$/', (string) $from)) {
                $region['postalCodeRange'][] = ['postalCodeBegin' => $from, 'postalCodeEnd' => preg_match('/^\d{4}$/', (string) $to) ? $to : $from];
            }
        }
        $noticeDays = (int) ceil(max(order_notice_hours('delivery'), (int) ($product['lead_time_hours'] ?? 0)) / 24);
        $extra['shippingDetails'] = [
            '@type'               => 'OfferShippingDetails',
            'shippingRate'        => ['@type' => 'MonetaryAmount', 'value' => number_format(max(0, (float) setting('delivery_fee', '0')), 2, '.', ''), 'currency' => 'AUD'],
            'shippingDestination' => $region,
            'deliveryTime'        => [
                '@type'        => 'ShippingDeliveryTime',
                'handlingTime' => ['@type' => 'QuantitativeValue', 'minValue' => $noticeDays, 'maxValue' => $noticeDays, 'unitCode' => 'DAY'],
                'transitTime'  => ['@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 0, 'unitCode' => 'DAY'],
            ],
        ];
    }
    return $extra;
}

/** Extra Open Graph tags for a cake page (Facebook, Pinterest and WhatsApp read the price from them). */
function product_meta_tags(array $product, array $sizes): array
{
    $tags = ['og:image:alt' => product_image_alt($product)];
    if ($sizes && empty($product['is_enquiry'])) {
        $tags += [
            'product:price:amount'      => number_format(min(array_map(static fn ($s) => (float) $s['price'], $sizes)), 2, '.', ''),
            'product:price:currency'    => 'AUD',
            'product:availability'      => product_sold_out($product) ? 'out of stock' : 'in stock',
            'product:condition'         => 'new',
            'product:brand'             => setting('store_short_name'),
            'product:retailer_item_id'  => 'CCS-' . $product['id'],
            'product:category'          => (string) $product['category_name'],
        ];
    }
    return $tags;
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
    'gallery'                            => 'custom-cake-gallery',
    'cake-gallery'                       => 'custom-cake-gallery',
    'custom-cakes-gallery'               => 'custom-cake-gallery',
    'about-us'                           => 'about',
    'contact-us'                         => 'contact',
    'locations'                          => 'contact',
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

/**
 * The usual address for a near-miss that reached the 404 page: "/about/" -> "/about",
 * "/About" -> "/about", "/index.php" -> "/". Search engines then see one address per page.
 */
function tidy_path_redirect(): ?string
{
    $raw = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $path = current_path();
    $tidy = strtolower($path) === '/index.php' ? '/' : strtolower($path);
    if ($tidy === $path && !(strlen($raw) > 1 && str_ends_with($raw, '/'))) {
        return null;
    }
    if (!preg_match('#^/[a-z0-9/-]*$#', $tidy)) {
        return null;
    }
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
    return url(ltrim($tidy, '/')) . ($query !== '' ? '?' . $query : '');
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
    return ['%s Canberra | ' . setting('store_short_name'), '%s Canberra | Cake Shop Greenway', '%s Canberra | Order Online', '%s Canberra', '%s'];
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
    $labels = array_map(static fn ($s) => (string) $s['label'], $sizes);
    // "20 pieces, 25 pieces or 30 pieces" reads better as "20, 25 or 30 pieces".
    $unit = preg_match('/^\d+ (\D+)$/', $labels[0], $m) ? $m[1] : null;
    if ($unit !== null && count($labels) > 1 && count(array_filter($labels, static fn ($l) => preg_match('/^\d+ ' . preg_quote($unit, '/') . '$/', $l))) === count($labels)) {
        $labels = array_map('intval', $labels);
        $labels[count($labels) - 1] .= ' ' . $unit;
    }
    $sizeText = count($sizes) === 1
        ? strtolower((string) ($sizes[0]['servings'] ?: $sizes[0]['label']))
        : implode(', ', array_slice($labels, 0, -1)) . ' or ' . end($labels);
    $text = sprintf(
        'Fresh 100%% Halal %s Canberra — %s, from %s. Baked in ' . shop_places() . '; order online for pickup%s.',
        $product['name'], $sizeText, money(min(array_map(static fn ($s) => (float) $s['price'], $sizes))),
        setting_bool('delivery_enabled') ? ' or delivery across Canberra' : ' in Canberra, ACT'
    );
    return mb_strlen($text) > 160 ? rtrim(mb_substr($text, 0, 157), ' ,.') . '...' : $text;
}

/**
 * Other words people search for, used when the cake has none saved: its name, the focus keyword,
 * "<name> canberra", "<category> canberra" and "halal cakes canberra". Same as update 030.
 */
function product_default_search_words(string $name, string $categoryName, string $keyword = ''): string
{
    $name = mb_strtolower(trim($name));
    $words = [$name, mb_strtolower(trim($keyword)), $name . ' canberra', mb_strtolower(str_replace('&', 'and', trim($categoryName))) . ' canberra', 'halal cakes canberra'];
    return implode(', ', array_values(array_unique(array_filter($words, static fn ($w) => $w !== '' && $w !== ' canberra'))));
}

/** A cake's search words as a list: what staff typed, else the automatic ones. */
function product_search_words(array $product): array
{
    $text = trim((string) ($product['search_words'] ?? ''))
        ?: product_default_search_words((string) $product['name'], (string) ($product['category_name'] ?? ''), (string) ($product['focus_keyword'] ?? ''));
    return array_values(array_unique(array_filter(array_map(static fn ($w) => trim(mb_strtolower($w)), explode(',', $text)))));
}

/** Tidies typed search words: lower case, comma separated, no repeats, at most 255 characters. */
function clean_search_words(string $text): string
{
    $words = array_values(array_unique(array_filter(array_map(static fn ($w) => trim((string) preg_replace('/\s+/', ' ', mb_strtolower($w))), preg_split('/[,;\n]+/', $text)))));
    $out = '';
    foreach ($words as $word) {
        $next = $out === '' ? $word : $out . ', ' . $word;
        if (mb_strlen($next) > 255) {
            break;
        }
        $out = $next;
    }
    return $out;
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

/** Small words that do not need to be in a web address. */
const SEO_STOP_WORDS = ['and', 'the', 'with', 'for', 'from', 'in', 'of', 'n'];

/**
 * Text as search engines read it, for matching a focus keyword: lower case, "&" as "and",
 * apostrophes dropped, hyphens and punctuation as spaces. "Cookies 'n' Cream" matches
 * "cookies n cream", "Honey-Bee" matches "honey bee". admin.js has the same rules (seoWords).
 */
function seo_words(string $text): string
{
    $text = mb_strtolower(str_replace('&', ' and ', $text));
    $text = str_replace(["'", '’', '‘'], '', $text);
    return trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text));
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
    $keyword = seo_words((string) ($p['focus_keyword'] ?? ''));
    $body = content_text((string) ($p['description'] ?? '')) . ' ' . ($p['short_description'] ?? '');
    $has = static fn (string $text): bool => $keyword !== '' && str_contains(' ' . seo_words($text) . ' ', ' ' . $keyword . ' ');
    $slugWords = ' ' . str_replace('-', ' ', (string) ($p['slug'] ?? '')) . ' ';
    $words = str_word_count(content_text((string) ($p['description'] ?? '')));
    return [
        ['Focus keyword chosen', $keyword !== '', 'Type the words people search for, e.g. "red velvet cake canberra".'],
        ['Keyword in the page title', $has($title), 'Start the page title with the keyword.'],
        ['Keyword in the search description', $has($desc), 'Use the keyword once in the description.'],
        ['Keyword in the web address', $keyword !== '' && count(array_filter(explode(' ', $keyword), static fn ($w) => mb_strlen($w) > 2 && !in_array($w, SEO_STOP_WORDS, true) && !str_contains($slugWords, ' ' . $w . ' '))) <= 1, 'Use the main words of the keyword in the web address.'],
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

/** A sentence about how each kind of cake is made, for descriptions written by product_seo_autofill(). */
const SEO_CATEGORY_TEXT = [
    'tortes'                  => ['Every %s is made fresh in our Greenway kitchen with 100%% Halal ingredients, and the writing on top is free: add your message when you order.', ['Birthdays and family celebrations', 'Eid, engagements and anniversaries', 'Office morning teas and farewells']],
    'velvet-cakes'            => ['Our %s is baked fresh in Greenway with 100%% Halal ingredients and finished by hand. Free writing on top is included, so it is ready to celebrate.', ['Birthdays and anniversaries', 'Engagements and Eid gatherings', 'Anyone who loves a cream cheese frosting']],
    'mud-cakes'               => ['Our %s is made with 100%% Halal ingredients in our Greenway kitchen, with free writing on top. Mud cakes are firm enough to decorate and travel well.', ['Birthday cakes that travel well to parties', 'A base for decorated and themed cakes', 'Chocolate lovers of every age']],
    'baked-cheesecakes'       => ['Our %s is baked in Greenway with 100%% Halal ingredients, so it suits a family dinner as well as a party.', ['Dinner parties and family lunches', 'Office morning teas', 'A dessert table next to a celebration cake']],
    'continental-cheesecakes' => ['Our %s is a light, set continental-style cheesecake made with 100%% Halal ingredients in Greenway.', ['Dessert after a big family meal', 'Morning and afternoon teas', 'Guests who prefer something light']],
    'tea-cakes-desserts'      => ['Our %s is made fresh in Greenway with 100%% Halal ingredients, ready for morning tea, a dessert table or a gift.', ['Morning and afternoon teas', 'Dessert tables and parties', 'Bringing something sweet to a friend']],
    ''                        => ['Our %s is made fresh in our Greenway kitchen with 100%% Halal ingredients.', ['Birthdays and celebrations', 'Family gatherings and Eid', 'Morning teas and dessert tables']],
];

/**
 * Fills in what Google needs when a cake is saved without it, so a new cake is search ready
 * straight away. Only empty or very short fields are filled; anything staff wrote is kept.
 *  - focus keyword: the cake's name plus "canberra"
 *  - photo text (alt): the name and the short description
 *  - description under 60 words: what staff wrote, then a paragraph about how it is made, the
 *    sizes, "Perfect for" occasions and a "<name> Canberra pickup and delivery" section
 *  - a description with no mention of the keyword gets the pickup and delivery section
 *  - search title and description only when the automatic ones would miss a custom keyword
 *
 * @param array $sizes rows with label and servings
 */
function product_seo_autofill(array $data, array $sizes, string $categorySlug, bool $hasImage): array
{
    $name = trim((string) ($data['name'] ?? ''));
    if ($name === '') {
        return $data;
    }
    $short = trim((string) ($data['short_description'] ?? ''));
    $keyword = trim((string) ($data['focus_keyword'] ?? ''));
    if ($keyword === '') {
        $keyword = $data['focus_keyword'] = mb_strtolower($name) . ' canberra';
    }
    if ($hasImage && trim((string) ($data['image_alt'] ?? '')) === '') {
        $first = trim((string) preg_split('/(?<=[.!?])\s/', $short)[0]);
        $data['image_alt'] = str_limit($first !== '' ? $name . ', ' . mb_strtolower(mb_substr($first, 0, 1)) . rtrim(mb_substr($first, 1), '.') : $name . ' from ' . setting('store_short_name') . ', Canberra', 160);
    }

    $enquiry = !empty($data['is_enquiry']);
    $ordering = '<h3>' . e($name) . ' Canberra pickup and delivery</h3><p>' . ($enquiry
        ? 'Send us your details for a quote and pick up from {store_address}, or ask about delivery across Canberra and Queanbeyan. Questions about allergens or a custom design? Call us on {store_phone}.'
        : 'Order online at least {min_lead_hours} hours ahead and pick up from {store_address}, or choose delivery across Canberra and Queanbeyan. Questions about allergens or a custom design? Call us on {store_phone}.') . '</p>';
    $html = trim((string) ($data['description'] ?? ''));
    $has = static fn (string $text): bool => str_contains(' ' . seo_words($text) . ' ', ' ' . seo_words($keyword) . ' ');
    if (str_word_count(content_text($html)) < 60) {
        [$made, $occasions] = SEO_CATEGORY_TEXT[$categorySlug] ?? SEO_CATEGORY_TEXT[''];
        $labels = array_values(array_filter(array_map(static fn ($s) => trim((string) $s['label']) . (trim((string) ($s['servings'] ?? '')) !== '' ? ' (' . mb_strtolower(trim((string) $s['servings'])) . ')' : ''), $sizes)));
        $sizeText = match (true) {
            count($labels) > 1 => 'Choose from ' . implode(', ', array_slice($labels, 0, -1)) . ' or ' . end($labels) . '.',
            count($labels) === 1 => 'It comes in one size: ' . $labels[0] . '.',
            default => 'The price depends on the size and design, so ask us for a quote.',
        };
        $intro = $html !== '' ? $html : ($short !== '' ? '<p>' . e($short) . '</p>' : '');
        $html = $intro
            . '<p>' . e(sprintf($made, $name)) . ' ' . e($sizeText) . '</p>'
            . '<h3>Perfect for</h3><ul>' . implode('', array_map(static fn ($o) => '<li>' . e($o) . '</li>', $occasions)) . '</ul>'
            . $ordering;
    } elseif (!$has(content_text($html))) {
        $html .= $ordering;
    }
    $data['description'] = $html;

    if (trim((string) ($data['search_words'] ?? '')) === '') {
        $categoryName = (string) (db_value('SELECT name FROM categories WHERE slug = ?', [$categorySlug]) ?: '');
        $data['search_words'] = product_default_search_words($name, $categoryName, $keyword);
    }

    // The automatic title and description start with "<name> Canberra"; a different keyword needs its own.
    if (trim((string) ($data['meta_title'] ?? '')) === '' && !$has(product_default_title($data))) {
        $base = mb_convert_case($keyword, MB_CASE_TITLE);
        $title = $base . ' | ' . setting('store_short_name');
        $data['meta_title'] = mb_strlen($title) <= 60 ? $title : mb_substr($base, 0, 60);
    }
    $defaultDesc = product_default_description($data, $sizes);
    if (trim((string) ($data['meta_description'] ?? '')) === '' && !$has($defaultDesc)) {
        // "Fresh 100% Halal Lemon Tart Canberra — serves 12, from $42.00. Baked in Greenway..."
        $rest = str_contains($defaultDesc, '— ') ? substr($defaultDesc, strpos($defaultDesc, '— ') + strlen('— ')) : $defaultDesc;
        $desc = 'Fresh 100% Halal ' . mb_convert_case($keyword, MB_CASE_TITLE) . ' — ' . $rest;
        $data['meta_description'] = mb_strlen($desc) <= 160 ? $desc : rtrim(mb_substr($desc, 0, (int) mb_strrpos(mb_substr($desc, 0, 158), ' ')), ' ,.') . '...';
    }
    return $data;
}

// ---------------------------------------------------------------------------
// Version history of a cake's SEO text (product editor > "Earlier versions"). Whenever the
// text changes (typed, written by the AI or restored), the text it replaces is kept, so any
// version can be brought back. The last 15 per cake are kept.
// ---------------------------------------------------------------------------

/** Keeps $old's SEO fields when $new changes any of them. $by: 'edit', 'ai' or 'restore'. */
function seo_history_save(int $productId, array $old, array $new, string $by, string $who = ''): void
{
    $before = [];
    $changed = false;
    foreach (AI_PRODUCT_FIELDS as $field) {
        $before[$field] = (string) ($old[$field] ?? '');
        $changed = $changed || (array_key_exists($field, $new) && (string) ($new[$field] ?? '') !== $before[$field]);
    }
    if (!$changed || implode('', $before) === '') {
        return;
    }
    db_run('INSERT INTO product_seo_history (product_id, fields, replaced_by, admin_name) VALUES (?, ?, ?, ?)',
        [$productId, json_encode($before, JSON_UNESCAPED_UNICODE), $by, str_limit($who, 120) ?: null]);
    $keep = array_column(db_all('SELECT id FROM product_seo_history WHERE product_id = ? ORDER BY id DESC LIMIT 15', [$productId]), 'id');
    db_run('DELETE FROM product_seo_history WHERE product_id = ? AND id NOT IN (' . implode(',', array_map('intval', $keep)) . ')', [$productId]);
}

/** Earlier versions of a cake's SEO text, newest first, each with its fields decoded. */
function seo_history(int $productId): array
{
    try {
        $rows = db_all('SELECT * FROM product_seo_history WHERE product_id = ? ORDER BY id DESC', [$productId]);
    } catch (PDOException) {
        return [];
    }
    foreach ($rows as &$row) {
        $row['fields'] = json_decode((string) $row['fields'], true) ?: [];
    }
    return $rows;
}
