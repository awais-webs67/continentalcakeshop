<?php /** @var array $categories */ ?>
<div class="page-head">
    <div><h1>Categories</h1><p>The groups shown in "Shop by category" and the shop filters.</p></div>
    <div class="page-head__actions"><a class="btn btn--primary" href="<?= e(admin_url('categories/edit')) ?>"><?= icon('plus', '', 18) ?> Add category</a></div>
</div>
<section class="card">
    <?php if (!$categories): ?>
        <div class="empty"><?= icon('menu', '', 32) ?><p>No categories yet.</p></div>
    <?php else: ?>
    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="sort">
        <div class="table-wrap"><table class="table">
            <thead><tr><th></th><th>Category</th><th>Products</th><th>Status</th><th style="width:110px">Sort order</th><th class="num">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td style="width:76px"><img class="thumb" src="<?= e(product_image_url($c['image'])) ?>" alt="" loading="lazy"></td>
                    <td><a class="row-link" href="<?= e(admin_url('categories/edit', ['id' => $c['id']])) ?>"><?= e($c['name']) ?></a>
                        <div class="muted">/shop/<?= e($c['slug']) ?></div></td>
                    <td><?= (int) $c['product_count'] ?> <span class="muted">(<?= (int) $c['visible_count'] ?> visible)</span></td>
                    <td><?= $c['is_active'] ? status_badge('active', 'Visible') : status_badge('inactive', 'Hidden') ?></td>
                    <td><input class="input" type="number" name="sort[<?= (int) $c['id'] ?>]" value="<?= (int) $c['sort_order'] ?>" style="width:90px"></td>
                    <td class="num">
                        <button class="link-btn" type="submit" form="toggle-<?= (int) $c['id'] ?>"><?= $c['is_active'] ? 'Hide' : 'Show' ?></button>
                        &nbsp;<a class="link-btn" href="<?= e(admin_url('categories/edit', ['id' => $c['id']])) ?>">Edit</a>
                        &nbsp;<a class="link-btn" href="<?= e(category_url($c)) ?>" target="_blank">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <div class="form-actions"><button class="btn btn--secondary btn--sm" type="submit">Save sort order</button><span class="hint">Lower numbers show first.</span></div>
    </form>
    <?php foreach ($categories as $c): ?>
        <form id="toggle-<?= (int) $c['id'] ?>" method="post" hidden><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"></form>
    <?php endforeach; ?>
    <?php endif; ?>
</section>
