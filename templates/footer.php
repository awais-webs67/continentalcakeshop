<?php
$footerCategories = get_categories();
$socials = array_filter([
    'facebook'  => setting('social_facebook'),
    'instagram' => setting('social_instagram'),
    'pinterest' => setting('social_pinterest'),
]);
$paymentLabels = array_map(static fn ($m) => $m['label'], available_payment_methods());
$todayKey = day_key(new DateTime());
?>
</main>

<footer class="site-footer">
    <?= drip_svg('drip--footer', 57) ?>
    <div class="container">
        <div class="footer-cta">
            <div>
                <h2>Planning a celebration?</h2>
                <p>Order online and pick up from our Greenway shop, or tell us about the custom cake you have in mind.</p>
            </div>
            <div class="footer-cta__actions">
                <a class="btn btn--light" href="<?= e(url('shop')) ?>"><?= icon('bag', '', 18) ?> Order Online</a>
                <a class="btn btn--ghost-light" href="<?= e(url('custom-cakes')) ?>">Get a Custom Quote</a>
            </div>
        </div>

        <div class="site-footer__grid">
            <div class="footer-brand">
                <a href="<?= e(url()) ?>" class="footer-brand__logo">
                    <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="84" height="84" loading="lazy">
                    <span>
                        <strong><?= e(setting('store_short_name')) ?></strong>
                        <span>Canberra</span>
                    </span>
                </a>
                <p>Fresh cakes and desserts made in our Greenway shop since <?= e(setting('store_founded')) ?>. 100% Halal ingredients, with gluten-free, eggless and vegan options on request.</p>
                <?php if ($socials): ?>
                    <div class="footer-social">
                        <?php foreach ($socials as $network => $link): ?>
                            <a href="<?= e($link) ?>" target="_blank" rel="noopener"><?= $network === 'pinterest' ? '' : icon($network, '', 18) ?><?= e(ucfirst($network)) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <nav class="footer-col" aria-label="Our cakes">
                <h2>Our Cakes</h2>
                <ul>
                    <?php foreach ($footerCategories as $cat): ?>
                        <li><a href="<?= e(category_url($cat)) ?>"><?= e($cat['name']) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?= e(url('custom-cakes')) ?>">Custom Cakes</a></li>
                </ul>
            </nav>

            <nav class="footer-col" aria-label="Quick links">
                <h2>Quick Links</h2>
                <ul>
                    <li><a href="<?= e(url('shop')) ?>">Order Online</a></li>
                    <li><a href="<?= e(url('custom-cakes')) ?>">Request a Quote</a></li>
                    <li><a href="<?= e(url('about')) ?>">About Us</a></li>
                    <li><a href="<?= e(url('contact')) ?>">Contact Us</a></li>
                    <li><a href="<?= e(url('cart')) ?>">Your Cart</a></li>
                </ul>
            </nav>

            <div class="footer-col footer-visit">
                <h2>Visit Us</h2>
                <ul class="footer-contact">
                    <li><?= icon('pin', '', 18) ?><a href="<?= e(store_maps_url()) ?>" target="_blank" rel="noopener"><?= e(setting('store_street')) ?>, <?= e(setting('store_suburb') . ' ' . setting('store_state') . ' ' . setting('store_postcode')) ?></a></li>
                    <li><?= icon('phone', '', 18) ?><a href="tel:<?= e(setting('store_phone_e164')) ?>"><?= e(setting('store_phone')) ?></a></li>
                    <li><?= icon('mail', '', 18) ?><a href="mailto:<?= e(setting('store_email')) ?>"><?= e(setting('store_email')) ?></a></li>
                </ul>
                <ul class="footer-hours">
                    <?php foreach (opening_hours() as $day => $slot): ?>
                        <li <?= $day === $todayKey ? 'class="is-today"' : '' ?>>
                            <span><?= e(substr(DAY_NAMES[$day], 0, 3)) ?><?= $day === $todayKey ? ' (today)' : '' ?></span>
                            <span><?= $slot ? e(format_time($slot[0]) . ' to ' . format_time($slot[1])) : 'Closed' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="site-footer__bottom">
        <div class="container site-footer__bottom-inner">
            <p>&copy; <?= date('Y') ?> <?= e(setting('store_name')) ?>. All rights reserved.</p>
            <?php if ($paymentLabels): ?>
                <div class="payment-badges">
                    <span>We accept</span>
                    <ul aria-label="Payment options">
                        <?php foreach ($paymentLabels as $label): ?>
                            <li><?= e($label) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</footer>

<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
