<?php
/**
 * Edit one branch. Owners (Admin > Branches) see every field; a branch manager (My shop) can
 * change the phone, order email, Google links and the branch page, not the address or name.
 *
 * @var array $editing  @var bool $limited  @var array $admin
 */
$f = static fn (string $field): string => e(old($field, (string) ($editing[$field] ?? '')));
$isNew = !(int) $editing['id'];
$back = $limited ? admin_url() : admin_url('branches');
$pageUrl = $isNew ? '' : branch_url($editing);
?>
<a class="crumb" href="<?= e($back) ?>"><?= icon('arrow', '', 14) ?> <?= $limited ? 'Dashboard' : 'Branches' ?></a>
<div class="page-head">
    <div>
        <h1><?= $isNew ? 'Add a branch' : ($limited ? 'My shop: ' : '') . e($editing['name']) ?></h1>
        <p><?= $limited ? 'The phone, order email and page of your shop. The address and name are changed by the owner.' : 'What customers and Google see for this shop, and where its orders are emailed.' ?></p>
    </div>
    <?php if (!$isNew && $editing['is_active']): ?>
        <div class="page-head__actions"><a class="btn btn--secondary" href="<?= e($pageUrl) ?>" target="_blank" rel="noopener">View its page <?= icon('arrow', '', 16) ?></a></div>
    <?php endif; ?>
</div>

