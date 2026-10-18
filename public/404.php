<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    require dirname(__DIR__) . '/src/bootstrap.php';
    http_response_code(404);
}

$page = [
    'title'   => 'Page not found | ' . setting('store_short_name'),
    'noindex' => true,
];
render('header', compact('page'));
?>

<section class="section">
    <div class="container">
        <div class="empty-state">
            <?= icon('cake', '', 48) ?>
            <h1>Sorry, we could not find that page</h1>
            <p>It may have moved, or the cake may no longer be on our menu.</p>
            <div class="empty-state__actions">
                <a class="btn btn--primary" href="<?= e(url('shop')) ?>">Shop Cakes</a>
                <a class="btn btn--outline" href="<?= e(url()) ?>">Go to Home</a>
            </div>
        </div>
    </div>
</section>

<?php render('footer'); ?>
