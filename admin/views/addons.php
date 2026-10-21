<?php /** @var array $addons @var ?array $editing */
$form = $editing ?? ['id' => 0, 'name' => '', 'description' => '', 'price' => '', 'max_qty' => 5, 'sort_order' => count($addons) + 1, 'is_active' => 1];
?>
<div class="page-head">
    <div><h1>Extras</h1><p>Small things customers can add to their order in the cart, like candles, a cake knife or a gift card.</p></div>
</div>

<section class="card">
    <form class="card__body form-stack" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="feature">
        <label class="switch"><input type="checkbox" name="addons_enabled" value="1" <?= checked(addons_enabled()) ?>><span class="switch__track"></span><span><strong>Show extras in the cart</strong></span></label>
        <label class="field"><span>Heading in the cart</span><input class="input" name="addons_title" maxlength="80" value="<?= e(setting('addons_title', 'Add something extra')) ?>"></label>
        <button class="btn btn--secondary btn--sm" type="submit">Save</button>
        <p class="hint" style="margin:0"><?= addons_enabled() ? 'On. Customers see the extras below their cakes in the cart.' : 'Off. No extras are shown.' ?></p>
    </form>
</section>

<div class="grid-main" style="margin-top:16px">
    <section class="card">
        <div class="card__head"><h2>All extras</h2><span class="hint"><?= count($addons) ?> items</span></div>
        <?php if (!$addons): ?>
            <div class="empty"><?= icon('gift', '', 32) ?><p>No extras yet. Add the first one with the form.</p></div>
        <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Extra</th><th>Price</th><th>Most per order</th><th>Sold</th><th>Status</th><th class="num">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($addons as $a): ?>
                <tr>
                    <td><a class="row-link" href="<?= e(admin_url('addons', ['edit' => $a['id']])) ?>#addon-form"><?= e($a['name']) ?></a><?php if ($a['description']): ?><div class="muted"><?= e($a['description']) ?></div><?php endif; ?></td>
                    <td><strong><?= e(money($a['price'])) ?></strong></td>
                    <td><?= (int) $a['max_qty'] ?></td>
                    <td><?= (int) $a['sold'] ?></td>
                    <td><?= $a['is_active'] ? status_badge('active', 'On') : status_badge('inactive', 'Off') ?></td>
                    <td class="num">
                        <a class="link-btn" href="<?= e(admin_url('addons', ['edit' => $a['id']])) ?>#addon-form">Edit</a>
                        <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="link-btn" type="submit"><?= $a['is_active'] ? 'Turn off' : 'Turn on' ?></button></form>
                        <form method="post" style="display:inline" data-confirm="Delete <?= e($a['name']) ?>?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="action" value="delete"><button class="link-btn link-btn--danger" type="submit">Delete</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </section>

    <section class="card" id="addon-form">
        <div class="card__head"><h2><?= $editing ? 'Edit ' . e($editing['name']) : 'Add an extra' ?></h2><?php if ($editing): ?><a href="<?= e(admin_url('addons')) ?>">Cancel</a><?php endif; ?></div>
        <form class="card__body form-stack" method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
            <label class="field"><span>Name</span><input class="input" name="name" maxlength="120" value="<?= e($form['name']) ?>" required placeholder="e.g. Birthday candles"></label>
            <label class="field"><span>Short description <small>optional</small></span><input class="input" name="description" maxlength="255" value="<?= e((string) $form['description']) ?>" placeholder="e.g. Pack of 12"></label>
            <div class="form-grid">
                <label class="field"><span>Price</span><div class="input-prefix"><span>$</span><input class="input" name="price" inputmode="decimal" value="<?= e((string) $form['price']) ?>" required></div></label>
                <label class="field"><span>Most per order</span><input class="input" type="number" min="1" max="99" name="max_qty" value="<?= (int) $form['max_qty'] ?>"></label>
                <label class="field"><span>Sort order</span><input class="input" type="number" name="sort_order" value="<?= (int) $form['sort_order'] ?>"></label>
            </div>
            <label class="switch"><input type="checkbox" name="is_active" value="1" <?= checked((bool) $form['is_active']) ?>><span class="switch__track"></span><span>Show to customers</span></label>
            <button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> <?= $editing ? 'Save extra' : 'Add extra' ?></button>
        </form>
    </section>
</div>
