<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$categories = get_categories();
$featured = get_featured_products(8);
$photos = get_gallery(12);
// The hero photo is a setting so the admin can change it; it falls back to the first gallery photo.
$heroImage = setting('hero_image');
$heroCake = db_one('SELECT * FROM gallery_images WHERE image = ?', [$heroImage])
    ?? ($heroImage !== '' ? ['id' => null, 'image' => $heroImage, 'caption' => 'Celebration cake from ' . setting('store_short_name')] : ($photos[0] ?? null));
$gallery = array_values(array_filter($photos, static fn ($p) => $p['id'] !== ($heroCake['id'] ?? null)));
$gallery = array_slice($gallery, 0, 6);
$reviews = db_all('SELECT * FROM reviews WHERE is_active = 1 ORDER BY sort_order, id LIMIT 4');
$todayKey = day_key(new DateTime());

// Category tiles: the first category gets the large tile; a custom cakes tile closes the grid.
$tiles = array_map(static fn ($cat) => [
    'name'  => $cat['name'],
    'meta'  => 'From ' . money($cat['price_from']) . ' · ' . (int) $cat['product_count'] . ' cakes',
    'link'  => category_url($cat),
    'image' => $cat['image'] ?: $cat['cover_image'],
], $categories);
$tiles[] = [
    'name'  => 'Custom Cakes',
    'meta'  => 'Birthdays, weddings and baby showers',
    'link'  => url('custom-cakes'),
    'image' => ($photos[9] ?? $photos[0] ?? null)['image'] ?? null,
];

$page = [
    'title'       => setting('seo_home_title'),
    'description' => setting('seo_home_description'),
    'canonical'   => url(),
    'body_class'  => 'page-home',
    'schema'      => [[
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => setting('store_name'),
        'url'      => url(),
    ]],
];
render('header', compact('page'));
?>

<section class="hero">
    <div class="container hero__inner">
        <div class="hero__copy">
            <p class="kicker"><?= e(setting('hero_eyebrow')) ?></p>
            <h1 class="hero__title"><?= e(setting('hero_title')) ?></h1>
            <p class="hero__text"><?= e(setting('hero_subtitle')) ?></p>
            <div class="hero__actions">
                <a class="btn btn--primary btn--lg" href="<?= e(url('shop')) ?>">Order Online <?= icon('arrow', '', 18) ?></a>
                <a class="btn btn--outline btn--lg" href="<?= e(url('custom-cakes')) ?>">Request a Custom Cake</a>
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
        </div>
    </div>
</section>

<section class="usp" aria-label="Why customers choose us">
    <div class="container usp__grid">
        <p class="usp__item"><?= icon('halal', '', 22) ?> 100% Halal ingredients</p>
        <p class="usp__item"><?= icon('leaf', '', 22) ?> Gluten-free, eggless and vegan options</p>
        <p class="usp__item"><?= icon('pen', '', 22) ?> Free message on every cake</p>
        <p class="usp__item"><?= icon('calendar', '', 22) ?> Order online, pick up in store</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <h2 class="section-title">Shop by category</h2>
                <p class="section-sub">Every cake is made in our Greenway shop.</p>
            </div>
            <a class="link-arrow" href="<?= e(url('shop')) ?>">View all cakes <?= icon('arrow', '', 18) ?></a>
        </div>
        <div class="tile-grid">
            <?php foreach ($tiles as $i => $tile): ?>
                <a class="tile <?= $i === 0 ? 'tile--feature' : '' ?>" href="<?= e($tile['link']) ?>">
                    <img src="<?= e(product_image_url($tile['image'])) ?>" alt="" width="1000" height="800" loading="lazy" decoding="async">
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

