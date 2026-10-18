<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$slug = input('slug');
$content = in_array($slug, POLICY_PAGES, true) ? get_page($slug) : null;
if (!$content) {
    not_found();
}

$crumbs = [['Home', url()], [$content['title'], url($slug)]];
$page = [
    'title'       => $content['meta_title'] ?: $content['title'] . ' | ' . setting('store_short_name'),
    'description' => $content['meta_description'] ?: (string) $content['summary'],
    'canonical'   => url($slug),
    'schema'      => [breadcrumb_schema($crumbs)],
];
render('header', compact('page'));
?>

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
            <?php foreach (db_all('SELECT slug, title FROM pages WHERE is_active = 1 ORDER BY id') as $other): ?>
                <?php if ($other['slug'] !== $slug && in_array($other['slug'], POLICY_PAGES, true)): ?>
                    <a href="<?= e(url($other['slug'])) ?>"><?= e($other['title']) ?> <?= icon('arrow', '', 16) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <a href="<?= e(url('faq')) ?>">Frequently Asked Questions <?= icon('arrow', '', 16) ?></a>
        </nav>
    </div>
</section>

<?php render('footer'); ?>
