<?php
/**
 * Rows for adding items to an order: a cake size, an extra or a custom line.
 * @var array  $catalogue from admin_size_choices()
 * @var string $prefix    form field name, e.g. "add"
 * @var int    $rows      empty rows shown at the start
 * @var array  $values    optional rows to show again after a failed save
 */
$rows ??= 1;
$values = array_values(array_filter((array) ($values ?? []), 'is_array'));
$row = static function (string $index, array $v = []) use ($catalogue, $prefix): string {
    $val = static fn (string $k): string => is_string($v[$k] ?? null) ? $v[$k] : '';
    $chosen = $val('item');
    ob_start(); ?>
    <div class="line-row" data-line-row>
        <label class="field line-row__item"><span>Item</span>
            <select class="input" name="<?= e($prefix) ?>[<?= $index ?>][item]" data-line-item>
                <option value="">Choose a cake or extra</option>
                <?php foreach ($catalogue as $group => $choices): ?>
                    <optgroup label="<?= e($group) ?>">
                        <?php foreach ($choices as $value => $choice): ?><option value="<?= e($value) ?>" data-price="<?= e(number_format($choice['price'], 2, '.', '')) ?>" <?= selected($chosen === $value) ?>><?= e($choice['label']) ?></option><?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
                <option value="custom" <?= selected($chosen === 'custom') ?>>Something else (type the name and price)</option>
            </select>
        </label>
        <label class="field line-row__custom" data-line-custom <?= $chosen === 'custom' ? '' : 'hidden' ?>><span>Name</span><input class="input" name="<?= e($prefix) ?>[<?= $index ?>][name]" maxlength="160" placeholder="e.g. 3 tier wedding cake" value="<?= e($val('name')) ?>"></label>
        <label class="field line-row__qty"><span>Qty</span><input class="input" type="number" min="1" max="99" name="<?= e($prefix) ?>[<?= $index ?>][qty]" value="<?= e($val('qty') !== '' ? $val('qty') : '1') ?>"></label>
        <label class="field line-row__price"><span>Price each</span><div class="input-prefix"><span>$</span><input class="input" name="<?= e($prefix) ?>[<?= $index ?>][price]" inputmode="decimal" placeholder="0.00" value="<?= e($val('price')) ?>" data-line-price></div></label>
        <label class="field line-row__options"><span>Options <small>e.g. Eggless</small></span><input class="input" name="<?= e($prefix) ?>[<?= $index ?>][options]" maxlength="255" value="<?= e($val('options')) ?>"></label>
        <label class="field line-row__message"><span>Message on cake</span><input class="input" name="<?= e($prefix) ?>[<?= $index ?>][message]" maxlength="60" value="<?= e($val('message')) ?>"></label>
        <button type="button" class="repeater__remove line-row__remove" data-repeater-remove aria-label="Remove line"><?= icon('trash', '', 16) ?></button>
    </div>
    <?php return (string) ob_get_clean();
};
?>
<div data-repeater data-line-adder>
    <div data-repeater-list class="line-rows">
        <?php foreach ($values as $i => $v): ?><?= $row((string) $i, $v) ?><?php endforeach; ?>
        <?php for ($i = count($values); $i < max($rows, count($values) ? 0 : $rows); $i++): ?><?= $row((string) $i) ?><?php endfor; ?>
    </div>
    <template><?= $row('__INDEX__') ?></template>
    <p style="margin:10px 0 0"><button type="button" class="btn btn--secondary btn--sm" data-repeater-add><?= icon('plus', '', 16) ?> Add another line</button></p>
</div>
