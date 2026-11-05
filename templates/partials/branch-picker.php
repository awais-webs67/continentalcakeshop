<?php
/**
 * "Choose your shop" cards, used at checkout and on the custom cake and contact forms.
 * Only shown when two or more branches are open to customers. "Find the nearest" asks the
 * browser for the customer's location (it never leaves the browser) and marks the closest shop.
 *
 * @var ?int   $selected  branch already chosen
 * @var string $legend    heading of the group
 * @var string $hint      line under the heading
 * @var string $error     error message for this field, if any
 */
$selected ??= null;
$legend ??= 'Choose your shop';
$hint ??= '';
$error ??= '';
$choices = branch_choices();
?>
<fieldset class="branch-picker" id="branch-picker" data-branch-picker tabindex="-1" <?= $error !== '' ? 'aria-describedby="branch_id-error"' : '' ?>>
    <legend class="branch-picker__legend"><?= e($legend) ?></legend>
    <div class="branch-picker__bar">
        <?php if ($hint !== ''): ?><p class="branch-picker__hint"><?= e($hint) ?></p><?php endif; ?>
        <button type="button" class="branch-picker__locate" data-branch-locate hidden><?= icon('pin', '', 16) ?> <span data-branch-locate-label>Find the nearest shop</span></button>
    </div>
    <div class="branch-picker__cards">
        <?php foreach ($choices as $b): ?>
            <label class="branch-option">
                <input type="radio" name="branch_id" value="<?= (int) $b['id'] ?>" <?= $selected === $b['id'] ? 'checked' : '' ?> required
                       data-branch data-address="<?= e($b['address']) ?>" data-name="<?= e($b['name']) ?>"
                       <?= $b['lat'] !== null ? 'data-lat="' . e((string) $b['lat']) . '" data-lng="' . e((string) $b['lng']) . '"' : '' ?>>
                <span class="branch-option__box">
                    <span class="branch-option__head">
                        <strong><?= e($b['name']) ?></strong>
                        <span class="branch-option__near" data-branch-near hidden>Nearest</span>
                    </span>
                    <span class="branch-option__addr"><?= e($b['address']) ?></span>
                    <span class="branch-option__meta">
                        <span><?= icon('clock', '', 14) ?> <?= e(today_hours_label()) ?></span>
                        <span class="branch-option__distance" data-branch-distance hidden></span>
                    </span>
                </span>
            </label>
        <?php endforeach; ?>
    </div>
    <?php if ($error !== ''): ?><p class="field-error" id="branch_id-error"><?= e($error) ?></p><?php endif; ?>
    <p class="field-help branch-picker__status" data-branch-status role="status" hidden></p>
</fieldset>
