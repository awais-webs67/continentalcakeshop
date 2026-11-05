<?php /** @var array $stats @var array $staff @var ?array $editing */
$f = static fn (string $field): string => e(old($field, (string) ($editing[$field] ?? '')));
?>
<div class="page-head">
    <div><h1>Branches</h1><p>Your shops. Customers choose one when they order, and each branch gets its own orders, custom cake requests and messages by email and in its own view of this panel. Cakes, prices, hours, coupons and payments are shared.</p></div>
    <a class="btn btn--primary btn--sm" href="<?= e(admin_url('branches', ['edit' => 'new'])) ?>#branch-form"><?= icon('plus', '', 16) ?> Add a branch</a>
</div>

<div class="branch-grid">
    <?php foreach (branches() as $b): $s = $stats[(int) $b['id']] ?? []; ?>
        <section class="card branch-card<?= $b['is_active'] ? '' : ' is-off' ?>">
            <div class="card__head">
                <h2><?= e($b['name']) ?></h2>
                <span class="branch-card__badges">
                    <?php if ($b['is_main']): ?><?= status_badge('active', 'Main shop') ?><?php endif; ?>
                    <?= $b['is_active'] ? status_badge('active', 'Shown to customers') : status_badge('inactive', 'Hidden') ?>
                </span>
            </div>
            <div class="card__body">
                <p class="branch-card__addr"><?= icon('pin', '', 16) ?> <?= e(branch_address_line($b)) ?><?= trim($b['street']) === '' ? ' <span class="badge badge--pending">Street missing</span>' : '' ?></p>
                <p class="branch-card__addr"><?= icon('phone', '', 16) ?> <?= e(branch_phone($b)) ?><?= trim($b['phone']) === '' && !$b['is_main'] ? ' <span class="muted">(main shop number for now)</span>' : '' ?></p>
                <p class="branch-card__addr"><?= icon('mail', '', 16) ?> Orders emailed to <strong><?= e(branch_notify_email((int) $b['id'])) ?></strong><?= trim($b['email']) === '' ? ' <span class="muted">(Settings &gt; Emails)</span>' : '' ?></p>
                <dl class="branch-stats">
                    <div><dt>Due today</dt><dd><?= (int) ($s['due_today'] ?? 0) ?></dd></div>
                    <div><dt>New orders</dt><dd><?= (int) ($s['new_orders'] ?? 0) ?></dd></div>
                    <div><dt>This month</dt><dd><?= e(money((float) ($s['month_sales'] ?? 0))) ?><small><?= (int) ($s['month_orders'] ?? 0) ?> orders</small></dd></div>
                    <div><dt>Open requests</dt><dd><?= (int) ($s['requests'] ?? 0) ?></dd></div>
                    <div><dt>Unread messages</dt><dd><?= (int) ($s['unread'] ?? 0) ?></dd></div>
                </dl>
                <div class="branch-card__staff">
                    <span class="label">Staff logins for this branch</span>
                    <?php if (empty($staff[(int) $b['id']])): ?>
                        <p class="hint">None yet. <a href="<?= e(admin_url('admins')) ?>#add-staff">Add one</a> and choose <?= e($b['name']) ?> as the branch.</p>
                    <?php else: ?>
                        <ul><?php foreach ($staff[(int) $b['id']] as $person): ?><li><?= e($person['name']) ?> <span class="muted"><?= e($person['email']) ?></span><?= $person['is_active'] ? '' : ' ' . status_badge('inactive', 'Disabled') ?></li><?php endforeach; ?></ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-actions">
                <a class="btn btn--secondary btn--sm" href="<?= e(admin_url('branches', ['edit' => $b['id']])) ?>#branch-form"><?= icon('pen', '', 16) ?> Edit</a>
                <form method="post" action="<?= e(admin_url('branch-view')) ?>"><?= csrf_field() ?><input type="hidden" name="branch" value="<?= (int) $b['id'] ?>"><input type="hidden" name="_back" value="<?= e(admin_url('orders')) ?>"><button class="btn btn--secondary btn--sm" type="submit"><?= icon('bag', '', 16) ?> Open its orders</button></form>
                <?php if ($b['is_active']): ?><a class="link-btn" href="<?= e(branch_url($b)) ?>" target="_blank" rel="noopener">Branch page</a><?php endif; ?>
                <?php if (!$b['is_main']): ?>
                    <form method="post" <?= $b['is_active'] ? 'data-confirm="Hide ' . e($b['name']) . ' from customers? Its orders stay in the panel."' : '' ?>><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="link-btn" type="submit"><?= $b['is_active'] ? 'Hide from customers' : 'Show to customers' ?></button></form>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<?php if (!empty($staff[0])): ?>
