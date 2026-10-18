<?php
$footerCategories = get_categories();
$socials = array_filter([
    'facebook'  => setting('social_facebook'),
    'instagram' => setting('social_instagram'),
    'pinterest' => setting('social_pinterest'),
]);
$paymentLabels = array_map(static fn ($m) => $m['label'], available_payment_methods());
?>
</main>

<footer class="site-footer">
    <?= drip_svg('drip--footer', 57) ?>
    <div class="container site-footer__grid">
        <div class="footer-brand">
            <a href="<?= e(url()) ?>" class="footer-brand__logo">
                <img src="<?= e(asset('img/logo.svg')) ?>" alt="<?= e(setting('store_name')) ?>" width="112" height="112" loading="lazy">
            </a>
            <p>Fresh cakes and desserts made in our Greenway shop since <?= e(setting('store_founded')) ?>. 100% Halal ingredients, with gluten-free, eggless and vegan options on request.</p>
            <?php if ($socials): ?>
                <div class="footer-social">
                    <?php foreach ($socials as $network => $link): ?>
                        <a href="<?= e($link) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($network)) ?>"><?= icon($network, '', 20) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer-col">
            <h2>Our Cakes</h2>
            <ul>
                <?php foreach ($footerCategories as $cat): ?>
                    <li><a href="<?= e(category_url($cat)) ?>"><?= e($cat['name']) ?></a></li>
                <?php endforeach; ?>
                <li><a href="<?= e(url('custom-cakes')) ?>">Custom Cakes</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h2>Opening Hours</h2>
            <ul class="footer-hours">
                <?php foreach (opening_hours_grouped(true) as $row): ?>
                    <li><span><?= e($row['days']) ?></span><span><?= e($row['hours']) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="footer-col">
            <h2>Visit Us</h2>
            <ul class="footer-contact">
                <li><?= icon('pin', '', 18) ?><a href="<?= e(store_maps_url()) ?>" target="_blank" rel="noopener"><?= e(setting('store_street')) ?><br><?= e(setting('store_suburb') . ' ' . setting('store_state') . ' ' . setting('store_postcode')) ?></a></li>
                <li><?= icon('phone', '', 18) ?><a href="tel:<?= e(setting('store_phone_e164')) ?>"><?= e(setting('store_phone')) ?></a></li>
                <li><?= icon('mail', '', 18) ?><a href="mailto:<?= e(setting('store_email')) ?>"><?= e(setting('store_email')) ?></a></li>
            </ul>
        </div>
    </div>

    <div class="container site-footer__bottom">
        <p>&copy; <?= date('Y') ?> <?= e(setting('store_name')) ?>. All rights reserved.</p>
        <?php if ($paymentLabels): ?>
            <ul class="payment-badges" aria-label="Payment options">
                <?php foreach ($paymentLabels as $label): ?>
                    <li><?= e($label) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</footer>

<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
