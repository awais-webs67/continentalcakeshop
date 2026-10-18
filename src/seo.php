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
        'areaServed' => [
            ['@type' => 'City', 'name' => 'Canberra'],
            ['@type' => 'City', 'name' => 'Queanbeyan'],
        ],
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

function product_schema(array $product, array $sizes): array
{
    $prices = array_map(static fn ($s) => (float) $s['price'], $sizes);
    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        'name'        => $product['name'],
        'description' => $product['meta_description'] ?: ($product['description'] ? content_text($product['description']) : $product['short_description']),
        'image'       => [product_image_url($product['image'])],
        'sku'         => 'CCS-' . $product['id'],
        'category'    => $product['category_name'],
        'brand'       => ['@type' => 'Brand', 'name' => setting('store_short_name')],
    ];
    if ($sizes && empty($product['is_enquiry'])) {
        $schema['offers'] = [
            '@type'         => 'AggregateOffer',
            'priceCurrency' => 'AUD',
            'lowPrice'      => number_format(min($prices), 2, '.', ''),
            'highPrice'     => number_format(max($prices), 2, '.', ''),
            'offerCount'    => count($sizes),
            'availability'  => 'https://schema.org/InStock',
            'url'           => product_url($product),
            'seller'        => ['@id' => url('#bakery')],
        ];
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
    'copy-of-birthday-gallery'           => 'custom-cakes',
    'copy-of-wedding-baby-gallery'       => 'custom-cakes',
    'gallery-06'                         => 'custom-cakes',
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
    return preg_match('#^https?://#i', $target) ? $target : url($target);
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