<section class="card">
    <div class="card__head"><h2>See every branch</h2><span class="hint">Owners and staff without a branch</span></div>
    <div class="card__body"><p class="hint" style="margin:0"><?= e(implode(', ', array_map(static fn ($p) => $p['name'], $staff[0]))) ?>. They switch between branches with the branch menu at the top of the panel.</p></div>
</section>
<?php endif; ?>

<?php if ($editing): ?>
<section class="card" id="branch-form">
    <div class="card__head"><h2><?= $editing['id'] ? 'Edit ' . e($editing['name']) : 'Add a branch' ?></h2><a href="<?= e(admin_url('branches')) ?>">Close</a></div>
    <form class="card__body form-stack" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
        <?php if ($editing['is_main']): ?><div class="alert alert--info">This is the main shop. Its address and phone are also the ones in Settings &gt; Shop details, and they change together.</div><?php endif; ?>
        <div class="form-grid">
            <label class="field"><span>Name customers see <small>e.g. Wright</small></span><input class="input" name="name" maxlength="80" value="<?= $f('name') ?>" required></label>
            <label class="field"><span>Web address of its page <small>/locations/...</small></span><input class="input" name="slug" maxlength="60" value="<?= $f('slug') ?>" placeholder="wright"></label>
        </div>
        <label class="field"><span>Street <small>e.g. Shop 2, 10 Cooyong St</small></span><input class="input" name="street" maxlength="120" value="<?= $f('street') ?>"></label>
        <div class="form-grid form-grid--3">
            <label class="field"><span>Suburb</span><input class="input" name="suburb" maxlength="80" value="<?= $f('suburb') ?>" required></label>
            <label class="field"><span>State</span><input class="input" name="state" maxlength="10" value="<?= $f('state') ?>"></label>
            <label class="field"><span>Postcode</span><input class="input" name="postcode" maxlength="10" inputmode="numeric" value="<?= $f('postcode') ?>"></label>
        </div>
        <div class="form-grid">
            <label class="field"><span>Phone</span><input class="input" type="tel" name="phone" maxlength="40" value="<?= $f('phone') ?>"></label>
            <label class="field"><span>Email for its orders <small>empty = Settings &gt; Emails</small></span><input class="input" type="email" name="email" maxlength="190" value="<?= $f('email') ?>" placeholder="wright@yourshop.com.au"></label>
        </div>
        <p class="hint">New orders, custom cake requests and customer messages for this branch are emailed here, and customers' replies come back here.</p>
        <div class="form-grid">
            <label class="field"><span>Map position: latitude</span><input class="input" name="latitude" maxlength="12" value="<?= $f('latitude') ?>" placeholder="-35.3222"></label>
            <label class="field"><span>Map position: longitude</span><input class="input" name="longitude" maxlength="12" value="<?= $f('longitude') ?>" placeholder="149.0347"></label>
        </div>
        <p class="hint">Used for "nearest to you" at checkout and by Google. In Google Maps, right-click the shop and click the numbers at the top to copy them.</p>
        <label class="field"><span>Google Maps / Business Profile link <small>optional</small></span><input class="input" type="url" name="maps_url" maxlength="500" value="<?= $f('maps_url') ?>" placeholder="https://maps.app.goo.gl/..."></label>
        <label class="field"><span>Google review link <small>for the "how was your cake" email</small></span><input class="input" type="url" name="reviews_url" maxlength="500" value="<?= $f('reviews_url') ?>" placeholder="https://g.page/r/.../review"></label>
        <div class="field"><span class="label">About this branch <small>shown on its page, helps Google: parking, nearby suburbs, what makes it special</small></span><textarea class="input" name="intro" data-rich-editor="simple"><?= $f('intro') ?></textarea></div>
        <div class="form-grid">
            <label class="field"><span>Photo of the shop <small>optional, wide photos look best</small></span><input class="input" type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
            <label class="field"><span>Order in lists</span><input class="input" type="number" name="sort_order" value="<?= (int) $editing['sort_order'] ?>"></label>
        </div>
        <?php if (!empty($editing['photo'])): ?>
            <div class="branch-photo"><img src="<?= e(product_image_url($editing['photo'])) ?>" alt="" width="240" height="180"><label class="switch"><input type="checkbox" name="remove_photo" value="1"><span class="switch__track"></span><span>Remove photo</span></label></div>
        <?php endif; ?>
        <?php if (!$editing['is_main']): ?>
            <label class="switch"><input type="checkbox" name="is_active" value="1" <?= checked((bool) old('is_active', (string) $editing['is_active'])) ?>><span class="switch__track"></span><span><strong>Show to customers</strong><small>Customers can choose it at checkout and on the forms, and it gets its page for Google. Needs the street and phone.</small></span></label>
        <?php else: ?>
            <input type="hidden" name="is_active" value="1">
        <?php endif; ?>
        <div><button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> Save branch</button></div>
    </form>
</section>
<?php endif; ?>
