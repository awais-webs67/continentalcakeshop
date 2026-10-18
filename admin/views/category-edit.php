<?php /** @var array $category @var array $errors @var int $id @var int $productCount */ ?>
<a class="crumb" href="<?= e(admin_url('categories')) ?>"><?= icon('arrow', '', 14) ?> Categories</a>
<div class="page-head">
    <div><h1><?= $id ? e($category['name']) : 'Add category' ?></h1><?php if ($id): ?><p><?= $productCount ?> products &middot; <a href="<?= e(category_url($category)) ?>" target="_blank">View on website</a></p><?php endif; ?></div>
</div>
<?php if ($errors): ?><div class="alert alert--error">Please fix the highlighted fields.</div><?php endif; ?>

<form method="post" enctype="multipart/form-data" action="<?= e(admin_url('categories/edit', $id ? ['id' => $id] : [])) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <div class="grid-main">
        <div class="stack">
            <section class="card">
                <div class="card__head"><h2>Details</h2></div>
                <div class="card__body form-grid">
                    <label class="field"><span>Name</span><input class="input" name="name" value="<?= e($category['name']) ?>" required data-slug-source></label>
                    <label class="field"><span>Web address <small>(slug)</small></span><input class="input" name="slug" value="<?= e($category['slug']) ?>" data-slug-target placeholder="made from the name"></label>
                    <?= field_error($errors, 'name') ?>
                    <div class="field span-2"><span class="label">Description <small>shown at the top of the category page</small></span><textarea class="input" name="description" rows="3" data-rich-editor="simple"><?= e((string) $category['description']) ?></textarea></div>
                </div>
            </section>
            <section class="card">
                <div class="card__head"><h2>Search engines</h2></div>
                <div class="card__body form-stack">
                    <label class="field"><span>Page title <small>leave empty to use the category name</small></span><input class="input" name="meta_title" maxlength="160" value="<?= e((string) $category['meta_title']) ?>"></label>
                    <label class="field"><span>Description <small>about 150 characters</small></span><textarea class="input" name="meta_description" rows="2" maxlength="255"><?= e((string) $category['meta_description']) ?></textarea></label>
                </div>
            </section>
        </div>
        <div class="stack">
            <section class="card">
                <div class="card__head"><h2>Tile photo</h2></div>
                <div class="card__body form-stack">
                    <img id="category-preview" src="<?= e(product_image_url($category['image'])) ?>" alt="" style="width:100%;aspect-ratio:5/4;object-fit:cover;border-radius:12px;border:1px solid var(--line)">
                    <input class="input" type="file" name="image" accept="image/jpeg,image/png,image/webp" data-image-input="category-preview">
                    <p class="hint">Used as the background of the category tile. Landscape photos work best.</p>
                    <?= field_error($errors, 'image') ?>
                    <?php if ($category['image']): ?><label class="switch"><input type="checkbox" name="remove_image" value="1"><span class="switch__track"></span><span>Remove photo</span></label><?php endif; ?>
                </div>
            </section>
            <section class="card">
                <div class="card__head"><h2>Visibility</h2></div>
                <div class="card__body form-stack">
                    <label class="switch"><input type="checkbox" name="is_active" value="1" <?= checked((bool) $category['is_active']) ?>><span class="switch__track"></span><span>Show on the website</span></label>
                    <label class="field"><span>Sort order <small>lower shows first</small></span><input class="input" type="number" name="sort_order" value="<?= (int) $category['sort_order'] ?>"></label>
                </div>
            </section>
        </div>
    </div>
    <div class="card" style="margin-top:16px"><div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> Save category</button>
        <a class="btn btn--ghost" href="<?= e(admin_url('categories')) ?>">Cancel</a>
    </div></div>
</form>
<?php if ($id): ?>
    <form method="post" style="margin-top:16px" data-confirm="Delete this category?">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="action" value="delete">
        <button class="btn btn--danger btn--sm" type="submit" <?= $productCount ? 'disabled title="Move its products first"' : '' ?>><?= icon('trash', '', 16) ?> Delete category</button>
        <?php if ($productCount): ?><span class="hint" style="margin-left:8px">A category can be deleted once it has no products.</span><?php endif; ?>
    </form>
<?php endif; ?>
