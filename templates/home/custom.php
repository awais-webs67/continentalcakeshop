<?php
// Six gallery photos, skipping the one used in the hero.
$heroImage = setting('hero_image');
$gallery = array_slice(array_values(array_filter(get_gallery(12), static fn ($p) => $p['image'] !== $heroImage)), 0, 6);
?>
<section class="section">
    <div class="container custom-feature">
        <div class="custom-feature__copy">
            <h2 class="section-title"><?= e(setting('home_custom_title')) ?></h2>
            <div class="section-sub prose"><?= rich_text(setting('home_custom_text')) ?></div>
            <?php $steps = array_values(array_filter(setting_list('home_custom_steps'), static fn ($s) => is_array($s) && trim((string) ($s[0] ?? '')) !== '')); ?>
            <?php if ($steps): ?>
            <ol class="steps steps--plain">
                <?php foreach ($steps as $i => [$stepTitle, $stepText]): ?>
                    <li><span class="steps__num"><?= $i + 1 ?></span><div><strong><?= e((string) $stepTitle) ?></strong><span><?= e((string) $stepText) ?></span></div></li>
                <?php endforeach; ?>
            </ol>
            <?php endif; ?>
            <div class="custom-feature__actions">
                <a class="btn btn--primary" href="<?= e(url('custom-cakes')) ?>"><?= e(setting('home_custom_btn_text', 'Get a Quote')) ?> <?= icon('arrow', '', 18) ?></a>
                <a class="btn btn--outline" href="<?= e(url('custom-cakes') . '#gallery') ?>">See our work</a>
            </div>
        </div>
        <?php if ($gallery): ?>
            <div class="mosaic">
                <?php foreach ($gallery as $photo): ?>
                    <figure class="mosaic__item">
                        <img src="<?= e(product_image_url($photo['image'])) ?>" alt="<?= e($photo['caption']) ?>" width="720" height="960" loading="lazy" decoding="async">
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
