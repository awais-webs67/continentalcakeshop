<?php
$popup = promo_popup();
if (!$popup) {
    return;
}
$coupon = $popup['coupon'];
?>
<div class="promo" data-promo data-promo-delay="<?= (int) $popup['delay'] ?>" data-promo-version="<?= e($popup['version']) ?>" data-promo-frequency="<?= e($popup['frequency']) ?>" hidden>
    <div class="promo__backdrop" data-promo-close></div>
    <div class="promo__card" role="dialog" aria-modal="true" aria-labelledby="promo-title">
        <button class="promo__close" type="button" data-promo-close aria-label="Close"><?= icon('close', '', 20) ?></button>
        <?php if ($popup['image']): ?>
            <img class="promo__image" src="<?= e(product_image_url($popup['image'])) ?>" alt="" width="800" height="450" loading="lazy">
        <?php else: ?>
            <div class="promo__ribbon" aria-hidden="true"><?= icon($coupon ? 'tag' : 'cake', '', 30) ?></div>
        <?php endif; ?>
        <div class="promo__body">
            <?php if ($coupon): ?><p class="promo__eyebrow"><?= e(coupon_value_label($coupon)) ?></p><?php endif; ?>
            <h2 class="promo__title" id="promo-title"><?= e($popup['title']) ?></h2>
            <?php if ($popup['text'] !== ''): ?><p class="promo__text"><?= nl2br(e($popup['text'])) ?></p><?php endif; ?>
            <?php if ($coupon): ?>
                <div class="promo__coupon">
                    <span class="promo__code" data-promo-code><?= e($coupon['code']) ?></span>
                    <button class="promo__copy" type="button" data-copy="<?= e($coupon['code']) ?>"><?= icon('copy', '', 16) ?> <span>Copy</span></button>
                </div>
                <p class="promo__terms">Use this code at checkout to get <?= e(coupon_summary($coupon)) ?>.<?= $coupon['max_uses'] !== null ? ' Limited number of uses.' : '' ?></p>
            <?php endif; ?>
            <?php if ($popup['button_text'] !== ''): ?>
                <a class="btn btn--primary btn--lg btn--block" href="<?= e(popup_link($popup['button_url'])) ?>" data-promo-cta><?= e($popup['button_text']) ?> <?= icon('arrow', '', 18) ?></a>
            <?php endif; ?>
            <button class="promo__later" type="button" data-promo-close>No thanks</button>
        </div>
    </div>
</div>
