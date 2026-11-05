<?php
/**
 * "Which shop?": a simple choice between the shops, by name only. Shown at checkout and on the
 * custom cake form when two or more shops are open to customers.
 *
 * @var ?int   $selected  branch already chosen
 * @var string $legend    heading of the group
 * @var string $error     error message for this field, if any
 * @var string $style     'cards' (checkout, matches the pickup or delivery cards) or 'pills' (forms)
 */
$selected ??= null;
$legend ??= 'Which shop?';
$error ??= '';
$style ??= 'cards';
?>
<fieldset class="field shop-choice" id="branch-picker" tabindex="-1" <?= $error !== '' ? 'aria-describedby="branch_id-error"' : '' ?>>
    <legend class="field-label"><?= e($legend) ?></legend>
    <div class="<?= $style === 'pills' ? 'choice-row' : 'option-cards option-cards--shops' ?>">
        <?php foreach (active_branches() as $b): ?>
            <label class="<?= $style === 'pills' ? 'choice' : 'option-card' ?>">
                <input type="radio" name="branch_id" value="<?= (int) $b['id'] ?>" <?= $selected === (int) $b['id'] ? 'checked' : '' ?> required
                       data-branch data-name="<?= e($b['name']) ?>" data-address="<?= e(branch_address_line($b)) ?>">
                <span<?= $style === 'pills' ? '' : ' class="option-card__box"' ?>><?= icon('store', '', 20) ?> <strong><?= e($b['name']) ?></strong></span>
            </label>
        <?php endforeach; ?>
    </div>
    <?php if ($error !== ''): ?><p class="field-error" id="branch_id-error"><?= e($error) ?></p><?php endif; ?>
</fieldset>
