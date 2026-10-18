<?php $featured = get_featured_products(max(1, min(24, (int) setting('home_featured_count', '8')))); ?>
<?php if ($featured): ?>
<section class="section section--tint">
    <div class="container">
        <div class="section-head">
            <div>
                <h2 class="section-title"><?= e(setting('home_featured_title')) ?></h2>
                <?php if (setting('home_featured_text') !== ''): ?><p class="section-sub"><?= e(setting('home_featured_text')) ?></p><?php endif; ?>
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
