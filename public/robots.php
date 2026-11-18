<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// Lets search engines read the shop, products and pages, and keeps them out of private
// or duplicate pages (cart, checkout, accounts, search results, admin).

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$base = rtrim((string) parse_url(url(), PHP_URL_PATH), '/');
$private = ['/admin/', '/api/', '/cart', '/checkout', '/order/', '/quote/', '/account', '/login', '/register', '/logout',
            '/forgot-password', '/reset-password', '/messages/', '/stripe-webhook'];

echo "User-agent: *\n";
echo "Allow: {$base}/\n";
foreach ($private as $path) {
    echo "Disallow: {$base}{$path}\n";
}
// Search and sort versions of the shop repeat the same products.
echo "Disallow: {$base}/shop?q=\n";
echo "Disallow: {$base}/*?sort=\n";
echo "Disallow: {$base}/*&sort=\n\n";

// Explicitly welcome search and AI search citability crawlers on public menu and occasion pages.
foreach (['Googlebot', 'Bingbot', 'OAI-SearchBot', 'Claude-SearchBot', 'PerplexityBot', 'Applebot'] as $bot) {
    echo "User-agent: {$bot}\n";
    echo "Allow: {$base}/\n";
    foreach ($private as $path) {
        echo "Disallow: {$base}{$path}\n";
    }
    echo "Disallow: {$base}/shop?q=\n";
    echo "Disallow: {$base}/*?sort=\n";
    echo "Disallow: {$base}/*&sort=\n\n";
}

echo "Sitemap: " . url('sitemap.xml') . "\n";
