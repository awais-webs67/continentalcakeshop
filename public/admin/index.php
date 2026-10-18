<?php
declare(strict_types=1);

// Admin panel front controller: /admin/{route} -> admin/controllers/{file}.php

require dirname(__DIR__, 2) . '/src/bootstrap.php';
require BASE_PATH . '/admin/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$route = trim((string) ($_GET['route'] ?? ''), '/');

const ADMIN_ROUTES = [
    ''                     => 'dashboard',
    'login'                => 'login',
    'logout'               => 'logout',
    'setup'                => 'setup',
    'orders'               => 'orders',
    'orders/view'          => 'order-view',
    'coupons'              => 'coupons',
    'upload-image'         => 'upload-image',
    'homepage'             => 'homepage',
    'popup'                => 'popup',
    'orders/print'         => 'order-print',
    'pickups'              => 'pickups',
    'products'             => 'products',
    'products/edit'        => 'product-edit',
    'categories'           => 'categories',
    'categories/edit'      => 'category-edit',
    'custom-requests'      => 'custom-requests',
    'custom-requests/view' => 'custom-request-view',
    'messages'             => 'messages',
    'customers'            => 'customers',
    'customers/view'       => 'customer-view',
    'reviews'              => 'reviews',
    'gallery'              => 'gallery',
    'pages'                => 'pages',
    'pages/edit'           => 'page-edit',
    'faqs'                 => 'faqs',
    'settings'             => 'settings',
    'admins'               => 'admins',
    'account'              => 'account',
    'file'                 => 'file',
    'system'               => 'system',
];

$controller = ADMIN_ROUTES[$route] ?? null;
if ($controller === null) {
    http_response_code(404);
    require_admin();
    admin_view('not-found', ['title' => 'Page not found']);
}

// First run: no admin accounts yet, so send everyone to the setup page.
if ($controller !== 'setup' && admin_count() === 0) {
    redirect(admin_url('setup'));
}

if (is_post()) {
    verify_csrf();
}

require BASE_PATH . '/admin/controllers/' . $controller . '.php';
