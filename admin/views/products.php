<?php /** @var array $result @var array $categories */ ?>
<div class="page-head">
    <div><h1>Products</h1><p>Cakes shown in the shop, with their sizes and prices.</p></div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= e(admin_url('products/import')) ?>"><?= icon('upload', '', 18) ?> Import from Excel</a>
        <a class="btn btn--primary" href="<?= e(admin_url('products/edit')) ?>"><?= icon('plus', '', 18) ?> Add product</a>
    </div>
</div>
<section class="card">
    <form class="toolbar" method="get" action="<?= e(admin_url('products')) ?>">
        <input class="input grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Search products">
        <select class="input" name="category" data-auto-submit>
            <option value="">All categories</option>
            <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= selected($category === (int) $c['id']) ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn--secondary" type="submit">Search</button>
    </form>
    <?php if (!$result['rows']): ?>
        <div class="empty"><?= icon('cake', '', 32) ?><p>No products found.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table class="table">
        <thead><tr><th></th><th>Product</th><th>Category</th><th>Price</th><th>Status</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($result['rows'] as $p): ?>
            <tr>
                <td style="width:60px"><img class="thumb" src="<?= e(product_image_url($p['image'])) ?>" alt="" loading="lazy"></td>
                <td><a class="row-link" href="<?= e(admin_url('products/edit', ['id' => $p['id']])) ?>"><?= e($p['name']) ?></a>
                    <div class="muted"><?= $p['is_featured'] ? 'Featured on homepage' : '' ?><?= $p['badge'] ? ($p['is_featured'] ? ' &middot; ' : '') . e($p['badge']) : '' ?></div></td>
                <td><?= e($p['category_name']) ?></td>
                <td><?= $p['is_enquiry'] ? '<span class="muted">Price on request</span>' : ($p['size_count'] ? e(money($p['price_from'])) . ((int) $p['size_count'] > 1 ? ' <span class="muted">+' . ((int) $p['size_count'] - 1) . ' sizes</span>' : '') : '<span class="badge badge--cancelled">No sizes</span>') ?></td>
                <td><?= $p['is_active'] ? status_badge('active', 'Visible') : status_badge('inactive', 'Hidden') ?><?= product_sold_out($p) ? ' ' . status_badge('pending', 'Sold out') : '' ?></td>
                <td class="num">
                    <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="_back" value="<?= e(admin_url('products') . page_link($result['page'])) ?>"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="link-btn" type="submit"><?= $p['is_active'] ? 'Hide' : 'Show' ?></button></form>
                    &nbsp;<a class="link-btn" href="<?= e(admin_url('products/edit', ['id' => $p['id']])) ?>">Edit</a>
                    &nbsp;<a class="link-btn" href="<?= e(product_url($p)) ?>" target="_blank">View</a>
                    &nbsp;<form method="post" action="<?= e(admin_url('products/edit', ['id' => $p['id']])) ?>" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="action" value="duplicate"><button class="link-btn" type="submit">Duplicate</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
    <?php require __DIR__ . '/partials/pager.php'; ?>
</section>
