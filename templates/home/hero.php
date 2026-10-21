<?php
// The hero photo is a setting so the admin can change it; it falls back to the first gallery photo.
$heroImage = setting('hero_image');
$heroCake = db_one('SELECT * FROM gallery_images WHERE image = ?', [$heroImage])
    ?? ($heroImage !== '' ? ['id' => null, 'image' => $heroImage, 'caption' => 'Celebration cake from ' . setting('store_short_name')] : (get_gallery(1)[0] ?? null));
?>
<section class="hero">
    <div class="container hero__inner">
        <div class="hero__copy">
            <?php // The small line above the title is part of the main heading, so Google reads "cake shop ... Canberra" in it. ?>
            <h1 class="hero__heading">
                <?php if (setting('hero_eyebrow') !== ''): ?><span class="kicker"><?= e(setting('hero_eyebrow')) ?></span><?php endif; ?>
                <span class="hero__title"><?= e(setting('hero_title')) ?></span>
            </h1>
            <?php if (setting('hero_subtitle') !== ''): ?><p class="hero__text"><?= e(setting('hero_subtitle')) ?></p><?php endif; ?>
            <div class="hero__actions">
                <?php if (setting('hero_btn1_text') !== ''): ?><a class="btn btn--primary btn--lg" href="<?= e(popup_link(setting('hero_btn1_url', 'shop'))) ?>"><?= e(setting('hero_btn1_text')) ?> <?= icon('arrow', '', 18) ?></a><?php endif; ?>
                <?php if (setting('hero_btn2_text') !== ''): ?><a class="btn btn--outline btn--lg" href="<?= e(popup_link(setting('hero_btn2_url', 'custom-cakes'))) ?>"><?= e(setting('hero_btn2_text')) ?></a><?php endif; ?>
            </div>
            <a class="hero__rating" href="<?= e(setting('google_reviews_url')) ?>" target="_blank" rel="noopener">
                <?= google_logo(18) ?>
                <?= rating_stars((float) setting('google_rating'), 16) ?>
                <span><strong><?= e(setting('google_rating')) ?></strong> from <?= e(setting('google_review_count')) ?> Google reviews</span>
            </a>
        </div>

        <div class="hero__media">
            <?php if ($heroCake): ?>
                <img class="hero__cake" src="<?= e(product_image_url($heroCake['image'])) ?>" alt="<?= e($heroCake['caption']) ?>" width="720" height="960" fetchpriority="high">
            <?php endif; ?>
            <?php if (setting_bool('hero_show_shop_card')): ?>
            <figure class="hero__shop">
                <picture>
                    <source srcset="<?= e(asset('img/shop-front.webp')) ?>" type="image/webp">
                    <img src="<?= e(asset('img/shop-front.jpg')) ?>" alt="The Continental Cake Shop storefront on Reed Street North, Greenway" width="948" height="1024" loading="lazy">
                </picture>
                <figcaption>
                    <strong><?= e(today_hours_label()) ?></strong>
                    <span><?= e(setting('store_street') . ', ' . setting('store_suburb')) ?></span>
                </figcaption>
            </figure>
            <?php endif; ?>
        </div>
    </div>
</section>
