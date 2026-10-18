<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$faqs = get_faqs();
$groups = [];
foreach ($faqs as $faq) {
    $groups[$faq['category']][] = $faq;
}

$crumbs = [['Home', url()], ['FAQ', url('faq')]];
$page = [
    'title'       => 'Frequently Asked Questions | ' . setting('store_short_name'),
    'description' => 'Answers about ordering, pickup, payment, Halal and dietary options, and custom cakes at ' . setting('store_name') . '.',
    'canonical'   => url('faq'),
    'schema'      => [
        breadcrumb_schema($crumbs),
        [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array_map(static fn ($f) => [
                '@type'          => 'Question',
                'name'           => $f['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => content_text($f['answer'])],
            ], $faqs),
        ],
    ],
];
render('header', compact('page'));
?>

<section class="page-hero page-hero--slim">
    <div class="container">
        <?php render('partials/breadcrumbs', compact('crumbs')); ?>
        <h1 class="page-title">Frequently asked questions</h1>
        <p class="page-lead">Quick answers about ordering, pickup and our cakes. Still not sure? Call us on <a href="tel:<?= e(setting('store_phone_e164')) ?>"><?= e(setting('store_phone')) ?></a>.</p>
    </div>
</section>

<section class="section section--compact">
    <div class="container faq-layout">
        <nav class="faq-topics" aria-label="Topics">
            <?php foreach (array_keys($groups) as $i => $category): ?>
                <a href="#topic-<?= $i ?>"><?= e($category) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="faq-groups">
            <?php foreach (array_values($groups) as $i => $items): ?>
                <section class="faq-group" id="topic-<?= $i ?>">
                    <h2><?= e($items[0]['category']) ?></h2>
                    <?php foreach ($items as $faq): ?>
                        <details class="faq-item">
                            <summary><?= e($faq['question']) ?> <?= icon('plus', 'faq-item__icon', 18) ?></summary>
                            <div class="faq-item__answer prose"><?= render_content($faq['answer']) ?></div>
                        </details>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
            <div class="faq-cta">
                <div>
                    <h2>Still have a question?</h2>
                    <p>We are happy to help with orders, custom cakes and dietary needs.</p>
                </div>
                <div class="faq-cta__actions">
                    <a class="btn btn--primary" href="tel:<?= e(setting('store_phone_e164')) ?>"><?= icon('phone', '', 18) ?> Call Us</a>
                    <a class="btn btn--outline" href="<?= e(url('contact')) ?>">Send a Message</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php render('footer'); ?>
