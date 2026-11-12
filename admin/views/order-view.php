<?php /** @var array $order @var array $items @var array $events @var array $payments @var int $orderCount */
$delivery = is_delivery($order);
$balance = order_balance($order);
$next = order_next_step($order);
$cancelled = $order['order_status'] === 'cancelled';
$orderBranch = get_branch((int) $order['branch_id']);
$manyBranches = count(branches()) > 1;
$orderThread = db_one('SELECT id, unread_admin FROM threads WHERE order_id = ? AND last_message_at IS NOT NULL', [$order['id']]);
$phoneDigits = preg_replace('/\D+/', '', (string) $order['customer_phone']);
$waDigits = str_starts_with($phoneDigits, '0') ? '61' . substr($phoneDigits, 1) : $phoneDigits;
$initials = strtoupper(implode('', array_map(static fn ($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($order['customer_name'])) ?: [], 0, 2))));
$needsTime = $delivery && !$order['pickup_time'] && !$cancelled;
$steps = ['new', 'confirmed', 'in_progress', 'ready', 'completed'];
$at = array_search($order['order_status'], $steps, true);
$ordinal = static fn (int $n): string => $n . (in_array($n % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th'));
?>
<a class="crumb" href="<?= e(admin_url('orders')) ?>"><?= icon('arrow', '', 14) ?> Orders</a>

<section class="card ov-head">
    <div class="ov-head__top">
        <div class="ov-head__title">
            <p class="ov-head__meta"><?= e($order['order_number']) ?> &middot; placed <?= e(date('D j M Y, g:ia', strtotime($order['created_at']))) ?><?php if (($order['channel'] ?? 'online') !== 'online'): ?> &middot; <?= e(ORDER_CHANNELS[$order['channel']] ?? 'Staff order') ?><?php endif; ?></p>
            <h1><?= e($order['customer_name']) ?></h1>
            <p class="ov-head__when"><?= icon($delivery ? 'truck' : 'store', '', 17) ?> <?= $delivery ? 'Delivery' : 'Pickup' ?> <strong><?= e($needsTime ? date('l j F', strtotime($order['pickup_date'])) . ', time to set' : order_when($order)) ?></strong><?php if ($manyBranches && $orderBranch): ?> &middot; <?= e($orderBranch['name']) ?><?php endif; ?></p>
        </div>
        <div class="ov-head__actions">
            <a class="btn btn--secondary" href="<?= e(admin_url('orders/print', ['id' => $order['id']])) ?>" target="_blank"><?= icon('download', '', 16) ?> Print ticket</a>
            <details class="dropdown ov-more">
                <summary class="btn btn--secondary">More <?= icon('down', '', 15) ?></summary>
                <div class="dropdown__panel">
                    <?php if (admin_can('messages')): ?><a href="<?= e(admin_url('messages/view', ['order' => $order['id']])) ?>"><?= icon('mail', '', 17) ?> <?= $orderThread ? 'Conversation' . ($orderThread['unread_admin'] ? ' (new)' : '') : 'Message customer' ?></a><?php endif; ?>
                    <a href="<?= e(invoice_url($order)) ?>" target="_blank"><?= icon('pen', '', 17) ?> Invoice</a>
                    <a href="<?= e(order_url($order)) ?>" target="_blank"><?= icon('user', '', 17) ?> Customer view</a>
                </div>
            </details>
            <?php if ($next && !$needsTime): ?>
                <form method="post">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="status">
                    <input type="hidden" name="order_status" value="<?= e($next[0]) ?>"><input type="hidden" name="notify" value="1">
                    <button class="btn btn--primary" type="submit" title="<?= valid_email((string) $order['customer_email']) ? 'Also emails the customer' : 'No email address on this order' ?>"><?= icon('check', '', 17) ?> <?= e($next[1]) ?></button>
                </form>
            <?php elseif ($needsTime): ?>
                <a class="btn btn--primary" href="#when"><?= icon('clock', '', 17) ?> Set delivery time</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($cancelled): ?>
        <p class="ov-cancelled"><?= icon('alert', '', 18) ?> This order was cancelled.</p>
    <?php else: ?>
        <ol class="ov-steps" aria-label="Order progress">
            <?php foreach ($steps as $i => $key): ?>
                <li class="<?= $at !== false && $i < $at ? 'is-done' : '' ?><?= $i === $at ? 'is-now' : '' ?>"<?= $i === $at ? ' aria-current="step"' : '' ?>><span></span><?= e(order_status_label($order, $key)) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <details class="edit-toggle ov-status">
        <summary><?= icon('pen', '', 16) ?> Change status<?= $cancelled ? '' : ' or cancel the order' ?></summary>
        <form class="form-stack" method="post" style="margin-top:12px">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="status">
            <select class="input" name="order_status">
                <?php foreach (ORDER_STATUSES as $key => $label): ?><option value="<?= e($key) ?>" <?= selected($order['order_status'] === $key) ?>><?= e(order_status_label($order, $key)) ?></option><?php endforeach; ?>
            </select>
            <label class="switch"><input type="checkbox" name="notify" value="1" checked><span class="switch__track"></span><span>Email the customer</span></label>
            <textarea class="input" name="message" rows="2" placeholder="Optional message (a friendly default is used if empty)"></textarea>
            <button class="btn btn--primary" type="submit">Update status</button>
        </form>
    </details>
</section>

<div class="grid-main ov">
    <div class="stack">
        <section class="card ov__items" id="items">
            <div class="card__head"><h2>Cakes</h2><span class="hint"><?= array_sum(array_map(static fn ($i) => (int) $i['quantity'], $items)) ?> item<?= array_sum(array_map(static fn ($i) => (int) $i['quantity'], $items)) === 1 ? '' : 's' ?></span></div>
            <ul class="ov-items">
                <?php foreach ($items as $item): ?>
                    <li>
                        <span class="ov-items__pic"><?= icon('cake', '', 24) ?></span>
                        <div class="ov-items__main">
                            <strong><?= (int) $item['quantity'] ?> &times; <?= e($item['product_name']) ?></strong>
                            <span><?= e(trim(($item['size_label'] ?? '') . ($item['options'] ? ' · ' . $item['options'] : ''), ' ·')) ?><?= ($item['size_label'] ?? '') !== '' || $item['options'] ? ' &middot; ' : '' ?><?= e(money($item['unit_price'])) ?> each</span>
                            <?php if ($item['cake_message']): ?><span class="ov-items__writing">Write on the cake: &ldquo;<?= e($item['cake_message']) ?>&rdquo;</span><?php endif; ?>
                        </div>
                        <strong class="ov-items__price"><?= e(money($item['line_total'])) ?></strong>
                    </li>
                <?php endforeach; ?>
                <?php if (!$items): ?><li class="hint">No items on this order.</li><?php endif; ?>
            </ul>
            <div class="card__body ov-items__foot">
                <div class="totals">
                    <div><span>Subtotal</span><span><?= e(money($order['subtotal'])) ?></span></div>
                    <?php if ((float) $order['discount'] > 0): ?><div><span>Discount<?= $order['coupon_code'] ? ' (' . e((string) $order['coupon_code']) . ')' : '' ?></span><span>-<?= e(money($order['discount'])) ?></span></div><?php endif; ?>
                    <?php if ($delivery): ?><div><span>Delivery</span><span><?= (float) $order['delivery_fee'] > 0 ? e(money($order['delivery_fee'])) : 'Free' ?></span></div><?php endif; ?>
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

        <section class="card ov__pay" id="payment">
            <div class="card__head"><h2>Payment</h2><?= status_badge($order['payment_status'], PAYMENT_STATUSES[$order['payment_status']]) ?></div>
            <div class="card__body">
                <div class="ov-pay<?= $order['payment_proof'] ? ' ov-pay--proof' : '' ?>">
                    <?php if ($order['payment_proof']): ?>
                        <?php if ($proofIsImage): ?>
                            <a class="ov-pay__proof" href="<?= e(admin_url('file', ['path' => $order['payment_proof']])) ?>" target="_blank" title="Open the screenshot full size"><img src="<?= e(admin_url('file', ['path' => $order['payment_proof']])) ?>" alt="Payment screenshot"><span>Tap to zoom</span></a>
                        <?php else: ?>
                            <a class="ov-pay__proof ov-pay__proof--file" href="<?= e(admin_url('file', ['path' => $order['payment_proof']])) ?>" target="_blank"><?= icon('download', '', 22) ?><span>Open the uploaded receipt</span></a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="ov-pay__info">
                        <div class="balance-box">
                            <div><span>Total</span><strong><?= e(money($order['total'])) ?></strong></div>
                            <div><span>Paid</span><strong><?= e(money(order_net_paid($order))) ?></strong></div>
                            <div class="<?= $balance > 0 ? 'is-owing' : ($balance < 0 ? 'is-credit' : '') ?>"><span><?= $balance < 0 ? 'To refund' : 'Owing' ?></span><strong><?= e(money(abs($balance))) ?></strong></div>
                        </div>
                        <p class="ov-pay__method"><?= e(payment_method_label($order['payment_method'])) ?><?php if ($order['payment_reference']): ?> &middot; reference <?= e($order['payment_reference']) ?><?php endif; ?></p>
                        <?php if ($balance > 0 && !$cancelled): ?>
                            <div class="ov-pay__buttons">
                                <form method="post">
                                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="record_payment">
                                    <input type="hidden" name="amount" value="<?= e(number_format($balance, 2, '.', '')) ?>"><input type="hidden" name="method" value="<?= e($order['payment_method']) ?>">
                                    <?php if (valid_email($order['customer_email'])): ?><input type="hidden" name="notify" value="1"><?php endif; ?>
                                    <button class="btn btn--success" type="submit" title="<?= valid_email($order['customer_email']) ? 'Records the full amount and emails the customer a receipt' : 'Records the full amount' ?>"><?= icon('check', '', 17) ?> Money received <?= e(money($balance)) ?></button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($balance > 0 && !$cancelled): ?>
                    <details class="edit-toggle" style="margin-top:14px">
                        <summary><?= icon('card', '', 16) ?> Part payment, cash or another amount</summary>
                        <form method="post" class="form-stack" style="margin-top:12px">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="record_payment">
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
                    </details>
                    <?php if (in_array($order['payment_status'], ['pending', 'awaiting_verification'], true)): ?>
                        <details class="edit-toggle edit-toggle--danger" style="margin-top:10px">
                            <summary><?= icon('close', '', 16) ?> Money not received</summary>
                            <form method="post" class="form-stack" style="margin-top:12px" data-confirm="Tell the customer their payment was not received?">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="reject_payment">
                                <textarea class="input" name="reason" rows="2" placeholder="Optional message to the customer, e.g. the transfer has not arrived yet"></textarea>
                                <button class="btn btn--danger btn--sm" type="submit">Tell the customer it was not received</button>
                            </form>
                        </details>
                    <?php endif; ?>
                    <?php if ($order['payment_method'] === PAYMENT_STRIPE && valid_email($order['customer_email'])): ?>
                        <form method="post" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="send_payment_link">
                            <button class="btn btn--secondary btn--sm" type="submit"><?= icon('card', '', 16) ?> Email a card payment link</button></form>
                    <?php endif; ?>
                <?php endif; ?>
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

        <section class="card ov__history">
            <div class="card__head"><h2>What happened</h2></div>
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
        <section class="card ov-customer ov__customer">
            <div class="card__body">
                <div class="ov-customer__top">
                    <span class="ov-customer__avatar" aria-hidden="true"><?= e($initials ?: '?') ?></span>
                    <div>
                        <strong><?= e($order['customer_name']) ?></strong>
                        <?php if ($orderCount >= 2): ?><span class="ov-customer__badge">Regular customer &middot; <?= e($ordinal($orderCount)) ?> order</span><?php else: ?><span class="ov-customer__badge ov-customer__badge--new">First order</span><?php endif; ?>
                    </div>
                    <?php if ($customer): ?><a class="ov-customer__account" href="<?= e(admin_url('customers/view', ['id' => $customer['id']])) ?>">Account</a><?php endif; ?>
                </div>
                <p class="ov-customer__lines">
                    <?php if ($order['customer_phone'] !== ''): ?><span><?= icon('phone', '', 15) ?> <?= e($order['customer_phone']) ?></span><?php endif; ?>
                    <span><?= icon('mail', '', 15) ?> <?= $order['customer_email'] !== '' ? e($order['customer_email']) : '<span class="muted">No email given</span>' ?></span>
                </p>
                <div class="ov-customer__buttons">
                    <?php if ($phoneDigits !== ''): ?>
                        <a class="btn btn--secondary btn--sm" href="tel:<?= e($order['customer_phone']) ?>"><?= icon('phone', '', 15) ?> Call</a>
                        <a class="btn btn--sm ov-wa" href="https://wa.me/<?= e($waDigits) ?>" target="_blank" rel="noopener"><?= icon('whatsapp', '', 15) ?> WhatsApp</a>
                    <?php endif; ?>
                    <?php if (valid_email((string) $order['customer_email'])): ?><a class="btn btn--secondary btn--sm" href="mailto:<?= e($order['customer_email']) ?>"><?= icon('mail', '', 15) ?> Email</a><?php endif; ?>
                </div>
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

        <section class="card ov__when<?= $needsTime ? ' card--attention' : '' ?>" id="when">
            <div class="card__head"><h2><?= $delivery ? 'Delivery' : 'Pickup' ?></h2><?= $needsTime ? status_badge('pending', 'Set a time') : '' ?></div>
            <div class="card__body">
                <p class="ov-when"><?= e($needsTime ? date('D j M', strtotime($order['pickup_date'])) . ', no time yet' : order_when($order)) ?></p>
                <?php if ($delivery): ?>
                    <p class="hint" style="margin:4px 0 0"><?= e(order_address($order)) ?> &middot; <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode(order_address($order))) ?>" target="_blank" rel="noopener">Open in Maps</a></p>
                    <p class="hint" style="margin:4px 0 0">Customer prefers: <strong><?= e(delivery_window_label($order['delivery_window'] ?? null)) ?></strong></p>
                <?php elseif ($orderBranch): ?>
                    <p class="hint" style="margin:4px 0 0"><?= $manyBranches ? e($orderBranch['name']) . ' &middot; ' : '' ?><?= e(branch_address_line($orderBranch)) ?></p>
                <?php endif; ?>
                <details class="edit-toggle" style="margin-top:12px"<?= $needsTime ? ' open' : '' ?>>
                    <summary><?= icon('clock', '', 16) ?> <?= $needsTime ? 'Set the delivery time' : 'Change date or time' ?></summary>
                    <form class="form-stack" method="post" style="margin-top:12px">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="pickup">
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
                </details>
                <?php if ($manyBranches): ?>
                <?php if ($order['order_status'] !== 'completed'): ?>
                <details class="edit-toggle">
                    <summary class="link-btn">Move to another branch</summary>
                    <form method="post" class="form-stack" style="margin-top:12px" data-confirm="Move this order to the other branch? Its staff will be emailed.">
                        <?= csrf_field() ?><input type="hidden" name="action" value="move_branch">
                        <label class="field"><span>Move to</span><select class="input" name="branch_id"><?php foreach (branches() as $b): if ((int) $b['id'] === (int) $order['branch_id']) continue; ?><option value="<?= (int) $b['id'] ?>"><?= e($b['name']) ?><?= $b['is_active'] ? '' : ' (hidden)' ?></option><?php endforeach; ?></select></label>
                        <?php if (!is_delivery($order)): ?><label class="inline-check"><input type="checkbox" name="tell_customer" value="1" checked> Email the customer the new pickup address</label><?php endif; ?>
                        <button class="btn btn--secondary btn--sm" type="submit">Move order</button>
                    </form>
                </details>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="card ov__notes">
            <div class="card__head"><h2>Staff notes</h2></div>
            <form class="card__body form-stack" method="post">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="action" value="notes">
                <textarea class="input" name="admin_notes" rows="4" placeholder="Only staff can see these notes"><?= e((string) $order['admin_notes']) ?></textarea>
                <button class="btn btn--secondary" type="submit">Save notes</button>
            </form>
        </section>
    </div>
</div>
