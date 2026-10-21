<?php /** @var array $product @var array $categories @var array $sizeRows @var array $optionRows @var array $extraPhotos @var array $errors @var int $id */
$seoChecks = product_seo_checks($product + ['slug' => $product['slug'] ?? ''], count($extraPhotos));
[$seoPassed, $seoTotal, $seoLevel] = seo_score($seoChecks);
$sizesForSeo = array_values(array_filter($sizeRows, static fn ($r) => ($r['label'] ?? '') !== '' && (float) ($r['price'] ?? 0) > 0));
?>
<a class="crumb" href="<?= e(admin_url('products')) ?>"><?= icon('arrow', '', 14) ?> Products</a>
<div class="page-head">
    <div><h1><?= $id ? e($product['name']) : 'Add product' ?></h1><?php if ($id): ?><p><a href="<?= e(url('cakes/' . $product['slug'])) ?>" target="_blank">View on website</a><?= !$product['is_active'] ? ' &middot; ' . status_badge('inactive', 'Hidden') : '' ?></p><?php endif; ?></div>
    <?php if ($id): ?>
        <div class="page-head__actions">
            <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="action" value="duplicate"><button class="btn btn--secondary" type="submit"><?= icon('copy', '', 18) ?> Duplicate</button></form>
            <a class="btn btn--primary" href="<?= e(admin_url('products/edit')) ?>"><?= icon('plus', '', 18) ?> Add another</a>
        </div>
    <?php endif; ?>
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
                    <div class="field span-2"><span class="label">Full description <small>optional, shown in "About this cake"</small></span><textarea class="input" name="description" rows="4" data-rich-editor="simple"><?= e((string) $product['description']) ?></textarea></div>
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
                <div class="card__head"><h2>Options</h2><span class="hint">Optional choices that change the price, e.g. Eggless +$5</span></div>
                <div class="card__body" data-repeater>
                    <div class="repeater">
                        <div class="repeater__row repeater__row--options repeater__row--head"><span>Option</span><span>Extra price (AUD)</span><span></span></div>
                        <div data-repeater-list class="repeater">
                            <?php foreach ($optionRows as $i => $option): ?>
                                <div class="repeater__row repeater__row--options">
                                    <input type="hidden" name="options[<?= $i ?>][id]" value="<?= (int) $option['id'] ?>">
                                    <input class="input" name="options[<?= $i ?>][name]" value="<?= e($option['name']) ?>" maxlength="80" placeholder="e.g. Eggless">
                                    <div class="input-prefix"><span>+$</span><input class="input" name="options[<?= $i ?>][price]" value="<?= e((string) $option['price']) ?>" inputmode="decimal" placeholder="0.00"></div>
                                    <button type="button" class="repeater__remove" data-repeater-remove aria-label="Remove option"><?= icon('trash', '', 16) ?></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <template>
                        <div class="repeater__row repeater__row--options">
                            <input type="hidden" name="options[__INDEX__][id]" value="0">
                            <input class="input" name="options[__INDEX__][name]" maxlength="80" placeholder="e.g. Eggless">
                            <div class="input-prefix"><span>+$</span><input class="input" name="options[__INDEX__][price]" inputmode="decimal" placeholder="0.00"></div>
                            <button type="button" class="repeater__remove" data-repeater-remove aria-label="Remove option"><?= icon('trash', '', 16) ?></button>
                        </div>
                    </template>
                    <?= field_error($errors, 'options') ?>
                    <p style="margin:12px 0 0"><button type="button" class="btn btn--secondary btn--sm" data-repeater-add><?= icon('plus', '', 16) ?> Add an option</button></p>
                    <p class="hint" style="margin:8px 0 0">Customers tick options on the cake page. Use 0 for a free choice.</p>
                </div>
            </section>

            <section class="card" id="seo" data-seo-box data-store="<?= e(setting('store_short_name')) ?>" data-base-url="<?= e(url('cakes/')) ?>">
                <div class="card__head"><h2>Google and search engines</h2><span class="seo-score seo-score--<?= e($seoLevel) ?>" data-seo-score><?= $seoPassed ?>/<?= $seoTotal ?></span></div>
                <div class="card__body form-stack">
                    <label class="field"><span>Focus keyword <small>what people type into Google, e.g. "red velvet cake canberra"</small></span>
                        <input class="input" name="focus_keyword" maxlength="80" value="<?= e((string) ($product['focus_keyword'] ?? '')) ?>" placeholder="e.g. <?= e(mb_strtolower($product['name'] !== '' ? $product['name'] : 'chocolate cake')) ?> canberra" data-seo-keyword></label>
                    <div class="serp serp--live" aria-label="Preview of the Google result">
                        <span class="serp__url" data-seo-url><?= e(url('cakes/' . ($product['slug'] ?: 'your-cake'))) ?></span>
                        <span class="serp__title" data-seo-title-preview></span>
                        <span class="serp__desc" data-seo-desc-preview></span>
                    </div>
                    <label class="field"><span>Page title <small>empty = "<?= e(product_default_title(['name' => $product['name'] ?: 'Cake name'])) ?>"</small></span>
                        <input class="input" name="meta_title" maxlength="160" value="<?= e((string) $product['meta_title']) ?>" data-count data-seo-title data-default="<?= e(product_default_title(['name' => '__NAME__'])) ?>"></label>
                    <label class="field"><span>Search description <small>120 to 160 characters</small></span>
                        <textarea class="input" name="meta_description" rows="3" maxlength="255" data-count data-seo-desc data-default="<?= e(product_default_description(['name' => '__NAME__', 'is_enquiry' => $product['is_enquiry']], $sizesForSeo)) ?>"><?= e((string) $product['meta_description']) ?></textarea></label>
                    <p style="margin:-4px 0 0"><button type="button" class="btn btn--secondary btn--sm" data-seo-fill><?= icon('pen', '', 16) ?> Fill in for me</button> <span class="hint">Writes a title and description from the name, keyword and prices. Edit them as you like.</span></p>
                    <ul class="seo-checks" data-seo-checks>
                        <?php foreach ($seoChecks as [$label, $ok, $tip]): ?>
                            <li class="<?= $ok ? 'is-ok' : 'is-todo' ?>"><?= icon($ok ? 'check' : 'info', '', 16) ?><span><strong><?= e($label) ?></strong><?php if (!$ok): ?><small><?= e($tip) ?></small><?php endif; ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        </div>

        <div class="stack">
            <section class="card">
                <div class="card__head"><h2>Photos</h2><span class="hint"><?= 1 + count($extraPhotos) ?> max 10</span></div>
                <div class="card__body form-stack">
                    <img id="product-preview" src="<?= e(product_image_url($product['image'])) ?>" alt="" style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:12px;border:1px solid var(--line)">
                    <input class="input" type="file" name="image" accept="image/jpeg,image/png,image/webp" data-image-input="product-preview">
                    <label class="field"><span>Describe the main photo <small>alt text for Google Images</small></span><input class="input" name="image_alt" maxlength="160" value="<?= e((string) ($product['image_alt'] ?? '')) ?>" placeholder="e.g. Red velvet cake with cream cheese frosting" data-seo-alt></label>
                    <p class="hint">Square photos look best. Large photos are resized and saved as WebP automatically.</p>
                    <?= field_error($errors, 'image') ?>
                    <?php if ($product['image']): ?><label class="switch"><input type="checkbox" name="remove_image" value="1"><span class="switch__track"></span><span>Remove main photo</span></label><?php endif; ?>

                    <p class="label" style="margin:8px 0 0">More photos <small>slice, side view, sizes</small></p>
                    <?php if ($extraPhotos): ?>
                        <div class="photo-list">
                            <?php foreach ($extraPhotos as $photo): ?>
                                <div class="photo-row">
                                    <img src="<?= e(product_image_url($photo['image'])) ?>" alt="" width="64" height="64" loading="lazy">
                                    <div class="photo-row__fields">
                                        <input class="input" name="photos[<?= (int) $photo['id'] ?>][alt]" maxlength="160" value="<?= e((string) $photo['alt']) ?>" placeholder="Describe this photo">
                                        <div class="photo-row__actions">
                                            <label class="mini-field">Order <input class="input" type="number" name="photos[<?= (int) $photo['id'] ?>][sort]" value="<?= (int) $photo['sort_order'] ?>"></label>
                                            <label class="mini-check"><input type="radio" name="make_main" value="<?= (int) $photo['id'] ?>"> Main</label>
                                            <label class="mini-check mini-check--danger"><input type="checkbox" name="photos[<?= (int) $photo['id'] ?>][remove]" value="1"> Remove</label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (count($extraPhotos) < 9): ?>
                        <input class="input" type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple data-seo-gallery>
                        <p class="hint" style="margin:0">You can choose several photos at once.</p>
                    <?php endif; ?>
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
                    <?php $soldOutMode = !product_sold_out($product) ? 'no' : (($product['sold_out_until'] ?? '') === SOLD_OUT_FOREVER ? 'forever' : (($product['sold_out_until'] ?? '') === date('Y-m-d') ? 'today' : 'until')); ?>
                    <label class="field"><span>Sold out</span>
                        <select class="input" name="sold_out" data-sold-out>
                            <option value="no" <?= selected($soldOutMode === 'no') ?>>No, customers can order it</option>
                            <option value="today" <?= selected($soldOutMode === 'today') ?>>Sold out today (back tomorrow)</option>
                            <option value="until" <?= selected($soldOutMode === 'until') ?>>Sold out until a date</option>
                            <option value="forever" <?= selected($soldOutMode === 'forever') ?>>Sold out until I switch it back</option>
                        </select>
                    </label>
                    <label class="field" data-sold-out-date <?= $soldOutMode === 'until' ? '' : 'hidden' ?>><span>Last sold out day</span><input class="input" type="date" name="sold_out_until" min="<?= e(date('Y-m-d')) ?>" value="<?= e($soldOutMode === 'until' ? (string) $product['sold_out_until'] : '') ?>"></label>
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
