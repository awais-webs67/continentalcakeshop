<?php
/** @var string $content @var string $title @var array $admin */
$badges = admin_badges();
$route = trim((string) ($_GET['route'] ?? ''), '/');
$section = explode('/', $route)[0];
$nav = [
    'Shop' => [
        ['', 'Dashboard', 'store', null],
        ['orders', 'Orders', 'bag', $badges['orders'] + $badges['payments']],
        ['pickups', 'Pickups & deliveries', 'calendar', null],
        ['custom-requests', 'Custom requests', 'gift', $badges['custom']],
        ['messages', 'Messages', 'mail', $badges['messages']],
        ['customers', 'Customers', 'user', null],
        ['reports', 'Sales reports', 'chart', null],
        ['coupons', 'Coupons', 'tag', null],
    ],
    'Catalogue' => [
        ['products', 'Products', 'cake', null],
        ['categories', 'Categories', 'menu', null],
        ['addons', 'Extras', 'gift', null],
        ['gallery', 'Gallery', 'upload', null],
        ['reviews', 'Reviews', 'star', null],
    ],
    'Website' => [
        ['homepage', 'Homepage', 'store', null],
        ['popup', 'Promo popup', 'megaphone', null],
        ['pages', 'Policy pages', 'info', null],
        ['faqs', 'FAQs', 'search', null],
        ['settings', 'Settings', 'pen', null],
    ],
];
// Hide links the account may not open.
foreach ($nav as $group => $links) {
    $nav[$group] = array_values(array_filter($links, static fn ($link) => ($section = admin_route_section($link[0])) === null || admin_can($section, $admin)));
    if (!$nav[$group]) {
        unset($nav[$group]);
    }
}
if ($admin['role'] === 'owner') {
    $nav['Website'][] = ['admins', 'Staff accounts', 'user', null];
    $nav['Website'][] = ['system', 'System check', 'check', null];
}
?>
<!doctype html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Admin') ?> | <?= e(setting('store_short_name')) ?> Admin</title>
<link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin">
<?php
// Things waiting for attention, for the bell and the badges.
$alerts = array_values(array_filter([
    admin_can('orders', $admin) && $badges['orders'] ? [admin_url('orders', ['status' => 'new']), 'bag', $badges['orders'] . ' new order' . ($badges['orders'] === 1 ? '' : 's') . ' or deliveries to schedule'] : null,
    admin_can('orders', $admin) && $badges['payments'] ? [admin_url('orders', ['payment' => 'awaiting_verification']), 'card', $badges['payments'] . ' payment' . ($badges['payments'] === 1 ? '' : 's') . ' to check'] : null,
    admin_can('messages', $admin) && $badges['messages'] ? [admin_url('messages', ['filter' => 'unread']), 'mail', $badges['messages'] . ' unread message' . ($badges['messages'] === 1 ? '' : 's')] : null,
    admin_can('custom', $admin) && $badges['custom'] ? [admin_url('custom-requests', ['status' => 'new']), 'gift', $badges['custom'] . ' new custom request' . ($badges['custom'] === 1 ? '' : 's')] : null,
]));
$quickAdd = array_values(array_filter([
    admin_can('orders', $admin) ? [admin_url('orders/new'), 'bag', 'Phone or shop order'] : null,
    admin_can('products', $admin) ? [admin_url('products/edit'), 'cake', 'Product'] : null,
    admin_can('products', $admin) ? [admin_url('products/import'), 'upload', 'Import products'] : null,
    admin_can('coupons', $admin) ? [admin_url('coupons') . '#coupon-form', 'tag', 'Coupon'] : null,
    admin_can('products', $admin) ? [admin_url('categories/edit'), 'menu', 'Category'] : null,
    admin_can('gallery', $admin) ? [admin_url('gallery'), 'upload', 'Gallery photo'] : null,
]));
$bottomNav = array_values(array_filter([
    ['', 'Home', 'store', 0],
    admin_can('orders', $admin) ? ['orders', 'Orders', 'bag', $badges['orders'] + $badges['payments']] : null,
    admin_can('messages', $admin) ? ['messages', 'Messages', 'mail', $badges['messages']] : null,
    admin_can('products', $admin) ? ['products', 'Products', 'cake', 0] : (admin_can('custom', $admin) ? ['custom-requests', 'Requests', 'gift', $badges['custom']] : null),
]));
$alertCount = array_sum(array_map(static fn ($a) => (int) $a[2], $alerts));
?>
<aside class="sidebar" data-sidebar>
    <div class="sidebar__top">
        <a class="sidebar__brand" href="<?= e(admin_url()) ?>">
            <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="42" height="42">
            <span><strong><?= e(setting('store_short_name')) ?></strong><small>Admin panel</small></span>
        </a>
        <button class="sidebar__close" type="button" data-sidebar-close aria-label="Close menu"><?= icon('close', '', 20) ?></button>
    </div>
    <nav class="sidebar__nav" aria-label="Admin">
        <?php foreach ($nav as $group => $links): ?>
            <p class="sidebar__group"><?= e($group) ?></p>
            <?php foreach ($links as [$path, $label, $iconName, $count]): ?>
                <a href="<?= e(admin_url($path)) ?>" class="<?= $section === $path ? 'is-active' : '' ?>"<?= $section === $path ? ' aria-current="page"' : '' ?>>
                    <span class="sidebar__icon"><?= icon($iconName, '', 18) ?></span>
                    <span><?= e($label) ?></span>
                    <?php if ($count): ?><em><?= (int) $count ?></em><?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar__foot">
        <a class="sidebar__site" href="<?= e(url()) ?>" target="_blank" rel="noopener"><?= icon('store', '', 17) ?> <span>View website</span> <?= icon('arrow', 'sidebar__site-arrow', 15) ?></a>
        <div class="sidebar__user">
            <a class="sidebar__avatar" href="<?= e(admin_url('account')) ?>" style="background:<?= e(avatar_colour($admin['name'])) ?>"><?= e(mb_strtoupper(mb_substr($admin['name'], 0, 1))) ?></a>
            <a class="sidebar__who" href="<?= e(admin_url('account')) ?>"><strong><?= e($admin['name']) ?></strong><span><?= e(ADMIN_ROLES[$admin['role']]) ?></span></a>
            <form method="post" action="<?= e(admin_url('logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="icon-button icon-button--dark" title="Log out" aria-label="Log out"><?= icon('logout', '', 18) ?></button>
            </form>
        </div>
    </div>
