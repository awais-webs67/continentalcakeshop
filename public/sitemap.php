<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// XML sitemap with the main pages, categories, products and policy pages, including
// their photos so they can appear in Google Images.

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$latestProduct = (string) db_value('SELECT MAX(updated_at) FROM products WHERE is_active = 1');
$photo = static fn (?string $image, string $title): ?array => $image ? [product_image_url($image), $title] : null;
$galleryPhotos = array_map(static fn ($g) => [product_image_url($g['image']), $g['caption']], get_gallery(30));

$entries = [
    [url(), $latestProduct, 'daily', '1.0', array_filter([$photo(setting('hero_image'), setting('hero_title'))])],
    [url('shop'), $latestProduct, 'weekly', '0.9', []],
    [url('custom-cakes'), null, 'weekly', '0.8', $galleryPhotos],
    [url('about'), null, 'monthly', '0.5', [[asset_url_plain('img/shop-front.jpg'), setting('store_name') . ' shop front']]],
    [url('contact'), null, 'monthly', '0.6', []],
    [url('faq'), null, 'monthly', '0.6', []],
];
foreach (db_all('SELECT slug, title, type, updated_at FROM pages WHERE is_active = 1') as $content) {
    $entries[] = $content['type'] === 'landing'
        ? [url($content['slug']), $content['updated_at'], 'weekly', '0.8', array_map(static fn ($g) => [product_image_url($g['image']), $g['caption']], gallery_for_occasion($content['occasion'] ?? null, 8))]
        : [url($content['slug']), $content['updated_at'], 'yearly', '0.3', []];
}
foreach (get_categories() as $cat) {
    $entries[] = [category_url($cat), $cat['updated_at'], 'weekly', '0.8', array_filter([$photo($cat['image'] ?: $cat['cover_image'], $cat['name'] . ' from ' . setting('store_short_name'))])];
}
$extraPhotos = [];
foreach (db_all('SELECT product_id, image, alt FROM product_images ORDER BY sort_order, id') as $row) {
    $extraPhotos[$row['product_id']][] = [product_image_url($row['image']), $row['alt'] ?: ''];
}
foreach (get_products() as $product) {
    $photos = array_merge(array_filter([$photo($product['image'], product_image_alt($product))]), array_map(
        static fn ($p) => [$p[0], $p[1] !== '' ? $p[1] : $product['name']], $extraPhotos[$product['id']] ?? []
    ));
    $entries[] = [product_url($product), $product['updated_at'], 'weekly', '0.7', $photos];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
foreach ($entries as [$loc, $updated, $freq, $priority, $images]) {
    echo '  <url><loc>' . e($loc) . '</loc>';
    if ($updated) {
        echo '<lastmod>' . date('Y-m-d', strtotime($updated)) . '</lastmod>';
    }
    echo '<changefreq>' . $freq . '</changefreq><priority>' . $priority . '</priority>';
    foreach ($images as [$src, $title]) {
        echo '<image:image><image:loc>' . e($src) . '</image:loc><image:title>' . e($title) . '</image:title></image:image>';
    }
    echo "</url>\n";
}
echo "</urlset>\n";
