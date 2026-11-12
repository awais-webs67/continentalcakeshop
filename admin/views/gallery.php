<?php /** @var array $result @var string $hero @var array $photoStats */ ?>
<?php $showOnCustom = setting('custom_show_gallery', '1') === '1'; ?>
<div class="page-head">
    <div><h1>Gallery</h1><p>Photos of cakes you have made. They fill the <a href="<?= e(url('custom-cake-gallery')) ?>" target="_blank" rel="noopener">Custom Cake Gallery</a> page (Pinterest style, every photo keeps its own shape) and also show on the homepage and the custom cakes page.</p></div>
    <a class="btn btn--secondary btn--sm" href="<?= e(url('custom-cake-gallery')) ?>" target="_blank" rel="noopener">View gallery page <?= icon('arrow', '', 16) ?></a>
</div>
<?php if (query_int('added') > 0): ?>
    <div class="alert alert--success" role="status"><?= query_int('added') === 1 ? 'Photo added to the gallery.' : query_int('added') . ' photos added to the gallery.' ?></div>
<?php endif; ?>
<div class="grid-2">
<section class="card">
    <div class="card__head"><h2>Add photos</h2><span class="hint">Choose many at once</span></div>
    <form class="card__body form-stack" method="post" enctype="multipart/form-data" data-gallery-upload>
        <?= csrf_field() ?><input type="hidden" name="action" value="upload">
        <label class="field"><span>Photos <small>JPG, PNG or WebP, any size: made smaller and saved as WebP for you</small></span><input class="input" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required></label>
        <div class="form-grid">
            <label class="field"><span>Occasion <small>sorts them into the filter buttons</small></span>
                <select class="input" name="occasion">
                    <option value="">Choose one (optional)</option>
                    <?php foreach (CAKE_OCCASIONS as $o): ?><option><?= e($o) ?></option><?php endforeach; ?>
                </select></label>
            <label class="field"><span>Short description <small>optional, used for all photos chosen</small></span><input class="input" name="caption" maxlength="160" placeholder="e.g. Two-tier wedding cake with fresh roses"></label>
        </div>
        <p class="hint">The description is what Google Images reads, so describe the cake: colours, theme, tiers. Empty means "Birthday cake by <?= e(setting('store_short_name')) ?>, Canberra" (with the occasion you chose). You can change each photo's text below after uploading.</p>
        <div><button class="btn btn--primary" type="submit"><?= icon('upload', '', 18) ?> Upload</button></div>
        <p class="hint" data-gallery-progress role="status" hidden></p>
    </form>
</section>
<section class="card" id="gallery-settings">
    <div class="card__head"><h2>Where the photos show</h2></div>
    <form class="card__body form-stack" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="settings">
        <label class="switch"><input type="checkbox" name="custom_show_gallery" value="1" <?= checked($showOnCustom) ?>><span class="switch__track"></span><span><strong>Photos at the bottom of the "Order a custom cake" page</strong><small>Off hides them there. The gallery page and homepage keep their photos.</small></span></label>
        <label class="field" style="max-width:220px"><span>How many photos there</span><input class="input" type="number" min="2" max="24" name="custom_gallery_count" value="<?= (int) setting('custom_gallery_count', '8') ?>"></label>
        <label class="field"><span>Gallery page heading</span><input class="input" name="gallery_title" maxlength="80" value="<?= e(setting('gallery_title')) ?>" placeholder="Custom Cake Gallery"></label>
        <label class="field"><span>Gallery page intro</span><textarea class="input" name="gallery_intro" rows="3" maxlength="400" placeholder="<?= e(GALLERY_INTRO_DEFAULT) ?>"><?= e(setting('gallery_intro')) ?></textarea></label>
        <div><button class="btn btn--secondary btn--sm" type="submit"><?= icon('check', '', 16) ?> Save</button></div>
    </form>
