<?php /** @var array $result */ ?>
<?php if ($result['pages'] > 1 || $result['total'] > 0): ?>
<div class="pager">
    <span><?= (int) $result['total'] ?> result<?= $result['total'] === 1 ? '' : 's' ?> &middot; page <?= (int) $result['page'] ?> of <?= (int) $result['pages'] ?></span>
    <?php if ($result['pages'] > 1): ?>
        <div class="pager__links">
            <?php if ($result['page'] > 1): ?><a class="btn btn--ghost btn--sm" href="<?= e(page_link($result['page'] - 1)) ?>">Previous</a><?php endif; ?>
            <?php if ($result['page'] < $result['pages']): ?><a class="btn btn--ghost btn--sm" href="<?= e(page_link($result['page'] + 1)) ?>">Next</a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>
