<?php /** @var array $coupons @var ?array $editing */
$form = $editing ?? ['id' => 0, 'code' => '', 'description' => '', 'discount_type' => 'percent', 'discount_value' => '', 'min_order' => '0', 'max_uses' => '', 'starts_at' => '', 'expires_at' => '', 'is_active' => 1];
// Values kept after a failed save win over the stored ones.
foreach (array_keys($form) as $key) {
    if (old($key) !== '' || ($key === 'is_active' && old('code') !== '')) {
        $form[$key] = old($key);
    }
}
forget_input();
?>
<div class="page-head">
    <div><h1>Coupons</h1><p>Discount codes customers can enter at checkout. The discount comes off the cakes, not the delivery fee.</p></div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= e(admin_url('popup')) ?>"><?= icon('megaphone', '', 18) ?> Promote with the popup</a>
    </div>
</div>

<section class="card">
    <form class="card__body feature-switch" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="feature">
        <label class="switch"><input type="checkbox" name="coupons_enabled" value="1" <?= checked(coupons_enabled()) ?> data-auto-submit><span class="switch__track"></span><span><strong>Coupon codes at checkout</strong></span></label>
        <p class="hint"><?= coupons_enabled() ? 'On. Customers see a "Have a coupon code?" link in the order summary.' : 'Off. The coupon box is hidden and no code can be used.' ?></p>
    </form>
</section>

<div class="grid-main" style="margin-top:16px">
    <section class="card">
        <div class="card__head"><h2>All coupons</h2><span class="hint"><?= count($coupons) ?> codes</span></div>
        <?php if (!$coupons): ?>
            <div class="empty"><?= icon('tag', '', 32) ?><p>No coupons yet. Create your first one with the form.</p></div>
        <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Code</th><th>Discount</th><th>Used</th><th>Valid</th><th>Status</th><th class="num">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($coupons as $c): [$style, $label] = coupon_status($c); ?>
                <tr>
                    <td><a class="row-link coupon-code" href="<?= e(admin_url('coupons', ['edit' => $c['id']])) ?>#coupon-form"><?= e($c['code']) ?></a><?php if ($c['description']): ?><div class="muted"><?= e($c['description']) ?></div><?php endif; ?></td>
                    <td><strong><?= e(coupon_value_label($c)) ?></strong><div class="muted"><?= (float) $c['min_order'] > 0 ? 'Orders of ' . e(money($c['min_order'])) . '+' : 'Any order' ?></div></td>
                    <td><?= (int) $c['used_count'] ?><?= $c['max_uses'] !== null ? ' / ' . (int) $c['max_uses'] : '' ?><div class="muted"><?= (float) $c['total_discount'] > 0 ? e(money($c['total_discount'])) . ' saved' : ($c['max_uses'] === null ? 'No limit' : '') ?></div></td>
                    <td class="muted"><?= $c['expires_at'] ? 'Until ' . e(date('j M Y', strtotime($c['expires_at']))) : 'No expiry' ?><?= $c['starts_at'] ? '<br>From ' . e(date('j M Y', strtotime($c['starts_at']))) : '' ?></td>
                    <td><?= status_badge($style, $label) ?></td>
                    <td class="num">
                        <a class="link-btn" href="<?= e(admin_url('coupons', ['edit' => $c['id']])) ?>#coupon-form">Edit</a>
                        <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="link-btn" type="submit"><?= $c['is_active'] ? 'Turn off' : 'Turn on' ?></button></form>
                        <form method="post" style="display:inline" data-confirm="Delete coupon <?= e($c['code']) ?>?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="action" value="delete"><button class="link-btn link-btn--danger" type="submit">Delete</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </section>

    <section class="card" id="coupon-form">
        <div class="card__head"><h2><?= $editing ? 'Edit ' . e($editing['code']) : 'Create a coupon' ?></h2><?php if ($editing): ?><a href="<?= e(admin_url('coupons')) ?>">Cancel</a><?php endif; ?></div>
        <form class="card__body form-stack" method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
            <label class="field"><span>Code <small>what customers type</small></span>
                <div class="input-action">
                    <input class="input coupon-code" name="code" value="<?= e($form['code']) ?>" maxlength="40" required placeholder="e.g. CAKE10" data-coupon-code-input>
                    <button type="button" class="btn btn--secondary btn--sm" data-generate-code>Generate</button>
                </div></label>
            <label class="field"><span>Note <small>optional, for staff</small></span><input class="input" name="description" maxlength="160" value="<?= e((string) $form['description']) ?>" placeholder="e.g. Mother's Day campaign"></label>
            <div class="form-grid">
                <label class="field"><span>Discount type</span>
                    <select class="input" name="discount_type">
                        <option value="percent" <?= selected($form['discount_type'] === 'percent') ?>>Percentage (%)</option>
                        <option value="fixed" <?= selected($form['discount_type'] === 'fixed') ?>>Fixed amount ($)</option>
                    </select></label>
                <label class="field"><span>Amount</span><input class="input" name="discount_value" inputmode="decimal" value="<?= e(rtrim(rtrim((string) $form['discount_value'], '0'), '.')) ?>" required placeholder="e.g. 10"></label>
                <label class="field"><span>Minimum order <small>0 = any</small></span><div class="input-prefix"><span>$</span><input class="input" name="min_order" inputmode="decimal" value="<?= e((string) $form['min_order']) ?>"></div></label>
                <label class="field"><span>Number of uses <small>empty = no limit</small></span><input class="input" type="number" min="1" name="max_uses" value="<?= e((string) $form['max_uses']) ?>" placeholder="No limit"></label>
                <label class="field"><span>Starts <small>optional</small></span><input class="input" type="date" name="starts_at" value="<?= e((string) $form['starts_at']) ?>"></label>
                <label class="field"><span>Expires <small>last day it works</small></span><input class="input" type="date" name="expires_at" value="<?= e((string) $form['expires_at']) ?>"></label>
            </div>
            <label class="switch"><input type="checkbox" name="is_active" value="1" <?= checked((bool) $form['is_active']) ?>><span class="switch__track"></span><span>Active</span></label>
            <button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> <?= $editing ? 'Save coupon' : 'Create coupon' ?></button>
        </form>
    </section>
</div>
