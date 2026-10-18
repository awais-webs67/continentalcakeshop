<?php /** @var array $coupons @var ?array $selectedCoupon */
$couponProblem = $selectedCoupon ? coupon_error($selectedCoupon, PHP_FLOAT_MAX) : null;
?>
<div class="page-head">
    <div><h1>Promo popup</h1><p>A card that appears when someone opens the website. Use it for a campaign, a coupon code or an announcement.</p></div>
    <div class="page-head__actions"><a class="btn btn--secondary" href="<?= e(url()) ?>" target="_blank"><?= icon('arrow', '', 18) ?> View website</a></div>
</div>

<form method="post" enctype="multipart/form-data" action="<?= e(admin_url('popup')) ?>">
    <?= csrf_field() ?>
    <div class="grid-main">
        <div class="stack">
            <section class="card">
                <div class="card__body feature-switch">
                    <label class="switch"><input type="checkbox" name="popup_enabled" value="1" <?= checked(setting_bool('popup_enabled')) ?>><span class="switch__track"></span><span><strong>Show the popup on the website</strong></span></label>
                    <?= setting_bool('popup_enabled') ? status_badge('active', 'Live') : status_badge('inactive', 'Off') ?>
                </div>
            </section>
            <section class="card">
                <div class="card__head"><h2>Content</h2></div>
                <div class="card__body form-stack">
                    <label class="field"><span>Title</span><input class="input" name="popup_title" maxlength="80" value="<?= e(setting('popup_title')) ?>" required data-preview="title"></label>
                    <label class="field"><span>Text</span><textarea class="input" name="popup_text" rows="3" maxlength="400" data-preview="text"><?= e(setting('popup_text')) ?></textarea></label>
                    <label class="field"><span>Coupon to show <small>optional</small></span>
                        <select class="input" name="popup_coupon_id" data-preview="coupon">
                            <option value="">No coupon</option>
                            <?php foreach ($coupons as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" data-code="<?= e($c['code']) ?>" data-summary="<?= e(coupon_summary($c)) ?>" data-label="<?= e(coupon_value_label($c)) ?>" <?= selected(setting('popup_coupon_id') === (string) $c['id']) ?>><?= e($c['code']) ?>, <?= e(coupon_value_label($c)) ?><?= coupon_status($c)[1] !== 'Active' ? ' (' . e(coupon_status($c)[1]) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select></label>
                    <p class="hint" style="margin-top:-6px">The code, its discount, minimum order and expiry date are shown on the card automatically. <a href="<?= e(admin_url('coupons')) ?>">Create a coupon</a></p>
                    <?php if ($couponProblem): ?><div class="alert alert--warning">This coupon cannot be used right now (<?= e($couponProblem) ?>), so the popup will show without it.</div><?php endif; ?>
                    <div class="form-grid">
                        <label class="field"><span>Button text <small>empty = no button</small></span><input class="input" name="popup_button_text" maxlength="40" value="<?= e(setting('popup_button_text')) ?>" data-preview="button"></label>
                        <label class="field"><span>Button link</span><input class="input" name="popup_button_url" value="<?= e(setting('popup_button_url')) ?>" placeholder="shop, custom-cakes or https://..."></label>
                    </div>
                </div>
            </section>
            <section class="card">
                <div class="card__head"><h2>Picture</h2></div>
                <div class="card__body form-stack">
                    <input class="input" type="file" name="popup_image" accept="image/jpeg,image/png,image/webp" data-image-input="popup-preview-image">
                    <p class="hint">Optional. A wide photo works best (16:9). Without a picture the card shows a green banner.</p>
                    <?php if (setting('popup_image') !== ''): ?><label class="switch"><input type="checkbox" name="remove_image" value="1"><span class="switch__track"></span><span>Remove picture</span></label><?php endif; ?>
                </div>
            </section>
            <section class="card">
                <div class="card__head"><h2>When to show it</h2></div>
                <div class="card__body form-grid">
                    <label class="field"><span>Pages</span>
                        <select class="input" name="popup_pages">
                            <option value="all" <?= selected(setting('popup_pages', 'all') === 'all') ?>>All pages (not cart or checkout)</option>
                            <option value="home" <?= selected(setting('popup_pages') === 'home') ?>>Homepage only</option>
                        </select></label>
                    <label class="field"><span>How often</span>
                        <select class="input" name="popup_frequency">
                            <option value="once" <?= selected(setting('popup_frequency', 'once') === 'once') ?>>Once per visitor, until you change it</option>
                            <option value="session" <?= selected(setting('popup_frequency') === 'session') ?>>Once every visit</option>
                        </select></label>
                    <label class="field"><span>Delay <small>seconds after the page opens</small></span><input class="input" type="number" min="0" max="60" name="popup_delay_seconds" value="<?= e(setting('popup_delay_seconds', '3')) ?>"></label>
                </div>
            </section>
        </div>
        <div class="stack">
            <section class="card">
                <div class="card__head"><h2>Preview</h2></div>
                <div class="card__body" style="background:var(--bg)">
                    <div class="popup-preview">
                        <img class="popup-preview__image" id="popup-preview-image" src="<?= setting('popup_image') !== '' ? e(product_image_url(setting('popup_image'))) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 16 9%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22%3E%3Cstop stop-color=%22%231A8670%22/%3E%3Cstop offset=%221%22 stop-color=%22%2358DCBF%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%2216%22 height=%229%22 fill=%22url(%23g)%22/%3E%3C/svg%3E' ?>" alt="">
                        <div class="popup-preview__body">
                            <h3 class="popup-preview__title" data-preview-title><?= e(setting('popup_title')) ?></h3>
                            <p class="hint" data-preview-text><?= e(setting('popup_text')) ?></p>
                            <div data-preview-coupon <?= $selectedCoupon ? '' : 'hidden' ?>>
                                <span class="popup-preview__code" data-preview-code><?= e($selectedCoupon['code'] ?? '') ?></span>
                                <p class="hint" data-preview-summary>Use this code at checkout to get <?= e($selectedCoupon ? coupon_summary($selectedCoupon) : '') ?>.</p>
                            </div>
                            <span class="btn btn--primary btn--block" style="margin-top:14px" data-preview-button <?= setting('popup_button_text') === '' ? 'hidden' : '' ?>><?= e(setting('popup_button_text')) ?></span>
                        </div>
                    </div>
                </div>
            </section>
            <section class="card"><div class="form-actions">
                <button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> Save popup</button>
                <span class="hint">Saving shows the popup again to people who closed it before.</span>
            </div></section>
        </div>
    </div>
</form>
