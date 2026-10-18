<?php /** @var array $order @var array $items @var array $events */ ?>
<a class="crumb" href="<?= e(admin_url('orders')) ?>"><?= icon('arrow', '', 14) ?> Orders</a>
<div class="page-head">
    <div>
        <h1><?= e($order['order_number']) ?></h1>
        <p>Placed <?= e(date('l j F Y, g:ia', strtotime($order['created_at']))) ?> &middot; <?= status_badge($order['order_status'], order_status_label($order)) ?> <?= status_badge($order['payment_status'], PAYMENT_STATUSES[$order['payment_status']]) ?></p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= e(admin_url('orders/print', ['id' => $order['id']])) ?>" target="_blank"><?= icon('pen', '', 16) ?> Print ticket</a>
        <a class="btn btn--ghost" href="<?= e(order_url($order)) ?>" target="_blank">Customer view</a>
    </div>
</div>

<div class="grid-main">
    <div class="stack">
        <section class="card">
            <div class="card__head"><h2>Items</h2></div>
            <div class="card__body">
                <table class="items-table">
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><strong><?= (int) $item['quantity'] ?> &times; <?= e($item['product_name']) ?></strong><br><span class="hint"><?= e($item['size_label']) ?> &middot; <?= e(money($item['unit_price'])) ?> each</span>
                                <?php if ($item['cake_message']): ?><br><span class="badge badge--confirmed">Message: &ldquo;<?= e($item['cake_message']) ?>&rdquo;</span><?php endif; ?></td>
                            <td style="text-align:right"><strong><?= e(money($item['line_total'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div class="totals">
                    <div><span>Subtotal</span><span><?= e(money($order['subtotal'])) ?></span></div>
                    <?php if ((float) $order['discount'] > 0): ?><div><span>Discount (<?= e((string) $order['coupon_code']) ?>)</span><span>-<?= e(money($order['discount'])) ?></span></div><?php endif; ?>
                    <?php if (is_delivery($order)): ?><div><span>Delivery</span><span><?= (float) $order['delivery_fee'] > 0 ? e(money($order['delivery_fee'])) : 'Free' ?></span></div><?php endif; ?>
                    <div class="totals__grand"><span>Total</span><span><?= e(money($order['total'])) ?></span></div>
                </div>
                <?php if ($order['notes']): ?><p class="note-box" style="margin-top:16px"><strong>Customer notes:</strong> <?= nl2br(e($order['notes'])) ?></p><?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><h2>Payment</h2><?= status_badge($order['payment_status'], PAYMENT_STATUSES[$order['payment_status']]) ?></div>
            <div class="card__body">
                <dl class="dl">
                    <dt>Method</dt><dd><?= e(payment_method_label($order['payment_method'])) ?></dd>
                    <?php if ($order['payment_reference']): ?><dt>Reference</dt><dd><?= e($order['payment_reference']) ?></dd><?php endif; ?>
                    <dt>Amount</dt><dd><strong><?= e(money($order['total'])) ?></strong></dd>
                </dl>
                <?php if ($order['payment_proof']): ?>
                    <p class="label" style="margin-top:16px">Payment screenshot</p>
                    <?php if ($proofIsImage): ?>
                        <a class="proof" href="<?= e(admin_url('file', ['path' => $order['payment_proof']])) ?>" target="_blank"><img src="<?= e(admin_url('file', ['path' => $order['payment_proof']])) ?>" alt="Payment screenshot"></a>
                    <?php else: ?>
                        <a class="proof proof--file" href="<?= e(admin_url('file', ['path' => $order['payment_proof']])) ?>" target="_blank">Open the uploaded receipt</a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (in_array($order['payment_status'], ['pending', 'awaiting_verification', 'failed'], true)): ?>
                    <div class="form-stack" style="margin-top:18px">
                        <form method="post" data-confirm="Mark this order as paid and email the customer?">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="mark_paid">
                            <button class="btn btn--success btn--block" type="submit"><?= icon('check', '', 18) ?> Payment received, mark as paid</button>
                        </form>
                        <?php if ($order['payment_status'] !== 'failed'): ?>
                            <form method="post" class="form-stack" data-confirm="Tell the customer their payment was not received?">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="reject_payment">
                                <textarea class="input" name="reason" rows="2" placeholder="Optional message to the customer, e.g. the transfer has not arrived yet"></textarea>
                                <button class="btn btn--danger" type="submit">Payment not received</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php elseif ($order['payment_status'] === 'paid'): ?>
                    <form method="post" class="form-stack" style="margin-top:18px" data-confirm="Record a full refund for this order and email the customer?">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="refund">
                        <?php if ($order['payment_method'] === PAYMENT_STRIPE): ?>
                            <label class="switch"><input type="checkbox" name="via_stripe" value="1" checked><span class="switch__track"></span><span>Refund the card through Stripe<small>Untick if you already refunded in the Stripe dashboard</small></span></label>
                        <?php else: ?>
                            <p class="hint">Send the money back from your bank first, then record it here.</p>
                        <?php endif; ?>
                        <button class="btn btn--danger" type="submit">Refund <?= e(money($order['total'])) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><h2>History</h2></div>
            <div class="card__body">
                <ul class="timeline">
                    <?php foreach ($events as $event): ?>
                        <li><?= e($event['message']) ?><small><?= e(date('j M Y, g:ia', strtotime($event['created_at']))) ?><?= $event['admin_name'] ? ' by ' . e($event['admin_name']) : '' ?></small></li>
                    <?php endforeach; ?>
                    <?php if (!$events): ?><li>Order placed<small><?= e(date('j M Y, g:ia', strtotime($order['created_at']))) ?></small></li><?php endif; ?>
                </ul>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card__head"><h2>Order status</h2></div>
            <form class="card__body form-stack" method="post">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="status">
                <select class="input" name="order_status">
                    <?php foreach (ORDER_STATUSES as $key => $label): ?><option value="<?= e($key) ?>" <?= selected($order['order_status'] === $key) ?>><?= e(order_status_label($order, $key)) ?></option><?php endforeach; ?>
                </select>
                <label class="switch"><input type="checkbox" name="notify" value="1" checked><span class="switch__track"></span><span>Email the customer</span></label>
                <textarea class="input" name="message" rows="2" placeholder="Optional message (a friendly default is used if empty)"></textarea>
                <button class="btn btn--primary" type="submit">Update status</button>
            </form>
        </section>

        <section class="card">
            <div class="card__head"><h2>Customer</h2><?php if ($customer): ?><a href="<?= e(admin_url('customers/view', ['id' => $customer['id']])) ?>">Account</a><?php endif; ?></div>
            <div class="card__body">
                <dl class="dl">
                    <dt>Name</dt><dd><?= e($order['customer_name']) ?></dd>
                    <dt>Phone</dt><dd><a href="tel:<?= e($order['customer_phone']) ?>"><?= e($order['customer_phone']) ?></a></dd>
                    <dt>Email</dt><dd><a href="mailto:<?= e($order['customer_email']) ?>"><?= e($order['customer_email']) ?></a></dd>
                    <?php if (is_delivery($order)): ?><dt>Deliver to</dt><dd><?= e(order_address($order)) ?><br><a href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode(order_address($order))) ?>" target="_blank" rel="noopener">Open in Maps</a></dd><?php endif; ?>
                </dl>
            </div>
        </section>

        <section class="card<?= is_delivery($order) && !$order['pickup_time'] && $order['order_status'] !== 'cancelled' ? ' card--attention' : '' ?>" id="when">
            <div class="card__head"><h2><?= is_delivery($order) ? 'Delivery' : 'Pickup' ?></h2><?= is_delivery($order) && !$order['pickup_time'] ? status_badge('pending', 'Set a time') : '' ?></div>
            <form class="card__body form-stack" method="post">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="pickup">
                <p style="margin:0"><strong><?= e(order_when($order)) ?></strong></p>
                <?php if (is_delivery($order)): ?>
                    <p class="hint" style="margin:0">Customer prefers: <strong><?= e(delivery_window_label($order['delivery_window'] ?? null)) ?></strong></p>
                <?php endif; ?>
                <label class="field"><span>Date</span><input class="input" type="date" name="pickup_date" value="<?= e($order['pickup_date']) ?>" required></label>
                <div class="form-grid">
                    <label class="field"><span><?= is_delivery($order) ? 'From' : 'Time' ?></span><input class="input" type="time" name="pickup_time" value="<?= e(substr((string) $order['pickup_time'], 0, 5)) ?>" step="300" required></label>
                    <?php if (is_delivery($order)): ?>
                        <label class="field"><span>To <small>optional</small></span><input class="input" type="time" name="delivery_time_to" value="<?= e(substr((string) ($order['delivery_time_to'] ?? ''), 0, 5)) ?>" step="300"></label>
                    <?php endif; ?>
                </div>
                <?php if (is_delivery($order)): ?><p class="hint" style="margin:0">Give a range like 1pm to 3pm, or only a start time for an exact time.</p><?php endif; ?>
                <label class="switch"><input type="checkbox" name="notify" value="1" checked><span class="switch__track"></span><span>Email the time to the customer</span></label>
                <button class="btn <?= is_delivery($order) && !$order['pickup_time'] ? 'btn--primary' : 'btn--secondary' ?>" type="submit"><?= is_delivery($order) ? ($order['pickup_time'] ? 'Change delivery time' : 'Confirm delivery time') : 'Change pickup time' ?></button>
            </form>
        </section>

        <section class="card">
            <div class="card__head"><h2>Staff notes</h2></div>
            <form class="card__body form-stack" method="post">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="notes">
                <textarea class="input" name="admin_notes" rows="4" placeholder="Only staff can see these notes"><?= e((string) $order['admin_notes']) ?></textarea>
                <button class="btn btn--secondary" type="submit">Save notes</button>
            </form>
        </section>
    </div>
</div>
