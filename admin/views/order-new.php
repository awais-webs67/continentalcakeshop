<?php /** @var array $catalogue @var array $errors @var float $defaultFee @var array $values */
$fulfilment = old('fulfilment', 'pickup');
?>
<a class="crumb" href="<?= e(admin_url('orders')) ?>"><?= icon('arrow', '', 14) ?> Orders</a>
<div class="page-head">
    <div><h1>New order</h1><p>For orders taken by phone or in the shop. They appear in the pickup schedule, the morning email and the reports like website orders.</p></div>
</div>
<?php if ($errors): ?><div class="alert alert--error">Please check: <?= e(implode(' ', $errors)) ?></div><?php endif; ?>

<form method="post" action="<?= e(admin_url('orders/new')) ?>" data-new-order>
    <?= csrf_field() ?>
    <div class="grid-main">
        <div class="stack">
            <section class="card">
                <div class="card__head"><h2>Customer</h2></div>
                <div class="card__body form-grid">
                    <label class="field span-2"><span>Name</span><input class="input" name="name" maxlength="120" value="<?= e(old('name')) ?>" required <?= invalid_attr($errors, 'name') ?>></label>
                    <label class="field"><span>Phone</span><input class="input" type="tel" name="phone" maxlength="40" value="<?= e(old('phone')) ?>" <?= invalid_attr($errors, 'phone') ?>></label>
                    <label class="field"><span>Email <small>optional, for the confirmation</small></span><input class="input" type="email" name="email" maxlength="190" value="<?= e(old('email')) ?>" <?= invalid_attr($errors, 'email') ?>></label>
                    <label class="field span-2"><span>Order taken</span>
                        <select class="input" name="channel">
                            <option value="phone" <?= selected(old('channel', 'phone') === 'phone') ?>>By phone</option>
                            <option value="in_store" <?= selected(old('channel') === 'in_store') ?>>In the shop</option>
                        </select></label>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><h2>Items</h2></div>
                <div class="card__body">
                    <?php $prefix = 'add'; $rows = 2; require BASE_PATH . '/admin/views/partials/line-adder.php'; ?>
                    <?= field_error($errors, 'items') ?>
                    <div class="form-grid" style="margin-top:14px">
                        <label class="field" data-show-delivery <?= $fulfilment === 'delivery' ? '' : 'hidden' ?>><span>Delivery fee</span><div class="input-prefix"><span>$</span><input class="input" name="delivery_fee" inputmode="decimal" value="<?= e(old('delivery_fee', number_format($defaultFee, 2, '.', ''))) ?>"></div></label>
                        <label class="field"><span>Discount <small>optional</small></span><div class="input-prefix"><span>$</span><input class="input" name="discount" inputmode="decimal" value="<?= e(old('discount', '0.00')) ?>"></div></label>
                    </div>
                    <p class="new-order-total">Total: <strong data-new-order-total>$0.00</strong></p>
                    <label class="field" style="margin-top:12px"><span>Notes from the customer <small>shown on the order and the kitchen ticket</small></span><textarea class="input" name="notes" rows="2" maxlength="1000"><?= e(old('notes')) ?></textarea></label>
                </div>
            </section>
        </div>

        <div class="stack">
            <section class="card">
                <div class="card__head"><h2>Pickup or delivery</h2></div>
                <div class="card__body form-stack">
                    <div class="segmented">
                        <label><input type="radio" name="fulfilment" value="pickup" <?= checked($fulfilment === 'pickup') ?> data-fulfilment><span>Pickup</span></label>
                        <label><input type="radio" name="fulfilment" value="delivery" <?= checked($fulfilment === 'delivery') ?> data-fulfilment><span>Delivery</span></label>
                    </div>
                    <label class="field"><span>Date</span><input class="input" type="date" name="pickup_date" value="<?= e(old('pickup_date', preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['date'] ?? '')) && $_GET['date'] >= date('Y-m-d') ? $_GET['date'] : date('Y-m-d', strtotime('+1 day')))) ?>" required <?= invalid_attr($errors, 'pickup_date') ?>></label>
                    <div class="form-grid">
                        <label class="field"><span data-time-label><?= $fulfilment === 'delivery' ? 'From' : 'Time' ?></span><input class="input" type="time" name="pickup_time" step="300" value="<?= e(old('pickup_time')) ?>" <?= invalid_attr($errors, 'pickup_time') ?>></label>
                        <label class="field" data-show-delivery <?= $fulfilment === 'delivery' ? '' : 'hidden' ?>><span>To <small>optional</small></span><input class="input" type="time" name="delivery_time_to" step="300" value="<?= e(old('delivery_time_to')) ?>"></label>
                    </div>
                    <p class="hint" data-show-delivery <?= $fulfilment === 'delivery' ? '' : 'hidden' ?> style="margin:0">Leave the time empty to set it later.</p>
                    <div data-show-delivery class="form-stack" <?= $fulfilment === 'delivery' ? '' : 'hidden' ?>>
                        <label class="field"><span>Street address</span><input class="input" name="delivery_address" maxlength="255" value="<?= e(old('delivery_address')) ?>" <?= invalid_attr($errors, 'delivery_address') ?>></label>
                        <div class="form-grid">
                            <label class="field"><span>Suburb</span><input class="input" name="delivery_suburb" maxlength="80" value="<?= e(old('delivery_suburb')) ?>"></label>
                            <label class="field"><span>Postcode</span><input class="input" name="delivery_postcode" maxlength="4" inputmode="numeric" value="<?= e(old('delivery_postcode')) ?>"></label>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><h2>Payment</h2></div>
                <div class="card__body form-stack">
                    <label class="field"><span>Paid by</span>
                        <select class="input" name="payment_method">
                            <?php foreach (staff_payment_methods() as $key => $label): ?><option value="<?= e($key) ?>" <?= selected(old('payment_method', 'cash') === $key) ?>><?= e($label) ?></option><?php endforeach; ?>
                        </select></label>
                    <label class="field"><span>Amount paid now <small>0 if paying later, part for a deposit</small></span><div class="input-prefix"><span>$</span><input class="input" name="paid_now" inputmode="decimal" value="<?= e(old('paid_now', '0.00')) ?>" data-paid-now <?= invalid_attr($errors, 'paid_now') ?>></div></label>
                    <p style="margin:-4px 0 0"><button type="button" class="link-btn" data-pay-full>Paid in full</button></p>
                    <input class="input" name="payment_note" maxlength="200" placeholder="Payment note, e.g. deposit" value="<?= e(old('payment_note')) ?>">
                    <p class="hint" style="margin:0">Card by phone? Choose the card method and leave the amount at 0. The customer can pay from the link in their email.</p>
                </div>
            </section>

            <section class="card">
                <div class="card__body form-stack">
                    <label class="switch"><input type="checkbox" name="confirmed" value="1" <?= checked(old('confirmed', '1') === '1') ?>><span class="switch__track"></span><span>Mark as confirmed</span></label>
                    <label class="switch"><input type="checkbox" name="notify" value="1" <?= checked(old('notify', '1') === '1') ?>><span class="switch__track"></span><span>Email the customer a confirmation<small>Only when an email is given</small></span></label>
                    <label class="field"><span>Staff notes <small>only staff see these</small></span><textarea class="input" name="admin_notes" rows="2"><?= e(old('admin_notes')) ?></textarea></label>
                    <button class="btn btn--primary btn--block" type="submit"><?= icon('check', '', 18) ?> Save order</button>
                </div>
            </section>
        </div>
    </div>
</form>
<?php forget_input(); ?>
