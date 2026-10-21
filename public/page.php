<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// Any page written in Admin > Pages: policy pages (privacy, terms...) and landing pages
// for searches such as "birthday cakes canberra". Unknown addresses fall through to the
// 404 page, which also sends old website addresses to their new page.

$slug = input('slug');
$content = preg_match('/^[a-z0-9-]{1,80}$/', $slug) ? get_page($slug) : null;
if (!$content) {
    not_found();
}
$landing = ($content['type'] ?? 'policy') === 'landing';

$crumbs = [['Home', url()], [$content['title'], url($slug)]];
$page = [
    'title'       => $content['meta_title'] ?: $content['title'] . ' | ' . setting('store_short_name'),
    'description' => $content['meta_description'] ?: (string) $content['summary'],
    'canonical'   => url($slug),
    'schema'      => [breadcrumb_schema($crumbs)],
];
$photos = $landing && !empty($content['show_gallery']) ? get_gallery(8) : [];
if ($photos) {
    $page['image'] = product_image_url($photos[0]['image']);
}
$quoteUrl = url('custom-cakes') . (($content['occasion'] ?? '') !== '' ? '?occasion=' . rawurlencode($content['occasion']) : '') . '#quote';
render('header', compact('page'));
?>

<?php if ($landing): ?>
<section class="page-hero landing-hero">
    <div class="container container--narrow">
        <?php render('partials/breadcrumbs', compact('crumbs')); ?>
        <h1 class="page-title"><?= e($content['title']) ?></h1>
        <?php if ($content['summary']): ?><p class="page-lead"><?= e($content['summary']) ?></p><?php endif; ?>
        <div class="landing-hero__actions">
            <a class="btn btn--primary btn--lg" href="<?= e($quoteUrl) ?>"><?= icon('mail', '', 18) ?> Get a quote</a>
            <a class="btn btn--outline btn--lg" href="<?= e(url('shop')) ?>">Order a cake online</a>
        </div>
    </div>
</section>

<section class="section section--compact">
    <div class="container container--narrow">
        <article class="prose"><?= render_content($content['body']) ?></article>
    </div>
</section>

<?php if ($photos): ?>
<section class="section section--compact landing-gallery">
    <div class="container">
        <h2 class="section-title">Cakes we have made</h2>
        <div class="landing-gallery__grid">
            <?php foreach ($photos as $photo): ?>
                <img src="<?= e(product_image_url($photo['image'])) ?>" alt="<?= e($photo['caption']) ?>" width="540" height="720" loading="lazy" decoding="async">
            <?php endforeach; ?>
        </div>
        <p class="landing-gallery__more"><a class="link-arrow" href="<?= e(url('custom-cakes')) ?>">See more custom cakes <?= icon('arrow', '', 18) ?></a></p>
    </div>
</section>
<?php endif; ?>

<section class="section section--compact">
    <div class="container container--narrow">
        <div class="landing-cta">
            <div>
                <h2>Ready to order?</h2>
                <p>Send us your idea for a quote, or call <a href="tel:<?= e(setting('store_phone_e164')) ?>"><?= e(setting('store_phone')) ?></a>. We are at <?= e(store_address_line()) ?>.</p>
            </div>
            <a class="btn btn--primary btn--lg" href="<?= e($quoteUrl) ?>">Get a quote <?= icon('arrow', '', 18) ?></a>
        </div>
        <?php $others = array_filter(get_landing_pages(), static fn ($p) => $p['slug'] !== $slug); ?>
        <?php if ($others): ?>
            <nav class="policy-nav" aria-label="More cakes">
                <?php foreach ($others as $other): ?><a href="<?= e(url($other['slug'])) ?>"><?= e($other['title']) ?> <?= icon('arrow', '', 16) ?></a><?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>

<?php else: ?>
<section class="page-hero page-hero--slim">
    <div class="container container--narrow">
        <?php render('partials/breadcrumbs', compact('crumbs')); ?>
        <h1 class="page-title"><?= e($content['title']) ?></h1>
        <?php if ($content['summary']): ?>
            <p class="page-lead"><?= e($content['summary']) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section section--compact">
    <div class="container container--narrow">
        <article class="prose">
            <?= render_content($content['body']) ?>
            <p class="prose__updated">Last updated <?= e(date('j F Y', strtotime($content['updated_at']))) ?></p>
        </article>
        <nav class="policy-nav" aria-label="Policies">
            <?php foreach (db_all("SELECT slug, title FROM pages WHERE is_active = 1 AND type = 'policy' ORDER BY id") as $other): ?>
                <?php if ($other['slug'] !== $slug): ?>
                    <a href="<?= e(url($other['slug'])) ?>"><?= e($other['title']) ?> <?= icon('arrow', '', 16) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <a href="<?= e(url('faq')) ?>">Frequently Asked Questions <?= icon('arrow', '', 16) ?></a>
        </nav>
    </div>
</section>
<?php endif; ?>

<?php render('footer'); ?>
