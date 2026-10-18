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
                    <dt>Budget</dt><dd><?= e($request['budget'] ?: 'Not given') ?></dd>
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

        <section class="card">
            <div class="card__head"><h2>Send a quote or reply</h2></div>
            <form class="card__body form-stack" method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="quote">
                <label class="field"><span>Price <small>optional, shown as a highlighted quote</small></span>
                    <div class="input-prefix" style="max-width:220px"><span>$</span><input class="input" name="quoted_price" inputmode="decimal" value="<?= e((string) $request['quoted_price']) ?>" placeholder="0.00"></div></label>
                <label class="field"><span>Message</span><textarea class="input" name="message" rows="6" required><?= e($defaultMessage) ?></textarea></label>
                <p class="hint">The email goes to <?= e($request['email']) ?> with your logo and shop details. Their replies come to <?= e(setting('store_email')) ?>.</p>
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
