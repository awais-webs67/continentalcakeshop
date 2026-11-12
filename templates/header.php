<?php
/** @var array $page */
$page = page_meta($page ?? []);
$menu = nav_menu(); // Admin > Menu
$cartCount = cart_count();
$user = current_user();
$phone = setting('store_phone');
$phoneHref = 'tel:' . setting('store_phone_e164');
$target = static fn (array $item): string => $item['new_tab'] ? ' target="_blank" rel="noopener"' : '';
$current = static fn (array $item): string => $item['active'] ? ' aria-current="page"' : '';
?>
<!doctype html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>document.documentElement.classList.add('js');</script>
<title><?= e($page['title']) ?></title>
<meta name="description" content="<?= e($page['description']) ?>">
<?php if ($page['noindex']): ?>
<meta name="robots" content="noindex, nofollow">
<?php else: ?>
<link rel="canonical" href="<?= e($page['canonical']) ?>">
<?php if ($page['robots'] !== ''): ?><meta name="robots" content="<?= e($page['robots']) ?>">
<?php endif; ?>
<?php endif; ?>
<meta property="og:type" content="<?= e($page['type']) ?>">
<meta property="og:site_name" content="<?= e(setting('store_name')) ?>">
<meta property="og:title" content="<?= e($page['title']) ?>">
<meta property="og:description" content="<?= e($page['description']) ?>">
<meta property="og:url" content="<?= e($page['canonical']) ?>">
<meta property="og:image" content="<?= e($page['image']) ?>">
<meta property="og:locale" content="en_AU">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($page['title']) ?>">
<meta name="twitter:description" content="<?= e($page['description']) ?>">
<meta name="twitter:image" content="<?= e($page['image']) ?>">
<meta name="geo.region" content="AU-ACT">
<meta name="geo.placename" content="<?= e(setting('store_suburb', 'Canberra')) ?>, Canberra">
<meta name="theme-color" content="#1E9B82">
<link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&text=THECONTINALKSPBR&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<?= analytics_tags() ?>
<?= json_ld(local_business_schema()) ?>
<?php foreach ($page['schema'] as $schema): ?>
<?= json_ld($schema) ?>
<?php endforeach; ?>
</head>
<body class="<?= e($page['body_class']) ?>">
<?php if (maintenance_on()): // only staff get this far while it is on ?>
<div style="position:sticky;top:0;z-index:200;padding:8px 16px;background:#FFF4DB;color:#8A5A00;font:600 14px/1.4 system-ui,sans-serif;text-align:center">Maintenance mode is on: customers see the "back very soon" page. You see the website because you are logged in to the admin panel.</div>
<?php endif; ?>
<a class="skip-link" href="#main">Skip to content</a>

<?php $announcement = ordering_paused() ? ordering_paused_message() : setting('announcement_text'); ?>
<div class="topbar<?= $announcement === '' ? ' topbar--no-text' : '' ?><?= ordering_paused() ? ' topbar--paused' : '' ?>">
    <div class="container topbar__inner">
        <?php if ($announcement !== ''): ?><p class="topbar__text"><?= icon(ordering_paused() ? 'info' : 'truck', '', 17) ?> <?= e($announcement) ?></p><?php endif; ?>
        <div class="topbar__meta">
            <span><?= icon('clock', '', 16) ?> <?= e(today_hours_label()) ?></span>
            <a href="<?= e($phoneHref) ?>"><?= icon('phone', '', 16) ?> <?= e($phone) ?></a>
        </div>
    </div>
</div>

<header class="site-header" data-header>
    <div class="container site-header__inner">
        <button class="icon-btn nav-toggle" type="button" aria-controls="mobile-menu" aria-expanded="false" data-menu-open>
            <?= icon('menu', '', 26) ?>
            <span class="sr-only">Open menu</span>
        </button>

        <a class="brand" href="<?= e(url()) ?>" aria-label="<?= e(setting('store_name')) ?> home">
            <?php render('partials/logo'); ?>
        </a>

        <nav class="main-nav" aria-label="Main">
            <ul class="main-nav__list">
                <?php foreach ($menu as $item): ?>
                    <?php if (!$item['children']): ?>
                        <li><a href="<?= e($item['href']) ?>"<?= $current($item) . $target($item) ?>><?= e($item['label']) ?></a></li>
                    <?php else: ?>
                        <li class="has-dropdown">
                            <?php if ($item['href'] !== ''): ?>
                                <a href="<?= e($item['href']) ?>"<?= $current($item) . $target($item) ?>><?= e($item['label']) ?> <?= icon('down', 'dropdown-caret', 16) ?></a>
                            <?php else: ?>
                                <button type="button" class="main-nav__btn"<?= $item['active'] ? ' data-active' : '' ?>><?= e($item['label']) ?> <?= icon('down', 'dropdown-caret', 16) ?></button>
                            <?php endif; ?>
                            <div class="dropdown">
                                <ul>
                                    <?php foreach ($item['children'] as $child): ?>
                                        <li<?= $child['all'] ? ' class="dropdown__all"' : '' ?>><a href="<?= e($child['href']) ?>"<?= $current($child) . $target($child) ?>><?= e($child['label']) ?><?= $child['all'] ? ' ' . icon('arrow', '', 16) : '' ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="header-actions">
            <a class="icon-btn header-phone" href="<?= e($phoneHref) ?>" aria-label="Call us on <?= e($phone) ?>"><?= icon('phone', '', 22) ?></a>
            <?php if ($user): ?>
                <a class="icon-btn account-link" href="<?= e(url('account')) ?>" aria-label="My account"><?= icon('user', '', 23) ?></a>
            <?php else: ?>
                <a class="icon-btn account-link account-link--nudge" href="<?= e(url('login')) ?>" aria-label="Log in or create an account to save your order history">
                    <?= icon('user', '', 23) ?>
                    <span class="account-link__tip" aria-hidden="true">Log in to save your orders</span>
                </a>
            <?php endif; ?>
            <a class="icon-btn cart-link" href="<?= e(url('cart')) ?>" aria-label="Cart, <?= $cartCount ?> item<?= $cartCount === 1 ? '' : 's' ?>">
                <?= icon('bag', '', 24) ?>
                <span class="cart-count" data-cart-count <?= $cartCount ? '' : 'hidden' ?>><?= $cartCount ?></span>
            </a>
            <a class="btn btn--light btn--sm header-cta" href="<?= e(url('shop')) ?>">Order Online</a>
        </div>
    </div>
    <?= drip_svg('drip--header', 23) ?>