<?php if ($featured): ?>
<section class="section section--tint">
    <div class="container">
        <div class="section-head">
            <div>
                <h2 class="section-title">Customer favourites</h2>
                <p class="section-sub">The cakes Canberra keeps coming back for.</p>
            </div>
            <a class="link-arrow" href="<?= e(url('shop')) ?>">Shop all <?= icon('arrow', '', 18) ?></a>
        </div>
        <div class="product-grid">
            <?php foreach ($featured as $product): ?>
                <?php render('partials/product-card', compact('product')); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container custom-feature">
        <div class="custom-feature__copy">
            <h2 class="section-title">Made to order, just the way you pictured it</h2>
            <p class="section-sub">Bring us a photo or tell us your idea. We will help you choose the size, flavour and decorations, then send you a quote. Please allow at least <?= (int) setting('custom_lead_days', '3') ?> days for custom designs.</p>
            <ol class="steps steps--plain">
                <li><span class="steps__num">1</span><div><strong>Share your idea</strong><span>Upload a photo or describe the cake you want.</span></div></li>
                <li><span class="steps__num">2</span><div><strong>Get your quote</strong><span>We reply with a price and help with size and flavour.</span></div></li>
                <li><span class="steps__num">3</span><div><strong>Pick it up</strong><span>Collect your cake from our Greenway shop on the day.</span></div></li>
            </ol>
            <div class="custom-feature__actions">
                <a class="btn btn--primary" href="<?= e(url('custom-cakes')) ?>">Get a Quote <?= icon('arrow', '', 18) ?></a>
                <a class="btn btn--outline" href="<?= e(url('custom-cakes') . '#gallery') ?>">See our work</a>
            </div>
        </div>
        <?php if ($gallery): ?>
            <div class="mosaic">
                <?php foreach ($gallery as $photo): ?>
                    <figure class="mosaic__item">
                        <img src="<?= e(product_image_url($photo['image'])) ?>" alt="<?= e($photo['caption']) ?>" width="720" height="960" loading="lazy" decoding="async">
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($reviews): ?>
<section class="section section--tint reviews">
    <div class="container">
        <div class="reviews__head">
            <div>
                <h2 class="section-title">What our customers say</h2>
                <p class="section-sub">Reviews left by our customers on Google.</p>
            </div>
            <a class="reviews__badge" href="<?= e(setting('google_reviews_url')) ?>" target="_blank" rel="noopener">
                <?= google_logo(26) ?>
                <span class="reviews__badge-score"><?= e(setting('google_rating')) ?></span>
                <span class="reviews__badge-meta">
                    <?= rating_stars((float) setting('google_rating'), 16) ?>
                    <span><?= e(setting('google_review_count')) ?> reviews</span>
                </span>
            </a>
        </div>
        <div class="reviews__list">
            <?php foreach ($reviews as $review): ?>
                <figure class="review-card">
                    <svg class="review-card__quote" width="28" height="22" viewBox="0 0 28 22" aria-hidden="true"><path fill="currentColor" d="M0 22V13.2C0 5.7 4.1 1.3 11.2 0l1.3 3.1C8.6 4.4 6.6 7 6.4 10.6H12V22H0Zm16 0V13.2C16 5.7 20.1 1.3 27.2 0l1.3 3.1c-3.9 1.3-5.9 3.9-6.1 7.5H28V22H16Z"/></svg>
                    <blockquote><?= e($review['body']) ?></blockquote>
                    <figcaption class="review-card__foot">
                        <span class="review-card__avatar" style="--avatar: <?= e(avatar_colour($review['author'])) ?>" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($review['author'], 0, 1))) ?></span>
                        <span class="review-card__who"><strong><?= e($review['author']) ?></strong><span><?= e($review['source']) ?> review</span></span>
                        <?= google_logo(18) ?>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section visit">
    <div class="container visit__grid">
        <div class="visit__info">
            <h2 class="section-title">Visit us in Greenway</h2>
            <p class="visit__address"><?= icon('pin', '', 20) ?> <?= e(store_address_line()) ?></p>
            <table class="hours-table">
                <caption class="sr-only">Opening hours</caption>
                <tbody>
                    <?php foreach (opening_hours() as $day => $slot): ?>
                        <tr <?= $day === $todayKey ? 'class="is-today"' : '' ?>>
                            <th scope="row"><?= e(DAY_NAMES[$day]) ?><?= $day === $todayKey ? ' <span>Today</span>' : '' ?></th>
                            <td><?= $slot ? e(format_time($slot[0]) . ' to ' . format_time($slot[1])) : 'Closed' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="visit__actions">
                <a class="btn btn--primary" href="<?= e(store_maps_url()) ?>" target="_blank" rel="noopener"><?= icon('pin', '', 18) ?> Get Directions</a>
                <a class="btn btn--outline" href="tel:<?= e(setting('store_phone_e164')) ?>"><?= icon('phone', '', 18) ?> Call Us</a>
            </div>
        </div>
        <div class="visit__map">
            <iframe title="Map showing <?= e(setting('store_name')) ?>" src="https://www.google.com/maps?q=<?= e(rawurlencode(setting('store_name') . ', ' . store_address_line())) ?>&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>
</section>

<?php render('footer'); ?>
