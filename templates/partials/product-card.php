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
            <?php if (!empty($product['is_enquiry'])): ?>
                <a class="product-card__btn product-card__btn--enquire" href="<?= e(enquiry_url($product)) ?>" aria-label="Enquire about <?= e($product['name']) ?>"><?= icon('mail', '', 17) ?> Enquire</a>
            <?php else: ?>
            <form class="product-card__action" method="post" action="<?= e(url('cart')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="size_id" value="<?= (int) $product['default_size_id'] ?>">
                <input type="hidden" name="qty" value="1">
                <button class="product-card__btn" type="submit" aria-label="Add <?= e($product['name']) ?> to cart"><?= icon('bag', '', 17) ?> Add to Cart</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</article>
