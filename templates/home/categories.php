<?php
// Category tiles: the first category gets the large tile; a custom cakes tile closes the grid.
$photos = get_gallery(12);
$tiles = array_map(static fn ($cat) => [
    'name'  => $cat['name'],
    'meta'  => ($cat['price_from'] !== null ? 'From ' . money($cat['price_from']) . ' · ' : '') . (int) $cat['product_count'] . ' cakes',
    'link'  => category_url($cat),
    'image' => $cat['image'] ?: $cat['cover_image'],
    'alt'   => ($cat['image'] && !empty($cat['image_alt'])) ? $cat['image_alt'] : $cat['name'] . ' from ' . setting('store_short_name'),
], get_categories());
$tiles[] = [
    'name'  => 'Custom Cakes',
    'meta'  => 'Birthdays, weddings and baby showers',
    'link'  => url('custom-cakes'),
    'image' => ($photos[9] ?? $photos[0] ?? null)['image'] ?? null,
    'alt'   => 'Custom cake by ' . setting('store_short_name'),
];
?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <h2 class="section-title"><?= e(setting('home_categories_title')) ?></h2>
                <?php if (setting('home_categories_text') !== ''): ?><p class="section-sub"><?= e(setting('home_categories_text')) ?></p><?php endif; ?>
            </div>
            <a class="link-arrow" href="<?= e(url('shop')) ?>">View all cakes <?= icon('arrow', '', 18) ?></a>
        </div>
        <div class="tile-grid">
            <?php foreach ($tiles as $i => $tile): ?>
                <a class="tile <?= $i === 0 ? 'tile--feature' : '' ?>" href="<?= e($tile['link']) ?>">
                    <img src="<?= e(product_image_url($tile['image'])) ?>" alt="<?= e($tile['alt']) ?>" width="1000" height="800" loading="lazy" decoding="async">
                    <span class="tile__body">
                        <span class="tile__name"><?= e($tile['name']) ?></span>
                        <span class="tile__meta"><?= e($tile['meta']) ?></span>
                    </span>
                    <span class="tile__arrow" aria-hidden="true"><?= icon('arrow', '', 20) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
