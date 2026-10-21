<?php
// Router for PHP's built-in server during local development only:
//   php -S localhost:8000 -t public public/router.php
// On the live site Apache uses public/.htaccess instead.

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if ($path !== '/' && is_file(__DIR__ . $path) && !str_ends_with($path, '.php')) {
    return false;
}

if (preg_match('#^/admin(?:/(.*))?$#', $path, $m) && !is_file(__DIR__ . $path)) {
    $_GET['route'] = $m[1] ?? '';
    chdir(__DIR__ . '/admin');
    require __DIR__ . '/admin/index.php';
    return true;
}

$routes = [
    '#^/$#'                         => ['index.php', []],
    '#^/shop$#'                     => ['shop.php', []],
    '#^/shop/([a-z0-9-]+)$#'        => ['shop.php', ['category']],
    '#^/cakes/([a-z0-9-]+)$#'       => ['product.php', ['slug']],
    '#^/order/([A-Za-z0-9-]+)$#'    => ['order.php', ['number']],
    '#^/messages/([a-f0-9]{32})$#'  => ['messages.php', ['token']],
    '#^/quote/([a-f0-9]{32})$#'     => ['quote.php', ['token']],
    '#^/cart$#'                     => ['cart.php', []],
    '#^/checkout$#'                 => ['checkout.php', []],
    '#^/custom-cakes$#'             => ['custom-cakes.php', []],
    '#^/about$#'                    => ['about.php', []],
    '#^/contact$#'                  => ['contact.php', []],
    '#^/login$#'                    => ['login.php', []],
    '#^/register$#'                 => ['register.php', []],
    '#^/logout$#'                   => ['logout.php', []],
    '#^/account$#'                  => ['account.php', []],
    '#^/forgot-password$#'          => ['forgot-password.php', []],
    '#^/reset-password$#'           => ['reset-password.php', []],
    '#^/faq$#'                      => ['faq.php', []],
    '#^/(privacy-policy|terms|refund-policy|allergens)$#' => ['page.php', ['slug']],
    '#^/api/slots$#'                => ['api/slots.php', []],
    '#^/api/upload$#'               => ['api/upload.php', []],
    '#^/api/coupon$#'               => ['api/coupon.php', []],
    '#^/stripe-webhook$#'           => ['stripe-webhook.php', []],
    '#^/sitemap\.xml$#'             => ['sitemap.php', []],
    '#^/robots\.txt$#'              => ['robots.php', []],
];

foreach ($routes as $pattern => [$file, $params]) {
    if (preg_match($pattern, $path, $m)) {
        foreach ($params as $i => $name) {
            $_GET[$name] = $m[$i + 1];
        }
        chdir(dirname(__DIR__ . '/' . $file));
        require __DIR__ . '/' . $file;
        return true;
    }
}

require __DIR__ . '/404.php';
