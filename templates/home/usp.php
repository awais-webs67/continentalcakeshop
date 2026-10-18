<?php
$items = array_values(array_filter(array_map('trim', array_map('strval', setting_list('home_usp_items')))));
$icons = ['halal', 'leaf', 'pen', 'truck'];
if (!$items) {
    return;
}
?>
<section class="usp" aria-label="Why customers choose us">
    <div class="container usp__grid">
        <?php foreach ($items as $i => $item): ?>
            <p class="usp__item"><?= icon($icons[$i % 4], '', 22) ?> <?= e($item) ?></p>
        <?php endforeach; ?>
    </div>
</section>
