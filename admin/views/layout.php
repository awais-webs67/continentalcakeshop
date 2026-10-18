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
        ['coupons', 'Coupons', 'tag', null],
    ],
    'Catalogue' => [
        ['products', 'Products', 'cake', null],
        ['categories', 'Categories', 'menu', null],
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
<aside class="sidebar" data-sidebar>
    <a class="sidebar__brand" href="<?= e(admin_url()) ?>">
        <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="40" height="40">
        <span><strong><?= e(setting('store_short_name')) ?></strong><small>Admin panel</small></span>
    </a>
    <nav class="sidebar__nav" aria-label="Admin">
        <?php foreach ($nav as $group => $links): ?>
            <p class="sidebar__group"><?= e($group) ?></p>
            <?php foreach ($links as [$path, $label, $iconName, $count]): ?>
                <a href="<?= e(admin_url($path)) ?>" class="<?= $section === $path ? 'is-active' : '' ?>">
                    <?= icon($iconName, '', 19) ?>
                    <span><?= e($label) ?></span>
                    <?php if ($count): ?><em><?= (int) $count ?></em><?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    <a class="sidebar__site" href="<?= e(url()) ?>" target="_blank" rel="noopener"><?= icon('arrow', '', 16) ?> View website</a>
</aside>
<div class="sidebar-overlay" data-sidebar-close></div>

<div class="admin-main">
    <header class="topbar">
        <button class="topbar__menu" type="button" data-sidebar-open aria-label="Open menu"><?= icon('menu', '', 22) ?></button>
        <form class="topbar__search" method="get" action="<?= e(admin_url('orders')) ?>">
            <?= icon('search', '', 18) ?>
            <input type="search" name="q" placeholder="Search orders by number, name, email or phone" value="<?= e((string) ($_GET['q'] ?? '')) ?>">
        </form>
        <div class="topbar__user">
            <a href="<?= e(admin_url('account')) ?>" class="topbar__avatar" title="My account"><?= e(mb_strtoupper(mb_substr($admin['name'], 0, 1))) ?></a>
            <div class="topbar__who"><strong><?= e($admin['name']) ?></strong><span><?= e(ADMIN_ROLES[$admin['role']]) ?></span></div>
            <form method="post" action="<?= e(admin_url('logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--ghost btn--sm">Log out</button>
            </form>
        </div>
    </header>

    <?php $flashes = take_flashes(); if ($flashes): ?>
        <div class="toast-stack">
            <?php foreach ($flashes as $flash): ?>
                <div class="toast toast--<?= e($flash['type']) ?>" role="status"><span><?= e($flash['message']) ?></span><button type="button" data-toast-close aria-label="Dismiss"><?= icon('close', '', 16) ?></button></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <main class="admin-content">
        <?= $content ?>
    </main>
</div>
<script>window.CCS_ADMIN = <?= json_encode([
    'quillJs'   => asset('vendor/quill/quill.js'),
    'quillCss'  => asset('vendor/quill/quill.snow.css'),
    'uploadUrl' => admin_url('upload-image'),
    'csrf'      => csrf_token(),
], JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