</aside>
<div class="sidebar-overlay" data-sidebar-close></div>

<div class="admin-main">
    <header class="topbar">
        <button class="icon-button topbar__menu" type="button" data-sidebar-open aria-label="Open menu"><?= icon('menu', '', 22) ?></button>
        <a class="topbar__brand" href="<?= e(admin_url()) ?>"><img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="34" height="34"><span><?= e($title ?? 'Admin') ?></span></a>
        <?php if (admin_can('orders', $admin)): ?>
        <form class="topbar__search" method="get" action="<?= e(admin_url('orders')) ?>" role="search">
            <?= icon('search', '', 18) ?>
            <input type="search" name="q" placeholder="Search orders, customers, phone..." value="<?= e((string) ($_GET['q'] ?? '')) ?>" aria-label="Search orders" data-search-input>
            <kbd>/</kbd>
        </form>
        <?php endif; ?>
        <div class="topbar__actions">
            <?php if ($quickAdd): ?>
            <details class="dropdown">
                <summary class="btn btn--primary btn--sm topbar__new"><?= icon('plus', '', 17) ?> <span>New</span></summary>
                <div class="dropdown__panel">
                    <p class="dropdown__title">Add new</p>
                    <?php foreach ($quickAdd as [$link, $iconName, $label]): ?>
                        <a href="<?= e($link) ?>"><?= icon($iconName, '', 17) ?> <?= e($label) ?></a>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php endif; ?>
            <details class="dropdown">
                <summary class="icon-button" aria-label="Notifications"><?= icon('bell', '', 20) ?><?php if ($alertCount): ?><span class="icon-button__badge"><?= $alertCount > 99 ? '99+' : $alertCount ?></span><?php endif; ?></summary>
                <div class="dropdown__panel dropdown__panel--wide">
                    <p class="dropdown__title">Needs attention</p>
                    <?php if (!$alerts): ?><p class="dropdown__empty"><?= icon('check', '', 18) ?> You are all caught up.</p><?php endif; ?>
                    <?php foreach ($alerts as [$link, $iconName, $label]): ?>
                        <a href="<?= e($link) ?>"><span class="dropdown__icon"><?= icon($iconName, '', 17) ?></span> <?= e($label) ?></a>
                    <?php endforeach; ?>
                </div>
            </details>
            <a class="icon-button topbar__site" href="<?= e(url()) ?>" target="_blank" rel="noopener" title="View website" aria-label="View website"><?= icon('store', '', 20) ?></a>
            <details class="dropdown">
                <summary class="topbar__avatar" style="background:<?= e(avatar_colour($admin['name'])) ?>" aria-label="Account menu"><?= e(mb_strtoupper(mb_substr($admin['name'], 0, 1))) ?></summary>
                <div class="dropdown__panel">
                    <p class="dropdown__title"><?= e($admin['name']) ?><small><?= e($admin['email']) ?></small></p>
                    <a href="<?= e(admin_url('account')) ?>"><?= icon('user', '', 17) ?> My account</a>
                    <?php if ($admin['role'] === 'owner'): ?><a href="<?= e(admin_url('admins')) ?>"><?= icon('user', '', 17) ?> Staff accounts</a><?php endif; ?>
                    <form method="post" action="<?= e(admin_url('logout')) ?>"><?= csrf_field() ?><button type="submit"><?= icon('logout', '', 17) ?> Log out</button></form>
                </div>
            </details>
        </div>
    </header>

    <?php $flashes = take_flashes(); if ($flashes): ?>
        <div class="toast-stack">
            <?php foreach ($flashes as $flash): ?>
                <div class="toast toast--<?= e($flash['type']) ?>" role="status"><?= icon($flash['type'] === 'error' ? 'info' : 'check', 'toast__icon', 18) ?><span><?= e($flash['message']) ?></span><button type="button" data-toast-close aria-label="Dismiss"><?= icon('close', '', 16) ?></button></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <main class="admin-content">
        <?= $content ?>
    </main>
</div>

<nav class="bottom-nav" aria-label="Quick links">
    <?php foreach ($bottomNav as [$path, $label, $iconName, $count]): ?>
        <a href="<?= e(admin_url($path)) ?>" class="<?= $section === $path ? 'is-active' : '' ?>"><span class="bottom-nav__icon"><?= icon($iconName, '', 22) ?><?php if ($count): ?><em><?= (int) $count ?></em><?php endif; ?></span><span><?= e($label) ?></span></a>
    <?php endforeach; ?>
    <button type="button" data-sidebar-open><span class="bottom-nav__icon"><?= icon('menu', '', 22) ?></span><span>Menu</span></button>
</nav>
<script>window.CCS_ADMIN = <?= json_encode([
    'quillJs'   => asset('vendor/quill/quill.js'),
    'quillCss'  => asset('vendor/quill/quill.snow.css'),
    'uploadUrl' => admin_url('upload-image'),
    'csrf'      => csrf_token(),
], JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
