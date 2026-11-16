<?php
/**
 * Section navigation for pages with several parts (Settings, AI SEO agent): a list with icons
 * and a short hint beside the content on wide screens, tiles on phones.
 * @var array $items key => [label, icon, hint] @var string $active @var callable $link key => URL @var string $label
 */
?>
<nav class="section-nav" aria-label="<?= e($label) ?>">
    <?php foreach ($items as $key => [$title, $iconName, $hint]): ?>
        <a class="section-nav__item <?= $key === $active ? 'is-active' : '' ?>" href="<?= e($link($key)) ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>>
            <span class="section-nav__icon"><?= icon($iconName, '', 18) ?></span>
            <span class="section-nav__text"><strong><?= e($title) ?></strong><small><?= e($hint) ?></small></span>
        </a>
    <?php endforeach; ?>
</nav>
