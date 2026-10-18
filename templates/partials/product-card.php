<?php
/** @var array $product */
$link = product_url($product);
?>
<article class="product-card">
    <a class="product-card__media" href="<?= e($link) ?>" tabindex="-1" aria-hidden="true">
        <img src="<?= e(product_image_url($product['image'])) ?>" alt="" width="600" height="600" loading="lazy" decoding="async">
        <?php if (!empty($product['badge'])): ?>
            <span class="product-card__badge"><?= e($product['badge']) ?></span>
        <?php endif; ?>
    </a>
    <div class="product-card__body">
        <p class="product-card__category"><?= e($product['category_name']) ?></p>
        <h3 class="product-card__title"><a href="<?= e($link) ?>"><?= e($product['name']) ?></a></h3>
        <div class="product-card__foot">
            <p class="product-card__price"><?= e(price_label($product)) ?></p>
            <span class="product-card__cta"><?= (int) $product['size_count'] > 1 ? 'Choose size' : 'Order' ?> <?= icon('arrow', '', 16) ?></span>
        </div>
    </div>
</article>