<form class="branch-form" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
    <div class="branch-form__main">
        <section class="card">
            <div class="card__head"><h2>Shop details</h2></div>
            <div class="card__body form-stack">
                <?php if ($editing['is_main'] && !$limited): ?><p class="hint" style="margin:0">This is the main shop: its address and phone are also the ones in Settings &gt; Shop details, and they change together.</p><?php endif; ?>
                <?php if ($limited): ?>
                    <div class="branch-form__readonly"><span><?= icon('pin', '', 16) ?> <?= e(branch_address_line($editing)) ?></span></div>
                <?php else: ?>
                    <label class="field"><span>Shop name <small>what customers see, e.g. Wright</small></span><input class="input" name="name" maxlength="80" value="<?= $f('name') ?>" required></label>
                    <label class="field"><span>Street</span><input class="input" name="street" maxlength="120" value="<?= $f('street') ?>" placeholder="e.g. Shop 2, 10 Cooyong Crescent"></label>
                    <div class="form-grid form-grid--3">
                        <label class="field"><span>Suburb</span><input class="input" name="suburb" maxlength="80" value="<?= $f('suburb') ?>" required></label>
                        <label class="field"><span>State</span><input class="input" name="state" maxlength="10" value="<?= $f('state') ?>"></label>
                        <label class="field"><span>Postcode</span><input class="input" name="postcode" maxlength="10" inputmode="numeric" value="<?= $f('postcode') ?>"></label>
                    </div>
                <?php endif; ?>
                <div class="form-grid">
                    <label class="field"><span>Phone</span><input class="input" type="tel" name="phone" maxlength="40" value="<?= $f('phone') ?>"></label>
                    <label class="field"><span>Email for its orders</span><input class="input" type="email" name="email" maxlength="190" value="<?= $f('email') ?>" placeholder="<?= e(setting_raw('order_notification_email', 'shop@example.com')) ?>"></label>
                </div>
                <p class="hint" style="margin:0">New orders, custom cake requests and messages for this shop go to this email, and customers' replies come back to it. Empty means the email in Settings &gt; Emails.</p>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><h2>Google and maps</h2></div>
            <div class="card__body form-stack">
                <label class="field"><span>Google Maps link <small>optional, from the shop's Google listing (Share &gt; Copy link)</small></span><input class="input" type="url" name="maps_url" maxlength="500" value="<?= $f('maps_url') ?>" placeholder="https://maps.app.goo.gl/..."></label>
                <label class="field"><span>Google review link <small>sent in the "How was your cake?" email</small></span><input class="input" type="url" name="reviews_url" maxlength="500" value="<?= $f('reviews_url') ?>" placeholder="https://g.page/r/.../review"></label>
                <?php if (!$limited): ?>
                <div class="form-grid">
                    <label class="field"><span>Map position: latitude</span><input class="input" name="latitude" maxlength="12" inputmode="decimal" value="<?= $f('latitude') ?>" placeholder="-35.3222"></label>
                    <label class="field"><span>Map position: longitude</span><input class="input" name="longitude" maxlength="12" inputmode="decimal" value="<?= $f('longitude') ?>" placeholder="149.0347"></label>
                </div>
                <p class="hint" style="margin:0">Tells Google exactly where the shop is. In Google Maps, right-click the shop and click the two numbers at the top to copy them.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><h2>Its page on the website</h2><?php if ($pageUrl !== ''): ?><span class="hint"><?= e(preg_replace('#^https?://#', '', $pageUrl)) ?></span><?php endif; ?></div>
            <div class="card__body form-stack">
                <p class="hint" style="margin:0">Each shop has its own page, which Google shows when people search "cake shop <?= e($editing['suburb'] ?: 'suburb') ?>". Link the shop's Google listing to it.</p>
                <div class="field"><span class="label">About this shop <small>a few lines: parking, nearby suburbs, what is special here</small></span><textarea class="input" name="intro" data-rich-editor="simple"><?= $f('intro') ?></textarea></div>
                <div class="branch-photo">
                    <?php if (!empty($editing['photo'])): ?>
                        <img src="<?= e(product_image_url($editing['photo'])) ?>" alt="" width="160" height="120">
                    <?php else: ?>
                        <span class="branch-photo__empty"><?= icon('store', '', 26) ?></span>
                    <?php endif; ?>
                    <div class="form-stack" style="gap:8px">
                        <label class="field"><span>Photo of the shop front <small>optional</small></span><input class="input" type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
                        <p class="hint" style="margin:0">Shown next to the map on this shop's page and used by Google for the page. Without one, the page shows only the map.</p>
                        <?php if (!empty($editing['photo'])): ?><label class="inline-check"><input type="checkbox" name="remove_photo" value="1"> Remove this photo</label><?php endif; ?>
                    </div>
                </div>
                <?php if (!$limited): ?>
                    <label class="field" style="max-width:360px"><span>Page address <small>/locations/...</small></span><input class="input" name="slug" maxlength="60" value="<?= $f('slug') ?>" placeholder="wright"></label>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <aside class="branch-form__side">
        <section class="card">
            <div class="card__body form-stack">
                <?php if (!$limited && !$editing['is_main']): ?>
                    <label class="switch"><input type="checkbox" name="is_active" value="1" <?= checked(old('is_active', (string) $editing['is_active']) === '1') ?>><span class="switch__track"></span><span><strong>Show to customers</strong><small>They can choose it when they order, and its page goes live. Needs the street and phone.</small></span></label>
                <?php elseif (!$limited): ?>
                    <p class="hint" style="margin:0"><?= icon('check', '', 14) ?> The main shop is always shown to customers.</p>
                <?php endif; ?>
                <button class="btn btn--primary btn--block" type="submit"><?= icon('check', '', 18) ?> <?= $isNew ? 'Add branch' : 'Save' ?></button>
                <a class="btn btn--ghost btn--block" href="<?= e($back) ?>">Cancel</a>
            </div>
        </section>
    </aside>
</form>

<?php if (!$isNew): ?>
<section class="card branch-form__test">
    <div class="card__body">
        <div>
            <strong>Check the order email</strong>
            <p class="hint" style="margin:0">Sends a test to <?= e(branch_notify_email((int) $editing['id']) ?: 'the order email') ?>. Save your changes first.</p>
        </div>
        <form method="post" action="<?= e($limited ? admin_url('my-shop') : admin_url('branches')) ?>">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><input type="hidden" name="action" value="test_email"><input type="hidden" name="_back" value="<?= e(admin_url(admin_back_path())) ?>">
            <button class="btn btn--secondary btn--sm" type="submit"><?= icon('mail', '', 15) ?> Send a test email</button>
        </form>
    </div>
</section>
<?php endif; ?>
