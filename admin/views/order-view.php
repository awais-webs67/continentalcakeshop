<?php /** @var array $order @var array $items @var array $events */ ?>
<a class="crumb" href="<?= e(admin_url('orders')) ?>"><?= icon('arrow', '', 14) ?> Orders</a>
<div class="page-head">
    <div>
        <h1><?= e($order['order_number']) ?></h1>
        <p>Placed <?= e(date('l j F Y, g:ia', strtotime($order['created_at']))) ?> &middot; <?= status_badge($order['order_status'], order_status_label($order)) ?> <?= status_badge($order['payment_status'], PAYMENT_STATUSES[$order['payment_status']]) ?></p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= e(admin_url('orders/print', ['id' => $order['id']])) ?>" target="_blank"><?= icon('pen', '', 16) ?> Print ticket</a>
        <?php $orderThread = db_one('SELECT id, unread_admin FROM threads WHERE order_id = ? AND last_message_at IS NOT NULL', [$order['id']]); ?>
        <?php if (admin_can('messages')): ?><a class="btn btn--secondary" href="<?= e(admin_url('messages/view', ['order' => $order['id']])) ?>"><?= icon('mail', '', 16) ?> <?= $orderThread ? 'Conversation' . ($orderThread['unread_admin'] ? ' (new)' : '') : 'Message customer' ?></a><?php endif; ?>
        <a class="btn btn--ghost" href="<?= e(order_url($order)) ?>" target="_blank">Customer view</a>
    </div>
</div>