</header>

<div class="drawer" id="mobile-menu" data-drawer hidden>
    <div class="drawer__overlay" data-menu-close></div>
    <div class="drawer__panel" role="dialog" aria-modal="true" aria-label="Menu">
        <div class="drawer__head">
            <a class="drawer__brand" href="<?= e(url()) ?>">
                <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="48" height="48">
                <span><?= e(setting('store_short_name')) ?></span>
            </a>
            <button class="icon-btn drawer__close" type="button" data-menu-close>
                <?= icon('close', '', 24) ?>
                <span class="sr-only">Close menu</span>
            </button>
        </div>

        <nav class="drawer__nav" aria-label="Mobile">
            <?php foreach ($menu as $item): ?>
                <?php if (!$item['children']): ?>
                    <a href="<?= e($item['href']) ?>"<?= $current($item) . $target($item) ?>><?= e($item['label']) ?> <?= icon('chevron', '', 18) ?></a>
                <?php else: ?>
                    <?php // The parent's own page is offered first, unless a sub item already goes there.
                    $showParent = $item['href'] !== '' && !in_array($item['href'], array_column($item['children'], 'href'), true); ?>
                    <details class="drawer__group" <?= $item['active'] ? 'open' : '' ?>>
                        <summary><?= e($item['label']) ?> <?= icon('down', '', 18) ?></summary>
                        <div class="drawer__sub">
                            <?php if ($showParent): ?><a href="<?= e($item['href']) ?>"<?= $target($item) ?>><?= e($item['label']) ?></a><?php endif; ?>
                            <?php // On phones "All cakes" comes first, before the categories.
                            $drawerChildren = $item['children'];
                            usort($drawerChildren, static fn ($a, $b) => $b['all'] <=> $a['all']); ?>
                            <?php foreach ($drawerChildren as $child): ?>
                                <a href="<?= e($child['href']) ?>"<?= $current($child) . $target($child) ?>><?= e($child['all'] ? 'All cakes' : $child['label']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endif; ?>
            <?php endforeach; ?>
            <a href="<?= e(url('cart')) ?>">Cart<?= $cartCount ? ' (' . $cartCount . ')' : '' ?> <?= icon('chevron', '', 18) ?></a>
            <?php if ($user): ?>
                <a href="<?= e(url('account')) ?>">My Account <?= icon('chevron', '', 18) ?></a>
            <?php else: ?>
                <a href="<?= e(url('login')) ?>">Log In / Create Account <?= icon('chevron', '', 18) ?></a>
            <?php endif; ?>
        </nav>

        <div class="drawer__foot">
            <a class="btn btn--primary btn--block" href="<?= e(url('shop')) ?>"><?= icon('bag', '', 18) ?> Order Online</a>
            <a class="btn btn--outline btn--block" href="<?= e($phoneHref) ?>"><?= icon('phone', '', 18) ?> Call <?= e($phone) ?></a>
            <p class="drawer__info"><?= icon('clock', '', 16) ?> <?= e(today_hours_label()) ?></p>
            <p class="drawer__info"><?= icon('pin', '', 16) ?> <?= e(store_address_line()) ?></p>
        </div>
    </div>
</div>

<main id="main">
<?php $flashes = take_flashes(); if ($flashes): ?>
    <div class="toast-stack" data-toasts>
        <?php foreach ($flashes as $flash): ?>
            <div class="toast alert alert--<?= e($flash['type']) ?>" role="status">
                <span><?= e($flash['message']) ?></span>
                <button type="button" class="toast__close" data-toast-close aria-label="Dismiss"><?= icon('close', '', 18) ?></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
