<?php
/** @var array $product  ('photos' from with_card_photos() adds the extra photos) */
$link = product_url($product);
$photos = $product['photos'] ?? [$product['image']];
$photos = $photos ?: [null];
?>
<article class="product-card<?= count($photos) > 1 ? ' has-photos' : '' ?>">
    <a class="product-card__media" href="<?= e($link) ?>" tabindex="-1" aria-hidden="true"<?= count($photos) > 1 ? ' data-card-photos' : '' ?>>
        <span class="product-card__slides">
            <?php foreach ($photos as $i => $photo): ?>
                <img src="<?= e(product_image_url($photo)) ?>" alt="<?= $i === 0 ? e(product_image_alt($product)) : '' ?>" width="600" height="600" loading="lazy" decoding="async">
            <?php endforeach; ?>
        </span>
        <?php if (count($photos) > 1): ?>
            <span class="product-card__dots"><?php foreach ($photos as $i => $photo): ?><i<?= $i === 0 ? ' class="is-on"' : '' ?>></i><?php endforeach; ?></span>
            
        <?php endif; ?>
        <span class="product-card__quick"><?= icon('arrow', '', 16) ?> View cake</span>
        <?php if (product_sold_out($product)): ?>
            <span class="product-card__badge product-card__badge--soldout">Sold out</span>
        <?php elseif (!empty($product['badge'])): ?>
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
            <?php elseif (product_sold_out($product) || ordering_paused()): ?>
                <a class="product-card__btn product-card__btn--view" href="<?= e($link) ?>" aria-label="View <?= e($product['name']) ?>"><?= product_sold_out($product) ? 'Sold out' : 'View' ?> <?= icon('arrow', '', 16) ?></a>
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