<div class="grid-main">
    <div class="stack">
        <section class="card" id="items">
            <div class="card__head"><h2>Items</h2><?php if (($order['channel'] ?? 'online') !== 'online'): ?><?= status_badge('staff', ORDER_CHANNELS[$order['channel']] ?? 'Staff order') ?><?php endif; ?></div>
            <div class="card__body">
                <table class="items-table">
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><strong><?= (int) $item['quantity'] ?> &times; <?= e($item['product_name']) ?></strong><br><span class="hint"><?= e(trim(($item['size_label'] ?? '') . ($item['options'] ? ' · ' . $item['options'] : ''), ' ·')) ?> &middot; <?= e(money($item['unit_price'])) ?> each</span>
                                <?php if ($item['cake_message']): ?><br><span class="badge badge--confirmed">Message: &ldquo;<?= e($item['cake_message']) ?>&rdquo;</span><?php endif; ?></td>
                            <td style="text-align:right"><strong><?= e(money($item['line_total'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div class="totals">
                    <div><span>Subtotal</span><span><?= e(money($order['subtotal'])) ?></span></div>
                    <?php if ((float) $order['discount'] > 0): ?><div><span>Discount<?= $order['coupon_code'] ? ' (' . e((string) $order['coupon_code']) . ')' : '' ?></span><span>-<?= e(money($order['discount'])) ?></span></div><?php endif; ?>
                    <?php if (is_delivery($order)): ?><div><span>Delivery</span><span><?= (float) $order['delivery_fee'] > 0 ? e(money($order['delivery_fee'])) : 'Free' ?></span></div><?php endif; ?>
                    <div class="totals__grand"><span>Total</span><span><?= e(money($order['total'])) ?></span></div>
                </div>
                <?php if ($order['notes']): ?><p class="note-box" style="margin-top:16px"><strong>Customer notes:</strong> <?= nl2br(e($order['notes'])) ?></p><?php endif; ?>

                <?php if ($order['order_status'] !== 'cancelled'): ?>
                <details class="edit-toggle" style="margin-top:16px">
                    <summary><?= icon('pen', '', 16) ?> Change items, prices, delivery fee or discount</summary>
                    <form method="post" class="form-stack" style="margin-top:12px">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="items">
                        <div class="table-wrap"><table class="edit-table">
                            <thead><tr><th style="text-align:left">Item</th><th>Qty</th><th>Price each</th><th>Remove</th></tr></thead>
                            <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= e($item['product_name']) ?><div class="muted"><?= e(order_item_detail($item)) ?></div></td>
                                    <td style="width:80px"><input class="input" type="number" min="0" max="99" name="line[<?= (int) $item['id'] ?>][qty]" value="<?= (int) $item['quantity'] ?>"></td>
                                    <td style="width:130px"><div class="input-prefix"><span>$</span><input class="input" name="line[<?= (int) $item['id'] ?>][price]" inputmode="decimal" value="<?= e(number_format((float) $item['unit_price'], 2, '.', '')) ?>"></div></td>
                                    <td style="text-align:center"><input type="checkbox" name="line[<?= (int) $item['id'] ?>][remove]" value="1" aria-label="Remove <?= e($item['product_name']) ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table></div>
                        <p class="label" style="margin:6px 0 0">Add to the order</p>
                        <?php $prefix = 'add'; $rows = 1; require BASE_PATH . '/admin/views/partials/line-adder.php'; ?>
                        <div class="form-grid">
                            <label class="field"><span>Delivery fee</span><div class="input-prefix"><span>$</span><input class="input" name="delivery_fee" inputmode="decimal" value="<?= e(number_format((float) $order['delivery_fee'], 2, '.', '')) ?>"></div></label>
                            <label class="field"><span>Discount</span><div class="input-prefix"><span>$</span><input class="input" name="discount" inputmode="decimal" value="<?= e(number_format((float) $order['discount'], 2, '.', '')) ?>"></div></label>
                        </div>
                        <label class="switch"><input type="checkbox" name="notify" value="1"><span class="switch__track"></span><span>Email the customer the new total</span></label>
                        <button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> Save changes</button>
                    </form>
                </details>
                <?php endif; ?>
            </div>
        </section>

        <section class="card" id="payment">
            <div class="card__head"><h2>Payment</h2><?= status_badge($order['payment_status'], PAYMENT_STATUSES[$order['payment_status']]) ?></div>
            <div class="card__body">
                <?php $balance = order_balance($order); ?>
                <div class="balance-box">
                    <div><span>Total</span><strong><?= e(money($order['total'])) ?></strong></div>
                    <div><span>Paid</span><strong><?= e(money(order_net_paid($order))) ?></strong></div>
                    <div class="<?= $balance > 0 ? 'is-owing' : ($balance < 0 ? 'is-credit' : '') ?>"><span><?= $balance < 0 ? 'To refund' : 'Owing' ?></span><strong><?= e(money(abs($balance))) ?></strong></div>
                </div>
                <dl class="dl">
                    <dt>Method</dt><dd><?= e(payment_method_label($order['payment_method'])) ?></dd>
                    <?php if ($order['payment_reference']): ?><dt>Reference</dt><dd><?= e($order['payment_reference']) ?></dd><?php endif; ?>
                </dl>
                <?php if ($payments): ?>
                    <p class="label" style="margin-top:14px">Payment history</p>
                    <table class="money-table">
                        <?php foreach ($payments as $p): ?>
                            <tr class="<?= $p['kind'] === 'refund' ? 'is-refund' : '' ?>">
                                <td><?= $p['kind'] === 'refund' ? 'Refund' : 'Payment' ?> &middot; <?= e(payment_method_label($p['method'])) ?><div class="muted"><?= e(date('j M Y, g:ia', strtotime($p['created_at']))) ?><?= $p['admin_name'] ? ' by ' . e($p['admin_name']) : '' ?><?= $p['note'] ? ' &middot; ' . e($p['note']) : '' ?></div></td>
                                <td><?= $p['kind'] === 'refund' ? '-' : '' ?><?= e(money($p['amount'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
                <?php if ($order['payment_proof']): ?>
                    <p class="label" style="margin-top:16px">Payment screenshot</p>
                    <?php if ($proofIsImage): ?>
                        <a class="proof" href="<?= e(admin_url('file', ['path' => $order['payment_proof']])) ?>" target="_blank"><img src="<?= e(admin_url('file', ['path' => $order['payment_proof']])) ?>" alt="Payment screenshot"></a>
                    <?php else: ?>
                        <a class="proof proof--file" href="<?= e(admin_url('file', ['path' => $order['payment_proof']])) ?>" target="_blank">Open the uploaded receipt</a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($balance > 0 && $order['order_status'] !== 'cancelled'): ?>
                    <form method="post" class="form-stack" style="margin-top:18px">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="record_payment">
                        <p class="label" style="margin:0">Record a payment</p>
                        <div class="form-grid">
                            <label class="field"><span>Amount</span><div class="input-prefix"><span>$</span><input class="input" name="amount" inputmode="decimal" value="<?= e(number_format($balance, 2, '.', '')) ?>"></div></label>
                            <label class="field"><span>Paid by</span>
                                <select class="input" name="method">
                                    <?php foreach (staff_payment_methods() as $key => $label): ?><option value="<?= e($key) ?>" <?= selected($key === $order['payment_method']) ?>><?= e($label) ?></option><?php endforeach; ?>
                                </select></label>
                            <label class="field"><span>Reference <small>optional</small></span><input class="input" name="reference" maxlength="120" placeholder="e.g. bank receipt number"></label>
                            <label class="field"><span>Note <small>optional</small></span><input class="input" name="note" maxlength="200" placeholder="e.g. deposit"></label>
                        </div>
                        <label class="switch"><input type="checkbox" name="notify" value="1" <?= checked(valid_email($order['customer_email'])) ?>><span class="switch__track"></span><span>Email the customer a receipt</span></label>
                        <button class="btn btn--success" type="submit"><?= icon('check', '', 18) ?> Record payment</button>
                    </form>
                    <?php if (in_array($order['payment_status'], ['pending', 'awaiting_verification'], true)): ?>
                        <form method="post" class="form-stack" style="margin-top:14px" data-confirm="Tell the customer their payment was not received?">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="reject_payment">
                            <textarea class="input" name="reason" rows="2" placeholder="Optional message to the customer, e.g. the transfer has not arrived yet"></textarea>
                            <button class="btn btn--danger btn--sm" type="submit">Payment not received</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($order['payment_method'] === PAYMENT_STRIPE && valid_email($order['customer_email'])): ?>
                        <form method="post" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="send_payment_link">
                            <button class="btn btn--secondary btn--sm" type="submit"><?= icon('card', '', 16) ?> Email a card payment link</button></form>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (order_net_paid($order) > 0): ?>
                    <details class="edit-toggle" style="margin-top:18px" <?= $balance < 0 ? 'open' : '' ?>>
                        <summary>Refund<?= $balance < 0 ? ' ' . e(money(-$balance)) . ' owed to the customer' : '' ?></summary>
                        <form method="post" class="form-stack" style="margin-top:12px" data-confirm="Record this refund?">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="refund">
                            <div class="form-grid">
                                <label class="field"><span>Amount <small>up to <?= e(money(order_net_paid($order))) ?></small></span><div class="input-prefix"><span>$</span><input class="input" name="amount" inputmode="decimal" value="<?= e(number_format($balance < 0 ? -$balance : order_net_paid($order), 2, '.', '')) ?>"></div></label>
                                <label class="field"><span>Refunded by</span>
                                    <select class="input" name="method">
                                        <?php foreach (staff_payment_methods() as $key => $label): ?><option value="<?= e($key) ?>" <?= selected($key === $order['payment_method']) ?>><?= e($label) ?></option><?php endforeach; ?>
                                    </select></label>
                            </div>
                            <?php if ($hasStripePayment): ?>
                                <label class="switch"><input type="checkbox" name="via_stripe" value="1" checked><span class="switch__track"></span><span>Refund the card through Stripe<small>Untick if you already refunded in the Stripe dashboard or by another way</small></span></label>
                            <?php else: ?>
                                <p class="hint" style="margin:0">Send the money back first (cash or bank), then record it here.</p>
                            <?php endif; ?>
                            <input class="input" name="note" maxlength="150" placeholder="Note, e.g. one cake removed">
                            <label class="switch"><input type="checkbox" name="notify" value="1" <?= checked(valid_email($order['customer_email'])) ?>><span class="switch__track"></span><span>Email the customer</span></label>
                            <button class="btn btn--danger" type="submit">Record refund</button>
                        </form>
                    </details>
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
                    <dt>Email</dt><dd><?= $order['customer_email'] !== '' ? '<a href="mailto:' . e($order['customer_email']) . '">' . e($order['customer_email']) . '</a>' : '<span class="muted">None given</span>' ?></dd>
                    <?php if (is_delivery($order)): ?><dt>Deliver to</dt><dd><?= e(order_address($order)) ?><br><a href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode(order_address($order))) ?>" target="_blank" rel="noopener">Open in Maps</a></dd><?php endif; ?>
                </dl>
                <details class="edit-toggle" style="margin-top:12px">
                    <summary><?= icon('pen', '', 16) ?> Edit details</summary>
                    <form method="post" class="form-stack" style="margin-top:12px">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="details">
                        <label class="field"><span>Name</span><input class="input" name="customer_name" maxlength="120" value="<?= e($order['customer_name']) ?>" required></label>
                        <label class="field"><span>Phone</span><input class="input" name="customer_phone" maxlength="40" value="<?= e($order['customer_phone']) ?>"></label>
                        <label class="field"><span>Email <small>optional</small></span><input class="input" type="email" name="customer_email" maxlength="190" value="<?= e($order['customer_email']) ?>"></label>
                        <?php if (is_delivery($order)): ?>
                            <label class="field"><span>Street address</span><input class="input" name="delivery_address" maxlength="255" value="<?= e((string) $order['delivery_address']) ?>"></label>
                            <div class="form-grid">
                                <label class="field"><span>Suburb</span><input class="input" name="delivery_suburb" maxlength="80" value="<?= e((string) $order['delivery_suburb']) ?>"></label>
                                <label class="field"><span>Postcode</span><input class="input" name="delivery_postcode" maxlength="4" inputmode="numeric" value="<?= e((string) $order['delivery_postcode']) ?>"></label>
                            </div>
                        <?php endif; ?>
                        <button class="btn btn--secondary" type="submit">Save details</button>
                    </form>
                </details>
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
