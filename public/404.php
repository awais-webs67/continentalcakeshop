<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    require dirname(__DIR__) . '/src/bootstrap.php';
}
// Addresses from the old website move permanently to the new pages.
if ($target = legacy_redirect_target(current_path())) {
    redirect($target, 301);
}
if ($target = tidy_path_redirect()) {
    redirect($target, 301);
}
http_response_code(404);
log_not_found(current_path());

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
