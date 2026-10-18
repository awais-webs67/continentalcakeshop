<?php /** @var array $result @var string $hero */ ?>
<div class="page-head">
    <div><h1>Gallery</h1><p>Photos of past cakes. They appear on the custom cakes page and the homepage. Portrait photos (3:4) look best.</p></div>
</div>
<section class="card">
    <div class="card__head"><h2>Add a photo</h2></div>
    <form class="card__body toolbar toolbar--plain" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="action" value="upload">
        <input class="input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
        <input class="input grow" name="caption" maxlength="160" placeholder="Short description, e.g. Two-tier wedding cake with fresh roses">
        <button class="btn btn--primary" type="submit"><?= icon('upload', '', 18) ?> Upload</button>
    </form>
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
