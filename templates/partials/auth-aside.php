<?php
// Benefits panel shown beside the login and register forms.
$photo = get_gallery(1)[0] ?? null;
?>
<aside class="auth-aside">
    <?php if ($photo): ?>
        <img src="<?= e(product_image_url($photo['image'])) ?>" alt="" width="720" height="960" loading="lazy">
    <?php endif; ?>
    <div class="auth-aside__body">
        <h2>Why create an account?</h2>
        <ul class="tick-list">
            <li><?= icon('check', '', 18) ?> See all your past orders in one place</li>
            <li><?= icon('check', '', 18) ?> Follow each order from received to ready</li>
            <li><?= icon('check', '', 18) ?> Faster checkout with your details saved</li>
        </ul>
    </div>
</aside>
