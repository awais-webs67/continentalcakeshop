<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// Custom Cake Gallery: every visible gallery photo in a Pinterest style layout. One address, no
// page 2, 3..., so Google sees all photos on one page. Photos below the first screen load lazily.

$photos = get_gallery();
$title = setting('gallery_title') ?: 'Custom Cake Gallery';
$intro = setting('gallery_intro') ?: GALLERY_INTRO_DEFAULT;
$store = setting('store_short_name');

// Filter buttons only for occasions that have photos.
$counts = [];
foreach ($photos as $i => $photo) {
    $photos[$i]['tags'] = gallery_tags((string) $photo['caption']);
    [$photos[$i]['w'], $photos[$i]['h']] = upload_image_size($photo['image']);
    foreach ($photos[$i]['tags'] as $tag) {
        $counts[$tag] = ($counts[$tag] ?? 0) + 1;
    }
}
$filters = array_intersect_key(GALLERY_FILTERS, $counts);

// Photos cut to 3:4 before the gallery kept their shape get a mix of heights, so the
// columns look like Pinterest instead of a plain grid. Photos with their own shape keep it.
const PIN_SHAPES = ['3 / 4', '4 / 5', '2 / 3', '1 / 1', '3 / 4', '5 / 6', '2 / 3', '4 / 5'];
$shape = static function (array $photo, int $i): string {
    return abs($photo['w'] / max(1, $photo['h']) - 0.75) < 0.01 ? PIN_SHAPES[$i % count(PIN_SHAPES)] : $photo['w'] . ' / ' . $photo['h'];
};
$orderLink = static fn (array $photo): string => url('custom-cakes') . '?photo=' . (int) $photo['id'] . '#quote';

$crumbs = [['Home', url()], ['Custom Cakes', url('custom-cakes')], [$title, url('custom-cake-gallery')]];
$page = [
    'title'       => 'Custom Cake Gallery Canberra | Birthday & Wedding Cakes',
    'description' => 'See ' . count($photos) . ' custom cakes we have made in Greenway, Canberra: birthday, kids, wedding, engagement and baby shower cakes. 100% Halal. Order one like it.',
    'canonical'   => url('custom-cake-gallery'),
    'schema'      => [breadcrumb_schema($crumbs), [
        '@context'    => 'https://schema.org',
        '@type'       => 'ImageGallery',
        'name'        => $title . ' | ' . $store,
        'description' => $intro,
        'url'         => url('custom-cake-gallery'),
        'about'       => ['@id' => url('#bakery')],
        'image'       => array_map(static fn ($p) => [
            '@type'           => 'ImageObject',
            'contentUrl'      => product_image_url($p['image']),
            'caption'         => $p['caption'],
            'width'           => $p['w'],
            'height'          => $p['h'],
            'creditText'      => setting('store_name'),
            'creator'         => ['@type' => 'Organization', 'name' => setting('store_name')],
            'copyrightNotice' => setting('store_name'),
        ], array_slice($photos, 0, 60)),
    ]],
];
if ($photos) {
    $page['image'] = product_image_url($photos[0]['image']);
}
render('header', compact('page'));
?>

<section class="page-hero">
    <div class="container">
        <?php render('partials/breadcrumbs', compact('crumbs')); ?>
        <h1 class="page-title"><?= e($title) ?></h1>
        <p class="page-lead"><?= e($intro) ?></p>
        <div class="page-hero__actions">
            <a class="btn btn--primary" href="<?= e(url('custom-cakes') . '#quote') ?>">Order a Custom Cake <?= icon('arrow', '', 18) ?></a>
            <?php if (setting('social_pinterest')): ?><a class="btn btn--outline" href="<?= e(setting('social_pinterest')) ?>" target="_blank" rel="noopener">Follow us on Pinterest</a><?php endif; ?>
        </div>
    </div>
</section>

<section class="section section--compact" id="gallery">
    <div class="container">
        <?php if (!$photos): ?>
            <div class="empty-state"><p>New photos are on the way. In the meantime, <a href="<?= e(url('custom-cakes')) ?>">tell us about your cake</a>.</p></div>
        <?php else: ?>
            <?php if (count($filters) > 1): ?>
                <div class="filter-chips pin-filters" role="group" aria-label="Show cakes for" data-pin-filters>
                    <button type="button" class="chip is-active" data-pin-filter="" aria-pressed="true">All <span><?= count($photos) ?></span></button>
                    <?php foreach ($filters as $key => [$label]): ?>
                        <button type="button" class="chip" data-pin-filter="<?= e($key) ?>" aria-pressed="false"><?= e($label) ?> <span><?= $counts[$key] ?></span></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="pin-grid" data-pin-grid>
                <?php foreach ($photos as $i => $photo): ?>
                    <figure class="pin" data-pin data-tags="<?= e(implode(' ', $photo['tags'])) ?>" style="--pin-shape: <?= e($shape($photo, $i)) ?>">
                        <button type="button" class="pin__open" data-pin-open data-full="<?= e(product_image_url($photo['image'])) ?>" data-order="<?= e($orderLink($photo)) ?>">
                            <img src="<?= e(product_image_url($photo['image'])) ?>" alt="<?= e($photo['caption']) ?>" width="<?= $photo['w'] ?>" height="<?= $photo['h'] ?>" <?= $i < 6 ? '' : 'loading="lazy" ' ?>decoding="async">
                            <span class="sr-only">View larger</span>
                        </button>
                        <figcaption class="pin__caption">
                            <span><?= e($photo['caption']) ?></span>
                            <a class="pin__order" href="<?= e($orderLink($photo)) ?>" rel="nofollow">Order one like this <?= icon('arrow', '', 15) ?></a>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
            <p class="pin-empty" data-pin-empty hidden>No photos for this yet. <button type="button" class="link-btn" data-pin-filter="">Show all cakes</button></p>
        <?php endif; ?>

        <div class="cta-band pin-cta">
            <div>
                <h2>Seen a cake you love?</h2>
                <p>Send us the photo or tell us your idea. We will reply with a price and the options.</p>
            </div>
            <div class="cta-band__actions">
                <a class="btn btn--light" href="<?= e(url('custom-cakes') . '#quote') ?>"><?= icon('gift', '', 18) ?> Get a Quote</a>
                <a class="btn btn--ghost-light" href="tel:<?= e(setting('store_phone_e164')) ?>"><?= icon('phone', '', 18) ?> <?= e(setting('store_phone')) ?></a>
            </div>
        </div>
    </div>
</section>

<?php if ($photos): ?>
<dialog class="lightbox pin-box" data-pin-box aria-label="<?= e($title) ?>">
    <button type="button" class="lightbox__close" data-pin-close aria-label="Close"><?= icon('close', '', 24) ?></button>
    <figure class="pin-box__body">
        <img alt="" data-pin-img>
        <figcaption>
            <span data-pin-text></span>
            <a class="btn btn--light btn--sm" href="<?= e(url('custom-cakes')) ?>" data-pin-order>Order one like this <?= icon('arrow', '', 16) ?></a>
        </figcaption>
    </figure>
    <button type="button" class="gallery-arrow gallery-arrow--prev" data-pin-prev aria-label="Previous photo"><?= icon('chevron', '', 24) ?></button>
    <button type="button" class="gallery-arrow gallery-arrow--next" data-pin-next aria-label="Next photo"><?= icon('chevron', '', 24) ?></button>
</dialog>
<?php endif; ?>

<?php render('footer'); ?>
