<?php /** @var array $product @var array $categories @var array $sizeRows @var array $errors @var int $id */ ?>
<a class="crumb" href="<?= e(admin_url('products')) ?>"><?= icon('arrow', '', 14) ?> Products</a>
<div class="page-head">
    <div><h1><?= $id ? e($product['name']) : 'Add product' ?></h1><?php if ($id): ?><p><a href="<?= e(url('cakes/' . $product['slug'])) ?>" target="_blank">View on website</a></p><?php endif; ?></div>
</div>
<?php if ($errors): ?><div class="alert alert--error">Please fix the highlighted fields.</div><?php endif; ?>

<form method="post" enctype="multipart/form-data" action="<?= e(admin_url('products/edit', $id ? ['id' => $id] : [])) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <div class="grid-main">
        <div class="stack">
            <section class="card">
                <div class="card__head"><h2>Details</h2></div>
                <div class="card__body form-grid">
                    <label class="field span-2"><span>Name</span><input class="input" name="name" value="<?= e($product['name']) ?>" required data-slug-source></label>
                    <?= field_error($errors, 'name') ?>
                    <label class="field"><span>Category</span>
                        <select class="input" name="category_id" required>
                            <option value="">Choose a category</option>
                            <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= selected((int) $product['category_id'] === (int) $c['id']) ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field"><span>Web address <small>(slug)</small></span><input class="input" name="slug" value="<?= e($product['slug']) ?>" data-slug-target placeholder="made from the name"></label>
                    <?= field_error($errors, 'category_id') ?>
                    <label class="field span-2"><span>Short description <small>shown under the name</small></span><input class="input" name="short_description" maxlength="255" value="<?= e((string) $product['short_description']) ?>"></label>
                    <label class="field span-2"><span>Full description <small>optional, shown in "About this cake"</small></span><textarea class="input" name="description" rows="4"><?= e((string) $product['description']) ?></textarea></label>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><h2>Sizes and prices</h2><span class="hint">Standard cakes: Midi serves 8, Large serves 14</span></div>
                <div class="card__body" data-repeater>
                    <div class="repeater">
                        <div class="repeater__row repeater__row--head"><span>Size name</span><span>Serves <small>(optional)</small></span><span>Price (AUD)</span><span></span></div>
                        <div data-repeater-list class="repeater">
                            <?php foreach ($sizeRows as $i => $size): ?>
                                <div class="repeater__row">
                                    <input type="hidden" name="sizes[<?= $i ?>][id]" value="<?= (int) $size['id'] ?>">
                                    <input class="input" name="sizes[<?= $i ?>][label]" value="<?= e($size['label']) ?>" placeholder="e.g. Full">
                                    <input class="input" name="sizes[<?= $i ?>][servings]" value="<?= e((string) $size['servings']) ?>" placeholder="e.g. Serves 10 to 12">
                                    <div class="input-prefix"><span>$</span><input class="input" name="sizes[<?= $i ?>][price]" value="<?= e((string) $size['price']) ?>" inputmode="decimal" placeholder="0.00"></div>
                                    <button type="button" class="repeater__remove" data-repeater-remove aria-label="Remove size"><?= icon('trash', '', 16) ?></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <template>
                        <div class="repeater__row">
                            <input type="hidden" name="sizes[__INDEX__][id]" value="0">
                            <input class="input" name="sizes[__INDEX__][label]" placeholder="e.g. Midi">
                            <input class="input" name="sizes[__INDEX__][servings]" placeholder="e.g. Serves 6 to 8">
                            <div class="input-prefix"><span>$</span><input class="input" name="sizes[__INDEX__][price]" inputmode="decimal" placeholder="0.00"></div>
                            <button type="button" class="repeater__remove" data-repeater-remove aria-label="Remove size"><?= icon('trash', '', 16) ?></button>
                        </div>
                    </template>
                    <?= field_error($errors, 'sizes') ?>
                    <p style="margin:12px 0 0"><button type="button" class="btn btn--secondary btn--sm" data-repeater-add><?= icon('plus', '', 16) ?> Add a size</button></p>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><h2>Search engines</h2></div>
                <div class="card__body form-stack">
                    <label class="field"><span>Page title <small>leave empty to use the product name</small></span><input class="input" name="meta_title" maxlength="160" value="<?= e((string) $product['meta_title']) ?>"></label>
                    <label class="field"><span>Description <small>about 150 characters</small></span><textarea class="input" name="meta_description" rows="2" maxlength="255"><?= e((string) $product['meta_description']) ?></textarea></label>
                </div>
            </section>
        </div>

        <div class="stack">
            <section class="card">
                <div class="card__head"><h2>Photo</h2></div>
                <div class="card__body form-stack">
                    <img id="product-preview" src="<?= e(product_image_url($product['image'])) ?>" alt="" style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:12px;border:1px solid var(--line)">
                    <input class="input" type="file" name="image" accept="image/jpeg,image/png,image/webp" data-image-input="product-preview">
                    <p class="hint">Square photos look best. Large photos are resized automatically.</p>
                    <?= field_error($errors, 'image') ?>
                    <?php if ($product['image']): ?><label class="switch"><input type="checkbox" name="remove_image" value="1"><span class="switch__track"></span><span>Remove photo</span></label><?php endif; ?>
                </div>
            </section>
            <section class="card">
                <div class="card__head"><h2>Visibility</h2></div>
                <div class="card__body form-stack">
                    <label class="switch"><input type="checkbox" name="is_active" value="1" <?= checked((bool) $product['is_active']) ?>><span class="switch__track"></span><span>Show in the shop</span></label>
                    <label class="switch"><input type="checkbox" name="is_featured" value="1" <?= checked((bool) $product['is_featured']) ?>><span class="switch__track"></span><span>Feature on the homepage</span></label>
                    <label class="switch"><input type="checkbox" name="allow_message" value="1" <?= checked((bool) $product['allow_message']) ?>><span class="switch__track"></span><span>Allow a message on the cake</span></label>
                    <label class="switch"><input type="checkbox" name="is_enquiry" value="1" <?= checked((bool) $product['is_enquiry']) ?>><span class="switch__track"></span><span>Price on request (special)</span></label>
                    <p class="hint" style="margin-top:-6px">Specials show an "Enquire" button instead of Add to Cart. Sizes are optional for them.</p>
                    <label class="field"><span>Badge <small>e.g. Customer favourite</small></span><input class="input" name="badge" maxlength="40" value="<?= e((string) $product['badge']) ?>"></label>
                    <label class="field"><span>Notice needed (hours) <small>empty = shop default</small></span><input class="input" type="number" min="0" name="lead_time_hours" value="<?= e((string) $product['lead_time_hours']) ?>"></label>
                    <label class="field"><span>Sort order <small>lower shows first</small></span><input class="input" type="number" name="sort_order" value="<?= (int) $product['sort_order'] ?>"></label>
                </div>
            </section>
        </div>
    </div>
    <div class="card" style="margin-top:16px"><div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> Save product</button>
        <a class="btn btn--ghost" href="<?= e(admin_url('products')) ?>">Cancel</a>
    </div></div>
</form>
<?php if ($id): ?>
    <form method="post" style="margin-top:16px" data-confirm="Delete this product? Past orders keep their details.">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="action" value="delete">
        <button class="btn btn--danger btn--sm" type="submit"><?= icon('trash', '', 16) ?> Delete product</button>
    </form>
<?php endif; ?>
