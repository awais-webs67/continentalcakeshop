<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "User-agent: *\n";
echo "Disallow: /cart\n";
echo "Disallow: /checkout\n";
echo "Disallow: /order/\n";
echo "Disallow: /api/\n";
echo "Disallow: /admin/\n";
echo "\nSitemap: " . url('sitemap.xml') . "\n";