</section>
</div>
<?php $optimisedAt = setting('photos_optimised_at'); ?>
<section class="card photo-tools">
    <div class="card__head"><h2>Photo tools</h2><span class="hint">Every photo on the website</span></div>
    <div class="card__body photo-tools__grid">
        <div class="photo-tools__item" data-photo-optimise>
            <h3>Make photos smaller</h3>
            <p class="hint">New photos are already made small when you upload them. This goes over the <strong><?= (int) $photoStats['count'] ?></strong> photos added in the panel (<?= e(number_format($photoStats['bytes'] / 1048576, 1)) ?> MB: cakes, gallery, categories, pages) and saves each one smaller when that saves at least 15%, at a quality you will not see a difference in. Names and places stay the same, so nothing on the website breaks.<?= $optimisedAt !== '' ? ' Last done ' . e(time_ago($optimisedAt)) . '.' : '' ?></p>
            <form method="post" data-optimise-form><?= csrf_field() ?><input type="hidden" name="action" value="optimise"><button class="btn btn--secondary" type="submit" <?= $photoStats['count'] ? '' : 'disabled' ?>><?= icon('check', '', 16) ?> Make all photos smaller</button></form>
            <div class="progress" data-optimise-progress hidden><div class="progress__bar" data-optimise-bar></div></div>
            <p class="hint" data-optimise-status role="status" aria-live="polite"></p>
        </div>
        <div class="photo-tools__item">
            <h3>Download photos</h3>
            <p class="hint">A ZIP file to keep a copy on your computer or share with a designer.</p>
            <div class="photo-tools__buttons">
                <a class="btn btn--secondary" href="<?= e(admin_url('gallery', ['download' => 'gallery'])) ?>"><?= icon('download', '', 16) ?> Gallery photos (ZIP)</a>
                <a class="btn btn--secondary" href="<?= e(admin_url('gallery', ['download' => 'all'])) ?>"><?= icon('download', '', 16) ?> All website photos (ZIP)</a>
            </div>
        </div>
    </div>
</section>
<section class="card">
    <div class="card__head"><h2>Photos</h2><span class="hint">The outlined photo is the main homepage photo.</span></div>
    <div class="card__body">
        <?php if (!$result['rows']): ?>
            <div class="empty"><?= icon('upload', '', 32) ?><p>No photos yet.</p></div>
        <?php else: ?>
        <div class="gallery-admin">
            <?php foreach ($result['rows'] as $g): ?>
                <?php $back = admin_url('gallery') . page_link($result['page']); ?>
                <figure class="<?= $g['image'] === $hero ? 'is-hero' : '' ?><?= $g['is_active'] ? '' : ' is-hidden' ?>">
                    <img src="<?= e(product_image_url($g['image'])) ?>" alt="<?= e($g['caption']) ?>" loading="lazy">
                    <figcaption>
                        <form method="post" class="form-stack" style="gap:8px">
                            <?= csrf_field() ?><input type="hidden" name="_back" value="<?= e($back) ?>"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>"><input type="hidden" name="action" value="save">
                            <textarea class="input input--sm" name="caption" rows="2" aria-label="Caption"><?= e($g['caption']) ?></textarea>
                            <div style="display:flex;gap:6px;align-items:center"><input class="input input--sm" type="number" name="sort_order" value="<?= (int) $g['sort_order'] ?>" style="width:70px" aria-label="Sort order" title="Sort order"><button class="btn btn--secondary btn--sm" type="submit">Save</button></div>
                        </form>
                        <div class="gallery-admin__actions">
                            <?php if ($g['image'] === $hero): ?>
                                <span class="badge badge--active">Homepage photo</span>
                            <?php else: ?>
                                <form method="post"><?= csrf_field() ?><input type="hidden" name="_back" value="<?= e($back) ?>"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>"><input type="hidden" name="action" value="hero"><button class="link-btn" type="submit">Use on homepage</button></form>
                            <?php endif; ?>
                            <form method="post"><?= csrf_field() ?><input type="hidden" name="_back" value="<?= e($back) ?>"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="link-btn" type="submit"><?= $g['is_active'] ? 'Hide' : 'Show' ?></button></form>
                            <form method="post" data-confirm="Delete this photo?"><?= csrf_field() ?><input type="hidden" name="_back" value="<?= e($back) ?>"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>"><input type="hidden" name="action" value="delete"><button class="link-btn link-btn--danger" type="submit">Delete</button></form>
                        </div>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php require __DIR__ . '/partials/pager.php'; ?>
</section>
