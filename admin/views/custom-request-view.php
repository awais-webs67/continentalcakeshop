<?php /** @var array $request @var bool $isImage @var string $defaultMessage */ ?>
<a class="crumb" href="<?= e(admin_url('custom-requests')) ?>"><?= icon('arrow', '', 14) ?> Custom requests</a>
<div class="page-head">
    <div><h1><?= e($request['occasion']) ?> cake for <?= e($request['name']) ?></h1>
        <p>Received <?= e(date('j M Y, g:ia', strtotime($request['created_at']))) ?> &middot; <?= status_badge($request['status'], CUSTOM_STATUSES[$request['status']]) ?></p></div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= e(admin_url('messages/view', ['request' => $request['id']])) ?>"><?= icon('mail', '', 18) ?> Conversation</a>
        <a class="btn btn--secondary" href="tel:<?= e(preg_replace('/[^\d+]/', '', $request['phone'])) ?>"><?= icon('phone', '', 18) ?> Call</a>
    </div>
</div>
<div class="grid-main">
    <div class="stack">
        <section class="card">
            <div class="card__head"><h2>What they asked for</h2></div>
            <div class="card__body">
                <dl class="dl">
                    <dt>Needed by</dt><dd><strong><?= e(date('l j F Y', strtotime($request['event_date']))) ?></strong></dd>
                    <dt>Occasion</dt><dd><?= e($request['occasion']) ?></dd>
                    <dt>Serves</dt><dd><?= e($request['servings'] ?: 'Not given') ?></dd>
                    <dt>Pickup or delivery</dt><dd><?= ($request['fulfilment'] ?? '') === 'delivery' ? 'Delivery' : 'Pickup' ?></dd>
                    <dt>Flavour</dt><dd><?= e($request['flavour'] ?: 'Not given') ?></dd>
                    <?php if ($request['budget']): ?><dt>Budget</dt><dd><?= e($request['budget']) ?></dd><?php endif; ?>
                </dl>
                <div class="note-box" style="margin-top:16px"><?= nl2br(e($request['details'])) ?></div>
                <?php if ($request['reference_image']): ?>
                    <p class="label" style="margin:18px 0 8px">Reference photo</p>
                    <?php if ($isImage): ?>
                        <a class="proof" href="<?= e(admin_url('file', ['path' => $request['reference_image']])) ?>" target="_blank"><img src="<?= e(admin_url('file', ['path' => $request['reference_image']])) ?>" alt="Reference photo"></a>
                    <?php else: ?>
                        <a class="proof proof--file" href="<?= e(admin_url('file', ['path' => $request['reference_image']])) ?>" target="_blank">Open the uploaded file</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($linkedOrder): ?>
            <div class="alert alert--success">The customer accepted this quote. <a href="<?= e(admin_url('orders/view', ['id' => $linkedOrder['id']])) ?>">Open order <?= e($linkedOrder['order_number']) ?></a> &middot; <?= e(PAYMENT_STATUSES[$linkedOrder['payment_status']]) ?><?= order_balance($linkedOrder) > 0 && (float) $linkedOrder['amount_paid'] > 0 ? ', ' . e(money(order_balance($linkedOrder))) . ' still owing' : '' ?>.</div>
        <?php endif; ?>
        <?php if (!$linkedOrder): $isDelivery = ($request['fulfilment'] ?? 'pickup') === 'delivery'; ?>
        <section class="card" id="make-order">
            <div class="card__head"><h2>Confirm as an order</h2><span class="hint">Agreed by phone, email or in the shop</span></div>
            <form class="card__body form-stack" method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="make_order">
                <p class="hint" style="margin:0">The cake goes into Orders (Upcoming) and the sales figures, with its receipt. Any money taken now is recorded as paid.</p>
                <div class="form-grid">
                    <label class="field"><span>Price</span><div class="input-prefix"><span>$</span><input class="input" name="price" inputmode="decimal" required value="<?= e($request['quoted_price'] ? number_format((float) $request['quoted_price'], 2, '.', '') : '') ?>" placeholder="0.00"></div></label>
                    <label class="field"><span>Pickup or delivery</span>
                        <select class="input" name="fulfilment" data-make-fulfilment><option value="pickup" <?= selected(!$isDelivery) ?>>Pickup</option><option value="delivery" <?= selected($isDelivery) ?>>Delivery</option></select></label>
                    <label class="field"><span>Date</span><input class="input" type="date" name="date" required value="<?= e($request['event_date']) ?>"></label>
                    <label class="field"><span>Time <small>optional</small></span><input class="input" type="time" name="time" step="300"></label>
                </div>
                <div class="form-grid" data-make-delivery <?= $isDelivery ? '' : 'hidden' ?>>
                    <label class="field span-2"><span>Delivery address</span><input class="input" name="delivery_address" maxlength="255"></label>
                    <label class="field"><span>Suburb</span><input class="input" name="delivery_suburb" maxlength="80"></label>
                    <label class="field"><span>Postcode</span><input class="input" name="delivery_postcode" maxlength="4" inputmode="numeric"></label>
                </div>
                <div class="form-grid">
                    <label class="field"><span>Paid now <small>deposit or full, 0 if nothing yet</small></span><div class="input-prefix"><span>$</span><input class="input" name="deposit" inputmode="decimal" value="<?= e($request['deposit_amount'] ? number_format((float) $request['deposit_amount'], 2, '.', '') : '0') ?>"></div></label>
                    <label class="field"><span>Paid by</span>
                        <select class="input" name="deposit_method"><?php foreach (staff_payment_methods() as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
                    <label class="field span-2"><span>The rest will be paid</span>
                        <select class="input" name="payment_method"><?php foreach (payment_method_definitions() as $key => $m): ?><option value="<?= e($key) ?>" <?= selected($key === ($isDelivery ? PAYMENT_CALL : PAYMENT_SHOP)) ?>><?= e($m['label']) ?></option><?php endforeach; ?></select></label>
                </div>
                <label class="switch"><input type="checkbox" name="notify" value="1" checked><span class="switch__track"></span><span>Email the customer the confirmation and receipt</span></label>
                <div><button class="btn btn--primary" type="submit"><?= icon('check', '', 17) ?> Confirm and add to orders</button></div>
            </form>
        </section>
        <?php endif; ?>
        <section class="card">
            <div class="card__head"><h2>Send a quote or reply</h2></div>
            <form class="card__body form-stack" method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="quote">
                <div class="form-grid">
                    <label class="field"><span>Price <small>optional, shown as a highlighted quote</small></span>
                        <div class="input-prefix"><span>$</span><input class="input" name="quoted_price" inputmode="decimal" value="<?= e((string) $request['quoted_price']) ?>" placeholder="0.00" data-quote-price></div></label>
                    <label class="field"><span>Deposit to accept <small>empty = full price</small></span>
                        <div class="input-prefix"><span>$</span><input class="input" name="deposit_amount" inputmode="decimal" value="<?= e((string) $request['deposit_amount']) ?>" placeholder="e.g. <?= (int) setting('quote_default_deposit_percent', '50') ?>% of the price" data-quote-deposit></div></label>
                </div>
                <?php if (!$linkedOrder): $canPay = with_branch((int) $request['branch_id'] ?: null, static fn () => available_payment_methods()) !== []; ?>
                    <label class="switch"><input type="checkbox" name="online_pay" value="1" <?= checked($canPay && ($request['quote_token'] !== null || $request['quoted_price'] === null)) ?> <?= $canPay ? '' : 'disabled' ?>><span class="switch__track"></span><span>Let the customer accept and pay online<small><?= $canPay ? 'The email gets an "Accept and pay" button. Paying the deposit or the full price turns this request into an order.' : 'Switch on a payment method first (Settings > Payments, or this shop\'s Payments tab).' ?></small></span></label>
                <?php endif; ?>
                <?php if ($request['quote_token'] && !$linkedOrder): ?><p class="hint" style="margin:0">Customer link: <a href="<?= e(quote_url($request)) ?>" target="_blank"><?= e(quote_url($request)) ?></a></p><?php endif; ?>
                <label class="field"><span>Message</span><textarea class="input" name="message" rows="6" required><?= e($defaultMessage) ?></textarea></label>
                <p class="hint">The email goes to <?= e($request['email']) ?> with your logo and shop details. Their replies come to <?= e(with_branch((int) $request['branch_id'] ?: null, static fn () => setting('store_email'))) ?>.</p>
                <div><button class="btn btn--primary" type="submit"><?= icon('mail', '', 18) ?> Send email</button></div>
            </form>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card__head"><h2>Customer</h2></div>
            <div class="card__body">
                <dl class="dl">
                    <dt>Name</dt><dd><?= e($request['name']) ?></dd>
                    <dt>Email</dt><dd><a href="mailto:<?= e($request['email']) ?>"><?= e($request['email']) ?></a></dd>
                    <dt>Phone</dt><dd><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $request['phone'])) ?>"><?= e($request['phone']) ?></a></dd>
                </dl>
            </div>
        </section>
        <section class="card">
            <div class="card__head"><h2>Status</h2></div>
            <form class="card__body form-stack" method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="status">
                <select class="input" name="status">
                    <?php foreach (CUSTOM_STATUSES as $key => $label): ?><option value="<?= e($key) ?>" <?= selected($request['status'] === $key) ?>><?= e($label) ?></option><?php endforeach; ?>
                </select>
                <button class="btn btn--secondary" type="submit">Update status</button>
            </form>
        </section>
        <section class="card">
            <div class="card__head"><h2>Private notes</h2></div>
            <form class="card__body form-stack" method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="notes">
                <textarea class="input" name="admin_notes" rows="4" placeholder="Only staff can see these notes"><?= e((string) $request['admin_notes']) ?></textarea>
                <button class="btn btn--secondary" type="submit">Save notes</button>
            </form>
        </section>
        <form method="post" data-confirm="Delete this request and its photo?">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete">
            <button class="btn btn--danger btn--sm" type="submit"><?= icon('trash', '', 16) ?> Delete request</button>
        </form>
    </div>
</div>
