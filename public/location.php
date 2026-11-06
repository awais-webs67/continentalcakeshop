<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// One page per shop (/locations/{slug}): what Google shows for "cake shop {suburb}" searches and
// where each branch's Google Business Profile links to. Address, phone, hours, map, popular
// cakes and a button to order for pickup there. Hidden branches are not shown.

$branch = branch_by_slug(input('slug'));
if (!$branch || !(int) $branch['is_active']) {
    not_found();
}

// "Order for pickup here": remembers the shop for the checkout, then opens the menu.
if (is_post()) {
    verify_csrf();
    remember_branch((int) $branch['id']);
    redirect(input('to') === 'custom-cakes' ? 'custom-cakes#quote' : 'shop');
}

$store = setting('store_name');
$suburb = $branch['suburb'];
$address = branch_address_line($branch);
$phone = branch_phone($branch);
$others = array_values(array_filter(active_branches(), static fn ($b) => (int) $b['id'] !== (int) $branch['id']));
$featured = with_card_photos(get_featured_products(8));
$todayKey = day_key(new DateTime());
$intro = trim((string) $branch['intro']);
$photo = !empty($branch['photo']) ? product_image_url($branch['photo']) : ((int) $branch['is_main'] ? shop_photo()['src'] : null);

$crumbs = [['Home', url()], ['Contact', url('contact')], [$suburb . ' shop', branch_url($branch)]];
$page = [
    'title'       => 'Cake Shop ' . $suburb . ' Canberra | ' . setting('store_short_name'),
    'description' => str_limit(setting('store_short_name') . ' ' . $suburb . ': ' . $address . '. Fresh and custom cakes, 100% Halal. Order online for pickup' . (setting_bool('delivery_enabled') ? ' or delivery' : '') . '.', 160),
    'canonical'   => branch_url($branch),
    'schema'      => [breadcrumb_schema($crumbs)],
];
if (!(int) $branch['is_main']) {
    $page['schema'][] = branch_business_schema($branch);
}
if ($photo) {
    $page['image'] = $photo;
}
render('header', compact('page'));
?>

<section class="page-hero">
    <div class="container">
        <?php render('partials/breadcrumbs', compact('crumbs')); ?>
        <h1 class="page-title"><span class="kicker"><?= e(setting('store_short_name')) ?></span> Cake shop in <?= e($suburb) ?>, Canberra</h1>
        <p class="page-lead">Fresh cakes, custom birthday and wedding cakes, made with 100% Halal ingredients at our <?= e($suburb) ?> shop. Order online and pick up here<?= setting_bool('delivery_enabled') ? ', or we deliver across Canberra' : '' ?>.</p>
        <div class="page-hero__actions">
            <form method="post" action="<?= e(branch_url($branch)) ?>">
                <?= csrf_field() ?>
                <button class="btn btn--primary" type="submit"><?= icon('bag', '', 18) ?> Order for pickup in <?= e($suburb) ?></button>
            </form>
            <a class="btn btn--outline" href="<?= e(branch_maps_url($branch)) ?>" target="_blank" rel="noopener"><?= icon('pin', '', 18) ?> Get Directions</a>
        </div>
    </div>
</section>

<section class="section section--compact">
    <div class="container">
        <div class="location-grid">
            <div class="info-card location-visit">
                <h2>Visit the <?= e($suburb) ?> shop</h2>
                <ul class="location-facts">
                    <li><?= icon('pin', '', 20) ?><span><strong>Address</strong><a href="<?= e(branch_maps_url($branch)) ?>" target="_blank" rel="noopener"><?= e($address) ?></a></span></li>
                    <li><?= icon('phone', '', 20) ?><span><strong>Phone</strong><a href="tel:<?= e(phone_e164($phone)) ?>"><?= e($phone) ?></a></span></li>
                    <?php if (($shopEmail = branch_email_shown($branch)) !== ''): ?><li><?= icon('mail', '', 20) ?><span><strong>Email</strong><a href="mailto:<?= e($shopEmail) ?>"><?= e($shopEmail) ?></a></span></li><?php endif; ?>
                </ul>
                <table class="hours-table">
                    <caption class="sr-only">Opening hours of the <?= e($suburb) ?> shop</caption>
                    <tbody>
                        <?php foreach (opening_hours() as $day => $slot): ?>
                            <tr <?= $day === $todayKey ? 'class="is-today"' : '' ?>>
                                <th scope="row"><?= e(DAY_NAMES[$day]) ?><?= $day === $todayKey ? ' <span>Today</span>' : '' ?></th>
                                <td><?= $slot ? e(format_time($slot[0]) . ' to ' . format_time($slot[1])) : 'Closed' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="location-media">
                <?php if ($photo): ?>
                    <figure class="location-photo"><img src="<?= e($photo) ?>" alt="<?= e($store . ' shop in ' . $suburb . ', Canberra') ?>" width="1200" height="900" loading="lazy" decoding="async"></figure>
                <?php endif; ?>
                <div class="visit__map location-map">
                    <?php // The main shop is found on Google by its name; other shops by their street address. ?>
                    <iframe title="Map showing <?= e($store . ' in ' . $suburb) ?>" src="https://www.google.com/maps?q=<?= e(rawurlencode((int) $branch['is_main'] ? $store . ', ' . $address : $address)) ?>&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>

        <div class="location-about">
            <div class="prose">
                <h2>About our <?= e($suburb) ?> shop</h2>
                <?php if ($intro !== ''): ?>
                    <?= render_content($intro) ?>
                <?php endif; ?>
                <p>Every cake on our menu is made with 100% Halal ingredients: tortes, velvet cakes, mud cakes, baked and continental cheesecakes, and tea cakes. Order online, choose <?= e($suburb) ?> at checkout and pick up at the time you chose. For birthdays, weddings and other celebrations we also make custom cakes.</p>
            </div>
            <form method="post" action="<?= e(branch_url($branch)) ?>" class="location-about__cta">
                <?= csrf_field() ?><input type="hidden" name="to" value="custom-cakes">
                <button class="btn btn--outline" type="submit"><?= icon('gift', '', 18) ?> Ask for a custom cake here</button>
            </form>
        </div>
    </div>
</section>

<?php if ($featured): ?>
<section class="section section--tint">
    <div class="container">
        <div class="section-head">
            <div>
                <h2 class="section-title">Popular cakes in <?= e($suburb) ?></h2>
                <p class="section-sub">Order online and pick up from our <?= e($suburb) ?> shop.</p>
            </div>
            <a class="link-arrow" href="<?= e(url('shop')) ?>">See all cakes <?= icon('arrow', '', 18) ?></a>
        </div>
        <div class="product-grid">
            <?php foreach ($featured as $product): ?>
                <?php render('partials/product-card', compact('product')); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($others): ?>
<section class="section section--compact">
    <div class="container">
        <h2 class="section-title">Our other shop<?= count($others) > 1 ? 's' : '' ?></h2>
        <div class="location-others">
            <?php foreach ($others as $other): ?>
                <a class="shop-card location-other" href="<?= e(branch_url($other)) ?>">
                    <span class="shop-card__name"><?= icon('store', '', 20) ?> <?= e($other['name']) ?> shop</span>
                    <span class="shop-card__addr"><?= e(branch_address_line($other)) ?></span>
                    <span class="link-arrow">Cake shop in <?= e($other['suburb']) ?> <?= icon('arrow', '', 16) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php render('footer'); ?>
