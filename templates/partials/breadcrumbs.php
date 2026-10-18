<?php
/** @var array $crumbs list of [label, url] pairs; the last one is the current page */
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol>
        <?php foreach ($crumbs as $i => [$label, $link]): ?>
            <?php if ($i === array_key_last($crumbs)): ?>
                <li aria-current="page"><?= e($label) ?></li>
            <?php else: ?>
                <li><a href="<?= e($link) ?>"><?= e($label) ?></a><?= icon('chevron', '', 14) ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>
