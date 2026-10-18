<?php /** @var array $reviews @var ?array $editing */
$form = $editing ?? ['id' => 0, 'author' => '', 'source' => 'Google', 'body' => '', 'is_active' => 1, 'sort_order' => count($reviews) + 1];
?>
<div class="page-head">
    <div><h1>Reviews</h1><p>Customer reviews shown on the homepage. Copy your best Google reviews here. The first four visible reviews are shown.</p></div>
</div>
<div class="grid-main">
    <section class="card">
        <div class="card__head"><h2>All reviews</h2><span class="hint">Rating shown: <?= e(setting('google_rating')) ?> from <?= e(setting('google_review_count')) ?> reviews (change in Settings)</span></div>
        <?php if (!$reviews): ?>
            <div class="empty"><?= icon('star', '', 32) ?><p>No reviews yet.</p></div>
        <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Review</th><th>Status</th><th class="num">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($reviews as $r): ?>
                <tr>
                    <td><strong><?= e($r['author']) ?></strong> <span class="muted">&middot; <?= e($r['source']) ?> &middot; #<?= (int) $r['sort_order'] ?></span>
                        <div style="max-width:560px"><?= e(mb_strimwidth($r['body'], 0, 160, '...')) ?></div></td>
                    <td><?= $r['is_active'] ? status_badge('active', 'Visible') : status_badge('inactive', 'Hidden') ?></td>
                    <td class="num">
                        <a class="link-btn" href="<?= e(admin_url('reviews', ['edit' => $r['id']])) ?>">Edit</a>
                        <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="link-btn" type="submit"><?= $r['is_active'] ? 'Hide' : 'Show' ?></button></form>
                        <form method="post" style="display:inline" data-confirm="Delete this review?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="delete"><button class="link-btn link-btn--danger" type="submit">Delete</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </section>
    <section class="card" id="review-form">
        <div class="card__head"><h2><?= $editing ? 'Edit review' : 'Add a review' ?></h2><?php if ($editing): ?><a href="<?= e(admin_url('reviews')) ?>">Cancel</a><?php endif; ?></div>
        <form class="card__body form-stack" method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
            <label class="field"><span>Customer name</span><input class="input" name="author" value="<?= e($form['author']) ?>" required placeholder="e.g. Sarah M."></label>
            <label class="field"><span>Review</span><textarea class="input" name="body" rows="5" required><?= e($form['body']) ?></textarea></label>
            <div class="form-grid">
                <label class="field"><span>Source</span><input class="input" name="source" value="<?= e($form['source']) ?>"></label>
                <label class="field"><span>Sort order</span><input class="input" type="number" name="sort_order" value="<?= (int) $form['sort_order'] ?>"></label>
            </div>
            <label class="switch"><input type="checkbox" name="is_active" value="1" <?= checked((bool) $form['is_active']) ?>><span class="switch__track"></span><span>Show on the website</span></label>
            <button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> <?= $editing ? 'Save review' : 'Add review' ?></button>
        </form>
    </section>
</div>
