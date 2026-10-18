<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');

$entries = [
    [url(), null, '1.0'],
    [url('shop'), null, '0.9'],
    [url('custom-cakes'), null, '0.8'],
    [url('about'), null, '0.5'],
    [url('contact'), null, '0.6'],
    [url('faq'), null, '0.6'],
];
foreach (db_all('SELECT slug, updated_at FROM pages WHERE is_active = 1') as $policy) {
    $entries[] = [url($policy['slug']), $policy['updated_at'], '0.3'];
}
foreach (db_all('SELECT slug, updated_at FROM categories WHERE is_active = 1 ORDER BY sort_order') as $cat) {
    $entries[] = [url('shop/' . $cat['slug']), $cat['updated_at'], '0.8'];
}
foreach (get_products() as $product) {
    $entries[] = [product_url($product), $product['updated_at'], '0.7'];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($entries as [$loc, $updated, $priority]) {
    echo '  <url><loc>' . e($loc) . '</loc>';
    if ($updated) {
        echo '<lastmod>' . date('Y-m-d', strtotime($updated)) . '</lastmod>';
    }
    echo '<priority>' . $priority . "</priority></url>\n";
}
echo "</urlset>\n";
