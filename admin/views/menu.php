<?php /** @var array $menu @var array $choices @var bool $isDefault */
// One row per menu item, sub items straight after their top item.
$rows = [];
foreach ($menu as $top) {
    $rows[] = ['level' => 0] + $top;
    foreach ((array) ($top['children'] ?? []) as $child) {
        $rows[] = ['level' => 1] + $child;
    }
}
$known = [];
foreach ($choices as $group) {
    $known += $group;
}
$menuRow = static function (string $i, array $row) use ($choices, $known): void {
    $link = (string) ($row['link'] ?? '');
    $custom = !array_key_exists($link, $known);
    $sub = (int) ($row['level'] ?? 0) === 1;
    ?>
    <li class="menu-row<?= $sub ? ' menu-row--sub' : '' ?>" data-menu-row>
        <input type="hidden" name="rows[<?= $i ?>][level]" value="<?= $sub ? '1' : '0' ?>" data-menu-level>
        <div class="menu-row__bar">
            <span class="menu-row__kind" data-menu-kind><?= $sub ? 'Sub item' : 'Top item' ?></span>
            <span class="menu-row__tools">
                <button type="button" class="icon-btn" data-menu-move="-1" aria-label="Move up" title="Move up">&#8593;</button>
                <button type="button" class="icon-btn" data-menu-move="1" aria-label="Move down" title="Move down">&#8595;</button>
                <button type="button" class="icon-btn icon-btn--danger" data-menu-remove aria-label="Remove" title="Remove"><?= icon('trash', '', 16) ?></button>
            </span>
        </div>
        <div class="menu-row__fields">
            <label class="field"><span>Name</span><input class="input" name="rows[<?= $i ?>][label]" maxlength="<?= MENU_LABEL_MAX ?>" value="<?= e((string) ($row['label'] ?? '')) ?>" placeholder="e.g. Our Range"></label>
            <div class="menu-row__link">
                <label class="field"><span>Goes to</span>
                <select class="input" id="menu-link-<?= $i ?>" name="rows[<?= $i ?>][link]" data-menu-link>
                    <?php foreach ($choices as $group => $options): if (!$options) continue; ?>
                        <optgroup label="<?= e($group) ?>">
                            <?php foreach ($options as $value => $name): ?>
                                <option value="<?= e((string) $value) ?>" <?= selected(!$custom && (string) $value === $link) ?>><?= e($name) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                    <optgroup label="Other">
                        <option value="__custom" <?= selected($custom) ?>>Another web address...</option>
                    </optgroup>
                </select></label>
                <input class="input menu-row__url" name="rows[<?= $i ?>][url]" value="<?= $custom ? e($link) : '' ?>" placeholder="https://... or /page-address" aria-label="Web address" data-menu-url <?= $custom ? '' : 'hidden' ?>>
            </div>
        </div>
        <div class="menu-row__opts">
            <label class="switch"><input type="checkbox" value="1" <?= checked($sub) ?> data-menu-sub><span class="switch__track"></span><span>Sub item <small>in the drop down of the item above</small></span></label>
            <label class="switch"><input type="checkbox" name="rows[<?= $i ?>][new_tab]" value="1" <?= checked(!empty($row['new_tab'])) ?>><span class="switch__track"></span><span>Open in a new tab</span></label>
        </div>
    </li>
    <?php
};
?>
<div class="page-head">
    <div><h1>Menu</h1><p>The links at the top of every page, on computers and in the phone menu. Rename, reorder, add sub menus or remove items, then save.</p></div>
    <a class="btn btn--secondary btn--sm" href="<?= e(url()) ?>" target="_blank" rel="noopener">View website <?= icon('arrow', '', 16) ?></a>
</div>

<div class="grid-main">
<section class="card">
    <div class="card__head"><h2>Menu items</h2><span class="hint">Up to <?= MENU_MAX_TOP ?> top items</span></div>
    <form method="post" data-menu-form>
        <?= csrf_field() ?>
        <ol class="menu-editor" data-menu-list>
            <?php foreach ($rows as $i => $row) { $menuRow((string) $i, $row); } ?>
        </ol>
        <template data-menu-template><?php $menuRow('__i__', ['label' => '', 'link' => '/', 'level' => 0]); ?></template>
        <div class="form-actions">
            <button class="btn btn--secondary" type="button" data-menu-add><?= icon('plus', '', 18) ?> Add item</button>
            <button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> Save menu</button>
        </div>
    </form>
</section>

<aside class="form-stack">
    <section class="card">
        <div class="card__head"><h2>How it works</h2></div>
        <div class="card__body">
            <ul class="help-list">
                <li><strong>Sub item</strong> puts the item in the drop down of the top item above it.</li>
                <li><strong>All cake categories</strong> lists every category by itself, with "View all cakes". New categories appear without editing the menu.</li>
                <li><strong>No link</strong> makes a top item that only opens its drop down.</li>
                <li>Changing a name here does not change the page address, so Google rankings stay.</li>
                <li>Empty the name of an item to remove it, or use Remove.</li>
            </ul>
        </div>
    </section>
    <?php if (!$isDefault): ?>
    <section class="card">
        <div class="card__head"><h2>Start again</h2></div>
        <form class="card__body form-stack" method="post" data-confirm="Replace your menu with the standard one?">
            <?= csrf_field() ?><input type="hidden" name="action" value="reset">
            <p class="hint" style="margin:0">Home, Our Range (with every category), Custom Cakes (order and gallery), About Us and Contact.</p>
            <div><button class="btn btn--secondary btn--sm" type="submit">Use the standard menu</button></div>
        </form>
    </section>
    <?php endif; ?>
</aside>
</div>
