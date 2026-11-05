<?php
/**
 * "Which shop?": a required choice between the shops, by name only.
 * 'tiles' = its own checkout step; 'pills' = a row on the custom cake form.
 *
 * @var ?int   $selected  branch already chosen
 * @var string $legend    heading of the group ('' when the step title already says it)
 * @var string $error     error message for this field, if any
 * @var string $style     'tiles' or 'pills'
 */
$selected ??= null;
$legend ??= 'Which shop?';
$error ??= '';
$style ??= 'tiles';
?>
<fieldset class="field shop-choice shop-choice--<?= e($style) ?>" <?= $error !== '' ? 'aria-describedby="branch_id-error"' : '' ?>>
    <?php if ($legend !== ''): ?><legend class="field-label"><?= e($legend) ?> <span class="field-hint">Required</span></legend><?php else: ?><legend class="sr-only">Choose your shop</legend><?php endif; ?>
    <div class="<?= $style === 'pills' ? 'choice-row' : 'shop-tiles' ?><?= $error !== '' ? ' is-invalid' : '' ?>" id="branch-picker" tabindex="-1">
        <?php foreach (active_branches() as $b): ?>
            <label class="<?= $style === 'pills' ? 'choice' : 'shop-tile' ?>">
                <input type="radio" name="branch_id" value="<?= (int) $b['id'] ?>" <?= $selected === (int) $b['id'] ? 'checked' : '' ?> required
                       data-branch data-name="<?= e($b['name']) ?>" data-address="<?= e(branch_address_line($b)) ?>">
                <?php if ($style === 'pills'): ?>
                    <span><?= icon('store', '', 20) ?> <strong><?= e($b['name']) ?></strong></span>
                <?php else: ?>
                    <span class="shop-tile__box">
                        <span class="shop-tile__icon"><?= icon('store', '', 22) ?></span>
                        <strong><?= e($b['name']) ?></strong>
                    </span>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
    </div>
    <?php if ($error !== ''): ?><p class="field-error" id="branch_id-error"><?= e($error) ?></p><?php endif; ?>
</fieldset>
