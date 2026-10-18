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
        'logo'       => url('assets/img/logo.svg'),
        'image'      => url('assets/img/shop-front.jpg'),
        'telephone'  => setting('store_phone_e164'),
        'email'      => setting('store_email'),
        'priceRange' => '$$',
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
        'areaServed' => ['Canberra', 'Tuggeranong', 'Queanbeyan'],
        'openingHoursSpecification' => $hours,
        'hasMap'     => store_maps_url(),
    ];
    if ($sameAs) {
        $schema['sameAs'] = $sameAs;
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
