<?php
$footerCategories = get_categories();
$socials = array_filter([
    'Facebook'  => setting('social_facebook'),
    'Instagram' => setting('social_instagram'),
    'Pinterest' => setting('social_pinterest'),
]);
?>
</main>

<footer class="site-footer">
    <?= drip_svg('drip--footer', 57) ?>
    <div class="container site-footer__grid">
        <div class="footer-brand">
            <a href="<?= e(url()) ?>" class="footer-brand__logo">
                <img src="<?= e(asset('img/logo.svg')) ?>" alt="<?= e(setting('store_name')) ?>" width="72" height="72" loading="lazy">
            </a>
            <p>Fresh cakes made in our Greenway shop since <?= e(setting('store_founded')) ?>. 100% Halal ingredients.</p>
            <ul class="footer-inline footer-inline--brand">
                <li><a href="<?= e(url('about')) ?>">About Us</a></li>
                <li><a href="<?= e(url('contact')) ?>">Contact</a></li>
                <li><a href="<?= e(url('account')) ?>">My Account</a></li>
            </ul>
        </div>

        <nav class="footer-col" aria-label="Our cakes">
            <h2>Our Cakes</h2>
            <ul class="footer-links footer-links--2col">
                <?php foreach ($footerCategories as $cat): ?>
                    <li><a href="<?= e(category_url($cat)) ?>"><?= e($cat['name']) ?></a></li>
                <?php endforeach; ?>
                <li><a href="<?= e(url('custom-cakes')) ?>">Custom Cakes</a></li>
            </ul>
        </nav>

        <div class="footer-col">
            <h2>Visit Us</h2>
            <ul class="footer-links">
                <li><a href="<?= e(store_maps_url()) ?>" target="_blank" rel="noopener"><?= e(store_address_line()) ?></a></li>
                <li><a href="tel:<?= e(setting('store_phone_e164')) ?>"><?= e(setting('store_phone')) ?></a></li>
                <?php foreach (opening_hours_grouped(true) as $row): ?>
                    <li><?= e($row['days']) ?>: <?= e($row['hours']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="container site-footer__bottom">
        <p>&copy; <?= date('Y') ?> <?= e(setting('store_name')) ?><?= setting('store_abn') !== '' ? ' &middot; ABN ' . e(setting('store_abn')) : '' ?></p>
        <ul class="footer-inline">
            <li><a href="<?= e(url('faq')) ?>">FAQ</a></li>
            <li><a href="<?= e(url('allergens')) ?>">Allergens</a></li>
            <li><a href="<?= e(url('refund-policy')) ?>">Refunds</a></li>
            <li><a href="<?= e(url('terms')) ?>">Terms</a></li>
            <li><a href="<?= e(url('privacy-policy')) ?>">Privacy</a></li>
            <?php foreach ($socials as $name => $link): ?>
                <li><a href="<?= e($link) ?>" target="_blank" rel="noopener"><?= e($name) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</footer>

<?php render('partials/promo-popup'); ?>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
