<section class="section <?= setting('home_text_style') === 'tint' ? 'section--tint' : '' ?> home-text">
    <div class="container home-text__inner">
        <?php if (setting('home_text_title') !== ''): ?><h2 class="section-title"><?= e(setting('home_text_title')) ?></h2><?php endif; ?>
        <div class="prose"><?= rich_text(setting('home_text_body')) ?></div>
    </div>
</section>
