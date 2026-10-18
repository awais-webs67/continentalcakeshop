<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$categories = get_categories();
$featured = get_featured_products(8);
$reviews = db_all('SELECT * FROM reviews WHERE is_active = 1 ORDER BY sort_order, id LIMIT 4');
$todayKey = day_key(new DateTime());

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
            <p class="eyebrow"><?= e(setting('hero_eyebrow')) ?></p>
            <h1 class="hero__title"><?= e(setting('hero_title')) ?></h1>
            <p class="hero__text"><?= e(setting('hero_subtitle')) ?></p>
            <div class="hero__actions">
                <a class="btn btn--primary btn--lg" href="<?= e(url('shop')) ?>">Order Online <?= icon('arrow', '', 18) ?></a>
                <a class="btn btn--outline btn--lg" href="<?= e(url('custom-cakes')) ?>">Request a Custom Cake</a>
            </div>
            <ul class="hero__trust">
                <li>
                    <a href="<?= e(setting('google_reviews_url')) ?>" target="_blank" rel="noopener">
                        <span class="stars" aria-hidden="true"><?= str_repeat(icon('star', '', 16), 5) ?></span>
                        <strong><?= e(setting('google_rating')) ?></strong> from <?= e(setting('google_review_count')) ?> Google reviews
                    </a>
                </li>
                <li><?= icon('halal', '', 18) ?> 100% Halal ingredients</li>
            </ul>
        </div>

        <div class="hero__media">
            <picture>
                <source srcset="<?= e(asset('img/shop-front.webp')) ?>" type="image/webp">
                <img src="<?= e(asset('img/shop-front.jpg')) ?>" alt="The Continental Cake Shop storefront at Reed Street North, Greenway" width="948" height="1024" fetchpriority="high">
            </picture>
            <div class="hero__card">
                <span class="hero__card-icon"><?= icon('store', '', 22) ?></span>
                <div>
                    <strong><?= e(today_hours_label()) ?></strong>
                    <span><?= e(setting('store_street') . ', ' . setting('store_suburb')) ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="usp" aria-label="Why customers choose us">
    <div class="container usp__grid">
        <div class="usp__item"><?= icon('halal', '', 26) ?><div><strong>100% Halal</strong><span>Every cake, every day</span></div></div>
        <div class="usp__item"><?= icon('leaf', '', 26) ?><div><strong>Dietary options</strong><span>Gluten-free, eggless and vegan</span></div></div>
        <div class="usp__item"><?= icon('pen', '', 26) ?><div><strong>Free cake message</strong><span>Add your words at no cost</span></div></div>
        <div class="usp__item"><?= icon('calendar', '', 26) ?><div><strong>Click and collect</strong><span>Choose your pickup time</span></div></div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Our range</p>
                <h2 class="section-title">Shop by category</h2>
            </div>
            <a class="link-arrow" href="<?= e(url('shop')) ?>">View all cakes <?= icon('arrow', '', 18) ?></a>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <?php $cover = $cat['image'] ?: $cat['cover_image']; ?>
                <a class="category-card <?= $cover ? '' : 'category-card--text' ?>" href="<?= e(category_url($cat)) ?>">
                    <?php if ($cover): ?>
                        <span class="category-card__media">
                            <img src="<?= e(product_image_url($cover)) ?>" alt="" width="400" height="400" loading="lazy">
                        </span>
                    <?php else: ?>
                        <span class="category-card__icon"><?= icon('cake', '', 26) ?></span>
                    <?php endif; ?>
                    <span class="category-card__body">
                        <span class="category-card__name"><?= e($cat['name']) ?></span>
                        <span class="category-card__meta">From <?= e(money($cat['price_from'])) ?> &middot; <?= (int) $cat['product_count'] ?> cakes</span>
                    </span>
                    <?php if (!$cover): ?><span class="category-card__arrow"><?= icon('arrow', '', 20) ?></span><?php endif; ?>
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
                <p class="eyebrow">Most loved</p>
                <h2 class="section-title">Customer favourites</h2>
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
    <div class="container">
        <div class="custom-band">
            <div class="custom-band__copy">
                <p class="eyebrow">Made to order</p>
                <h2 class="section-title">Custom cakes for birthdays, weddings and baby showers</h2>
                <p>Bring us a photo or tell us your idea. We will help you choose the size, flavour and decorations, then send you a quote. Please allow at least <?= (int) setting('custom_lead_days', '3') ?> days for custom designs.</p>
                <div class="chip-row">
                    <span class="chip">Birthdays</span>
                    <span class="chip">Weddings &amp; engagements</span>
                    <span class="chip">Baby showers</span>
                    <span class="chip">Gender reveals</span>
                    <span class="chip">Kids' parties</span>
                </div>
                <div class="custom-band__actions">
                    <a class="btn btn--primary" href="<?= e(url('custom-cakes')) ?>">Get a Quote <?= icon('arrow', '', 18) ?></a>
                    <a class="btn btn--outline" href="tel:<?= e(setting('store_phone_e164')) ?>"><?= icon('phone', '', 18) ?> <?= e(setting('store_phone')) ?></a>
                </div>
            </div>
            <ol class="steps">
                <li><span class="steps__num">1</span><div><strong>Share your idea</strong><span>Upload a photo or describe the cake you want.</span></div></li>
                <li><span class="steps__num">2</span><div><strong>Get your quote</strong><span>We reply with a price and help with size and flavour.</span></div></li>
                <li><span class="steps__num">3</span><div><strong>Pick it up</strong><span>Collect your cake from our Greenway shop on the day.</span></div></li>
            </ol>
        </div>
    </div>
</section>

<?php if ($reviews): ?>
<section class="section section--tint">
    <div class="container">
        <div class="section-head section-head--center">
            <p class="eyebrow">Reviews</p>
            <h2 class="section-title">What Canberra says about us</h2>
            <a class="rating-pill" href="<?= e(setting('google_reviews_url')) ?>" target="_blank" rel="noopener">
                <span class="stars" aria-hidden="true"><?= str_repeat(icon('star', '', 16), 5) ?></span>
                <?= e(setting('google_rating')) ?> on Google &middot; <?= e(setting('google_review_count')) ?> reviews
            </a>
        </div>
        <div class="review-grid">
            <?php foreach ($reviews as $review): ?>
                <figure class="review-card">
                    <blockquote>&ldquo;<?= e($review['body']) ?>&rdquo;</blockquote>
                    <figcaption>
                        <span class="review-card__avatar" aria-hidden="true"><?= e(mb_substr($review['author'], 0, 1)) ?></span>
                        <span><strong><?= e($review['author']) ?></strong><span><?= e($review['source']) ?> review</span></span>
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
            <p class="eyebrow">Visit the shop</p>
            <h2 class="section-title">Find us in Greenway</h2>
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
