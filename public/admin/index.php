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
    'forgot-password'      => 'forgot-password',
    'reset-password'       => 'reset-password',
    'orders'               => 'orders',
    'orders/view'          => 'order-view',
    'orders/new'           => 'order-new',
    'coupons'              => 'coupons',
    'addons'               => 'addons',
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
    'messages/view'        => 'message-view',
    'reports'              => 'reports',
    'reports/export'       => 'reports-export',
    'products/import'      => 'products-import',
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

// Staff only see the parts of the panel the owner allowed.
$signedIn = current_admin();
if ($signedIn) {
    $section = admin_route_section($route);
    $blocked = ($section !== null && !admin_can($section, $signedIn))
        || (in_array($route, OWNER_ONLY_ROUTES, true) && $signedIn['role'] !== 'owner')
        // Private files (payment screenshots, cake photos) need access to orders, requests or messages.
        || ($route === 'file' && !array_intersect(['orders', 'custom', 'messages'], admin_permissions($signedIn)));
    if ($blocked) {
        flash('error', 'Your account does not have access to that page. Ask the owner if you need it.');
        redirect(admin_url());
    }
}

if (is_post()) {
    verify_csrf();
}

require BASE_PATH . '/admin/controllers/' . $controller . '.php';
