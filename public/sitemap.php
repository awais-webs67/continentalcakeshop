<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// XML sitemap with the main pages, categories, products and policy pages, including
// their photos so they can appear in Google Images. Built from the database on every visit
// (sitemap_entries() in src/indexing.php), so new, hidden and deleted cakes show at once.

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$entries = sitemap_entries();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
foreach ($entries as [$loc, $updated, , , $images]) {
    echo '  <url><loc>' . e($loc) . '</loc>';
    if ($updated) {
        echo '<lastmod>' . date('Y-m-d', strtotime($updated)) . '</lastmod>';
    }
    foreach ($images as [$src]) {
        echo '<image:image><image:loc>' . e($src) . '</image:loc></image:image>';
    }
    echo "</url>\n";
}
echo "</urlset>\n";
