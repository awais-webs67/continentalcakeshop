<?php /** @var array $order @var array $items @var array $events @var array $payments @var ?array $customer @var bool $proofIsImage @var bool $hasStripePayment @var int $orderCount */
$id = (int) $order['id'];
$delivery = is_delivery($order);
$balance = order_balance($order);
$paidNet = order_net_paid($order);
$next = order_next_step($order);
$cancelled = $order['order_status'] === 'cancelled';
$orderBranch = get_branch((int) $order['branch_id']);
$manyBranches = count(branches()) > 1;
$orderThread = admin_can('messages') ? db_one('SELECT id, unread_admin FROM threads WHERE order_id = ? AND last_message_at IS NOT NULL', [$id]) : null;
$phoneDigits = preg_replace('/\D+/', '', (string) $order['customer_phone']);
$waDigits = str_starts_with($phoneDigits, '0') ? '61' . substr($phoneDigits, 1) : $phoneDigits;
$hasEmail = valid_email((string) $order['customer_email']);
$needsTime = $delivery && !$order['pickup_time'] && !$cancelled;
$confirmPays = confirm_takes_payment($order);
$steps = ['new', 'confirmed', 'in_progress', 'ready', 'completed'];
$at = array_search($order['order_status'], $steps, true);
$ordinal = static fn (int $n): string => $n . (in_array($n % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th'));
$idField = csrf_field() . '<input type="hidden" name="id" value="' . $id . '">';
$quantity = array_sum(array_map(static fn ($i) => (int) $i['quantity'], $items));
?>
<div class="ov-top">
    <a class="ov-back" href="<?= e(admin_url('orders')) ?>" aria-label="Back to orders"><?= icon('arrow', '', 18) ?></a>
    <div class="ov-top__title">
        <h1><?= e($order['order_number']) ?></h1>
        <?= status_badge($order['order_status'], order_status_label($order)) ?>
        <?= status_badge($order['payment_status'], PAYMENT_STATUSES[$order['payment_status']]) ?>
        <p><?= e(date('j M Y, g:ia', strtotime($order['created_at']))) ?> &middot; <?= e(($order['channel'] ?? 'online') === 'online' ? 'Website' : (ORDER_CHANNELS[$order['channel']] ?? 'Staff order')) ?><?php if ($manyBranches && $orderBranch): ?> &middot; <?= e($orderBranch['name']) ?><?php endif; ?></p>
    </div>
    <div class="ov-top__actions">
        <details class="dropdown">
            <summary class="btn btn--secondary"><?= icon('download', '', 16) ?> Print <?= icon('down', '', 14) ?></summary>
            <div class="dropdown__panel">
                <a href="<?= e(invoice_url($order)) ?>" target="_blank"><?= icon('card', '', 17) ?> Receipt</a>
                <a href="<?= e(invoice_url($order) . '&format=thermal') ?>" target="_blank"><?= icon('download', '', 17) ?> Thermal receipt (80mm)</a>
                <a href="<?= e(admin_url('orders/print', ['id' => $id])) ?>" target="_blank"><?= icon('cake', '', 17) ?> Kitchen ticket</a>
            </div>
        </details>
        <details class="dropdown">
            <summary class="btn btn--secondary">More <?= icon('down', '', 14) ?></summary>
            <div class="dropdown__panel ov-more">
                <p class="dropdown__title">Order status</p>
                <?php foreach ($steps as $i => $key): $isNow = $order['order_status'] === $key; ?>
                    <form method="post">
                        <?= $idField ?><input type="hidden" name="action" value="status"><input type="hidden" name="order_status" value="<?= e($key) ?>"><input type="hidden" name="notify" value="1">
                        <button type="submit" class="ov-more__step<?= $isNow ? ' is-now' : '' ?><?= $at !== false && $i < $at ? ' is-done' : '' ?>"<?= $isNow ? ' disabled aria-current="step"' : '' ?>>
                            <span class="ov-more__num"><?= $at !== false && $i < $at ? icon('check', '', 13) : $i + 1 ?></span>
                            <?= e(order_status_label($order, $key)) ?><?= $key === 'confirmed' && $confirmPays ? ' &amp; paid' : '' ?><?= $isNow ? ' <small>now</small>' : '' ?>
                        </button>
                    </form>
                <?php endforeach; ?>
                <?php if (!$cancelled): ?>
                    <form method="post" data-confirm="Cancel order <?= e($order['order_number']) ?>? The customer will be emailed.">
                        <?= $idField ?><input type="hidden" name="action" value="status"><input type="hidden" name="order_status" value="cancelled"><input type="hidden" name="notify" value="1">
                        <button type="submit" class="ov-more__cancel"><?= icon('close', '', 15) ?> Cancel order</button>
                    </form>
                <?php endif; ?>
                <button type="button" data-open="ov-status"><?= icon('pen', '', 17) ?> Change status with a message</button>
                <hr class="dropdown__sep">
                <?php if (admin_can('messages')): ?><a href="<?= e(admin_url('messages/view', ['order' => $id])) ?>"><?= icon('mail', '', 17) ?> <?= $orderThread ? 'Conversation' . ($orderThread['unread_admin'] ? ' (new)' : '') : 'Message customer' ?></a><?php endif; ?>
                <a href="<?= e(order_url($order)) ?>" target="_blank"><?= icon('user', '', 17) ?> Customer's order page</a>
            </div>
        </details>
        <?php if ($needsTime): ?>
            <button class="btn btn--primary" type="button" data-open="ov-when"><?= icon('clock', '', 16) ?> Set delivery time</button>
        <?php elseif ($next): ?>
            <form method="post">
                <?= $idField ?><input type="hidden" name="action" value="status"><input type="hidden" name="order_status" value="<?= e($next[0]) ?>"><input type="hidden" name="notify" value="1">
                <button class="btn btn--primary" type="submit" title="<?= $hasEmail ? 'Also emails the customer' : 'No email address on this order' ?>"><?= icon('check', '', 16) ?> <?= e($next[1]) ?></button>
            </form>
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

<section class="card ov-panel" id="ov-status" hidden>
    <div class="card__head"><h2>Change status</h2><button class="link-btn" type="button" data-open="ov-status">Close</button></div>
    <form class="card__body form-stack" method="post">
        <?= $idField ?><input type="hidden" name="action" value="status">
        <select class="input" name="order_status">
            <?php foreach (ORDER_STATUSES as $key => $label): ?><option value="<?= e($key) ?>" <?= selected($order['order_status'] === $key) ?>><?= e(order_status_label($order, $key)) ?></option><?php endforeach; ?>
        </select>
        <label class="switch"><input type="checkbox" name="notify" value="1" checked><span class="switch__track"></span><span>Email the customer</span></label>
        <textarea class="input" name="message" rows="2" placeholder="Message to the customer (optional)"></textarea>
        <div><button class="btn btn--primary" type="submit">Save status</button></div>
    </form>
</section>

<div class="grid-main ov">
    <div class="stack">
        <section class="card ov__items" id="items">
            <div class="card__head">
                <h2>Items <span class="count"><?= $quantity ?></span></h2>
                <?php if (!$cancelled): ?><button class="link-btn" type="button" data-open="ov-edit-items"><?= icon('pen', '', 15) ?> Edit</button><?php endif; ?>
            </div>
            <table class="ov-table">
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="ov-table__qty"><?= (int) $item['quantity'] ?> &times;</td>
                        <td>
                            <strong><?= e($item['product_name']) ?></strong>
                            <small><?= e(trim(($item['size_label'] ?? '') . ($item['options'] ? ' · ' . $item['options'] : ''), ' ·') ?: 'Standard') ?> &middot; <?= e(money($item['unit_price'])) ?> each</small>
                            <?php if ($item['cake_message']): ?><span class="ov-writing"><?= icon('pen', '', 13) ?> &ldquo;<?= e($item['cake_message']) ?>&rdquo;</span><?php endif; ?>
                        </td>
                        <td class="ov-table__money"><?= e(money($item['line_total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$items): ?><tr><td colspan="3" class="muted">No items on this order.</td></tr><?php endif; ?>
                </tbody>
            </table>
            <?php if ($order['notes']): ?><p class="ov-note"><strong>Customer note</strong><?= nl2br(e($order['notes'])) ?></p><?php endif; ?>
            <?php if (!$cancelled): ?>
            <div class="ov-panel ov-panel--inline" id="ov-edit-items" hidden>
                <form method="post" class="form-stack">
                    <?= $idField ?><input type="hidden" name="action" value="items">
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
                    <div class="form-actions-row"><button class="btn btn--primary" type="submit">Save items</button><button class="btn btn--ghost" type="button" data-open="ov-edit-items">Cancel</button></div>
                </form>
            </div>
            <?php endif; ?>
        </section>

        <section class="card ov__pay" id="payment">
            <div class="card__head"><h2>Payment</h2><?= status_badge($order['payment_status'], PAYMENT_STATUSES[$order['payment_status']]) ?></div>
            <div class="ov-pay<?= $order['payment_proof'] ? ' ov-pay--proof' : '' ?>">
                <dl class="ov-sum">
                    <dt>Subtotal</dt><dd><?= e(money($order['subtotal'])) ?></dd>
                    <?php if ((float) $order['discount'] > 0): ?><dt>Discount<?= $order['coupon_code'] ? ' (' . e((string) $order['coupon_code']) . ')' : '' ?></dt><dd>&minus;<?= e(money($order['discount'])) ?></dd><?php endif; ?>
                    <?php if ($delivery): ?><dt>Delivery</dt><dd><?= (float) $order['delivery_fee'] > 0 ? e(money($order['delivery_fee'])) : 'Free' ?></dd><?php endif; ?>
                    <dt class="ov-sum__total">Total</dt><dd class="ov-sum__total"><?= e(money($order['total'])) ?></dd>
                    <dt>Paid<?= $order['payment_method'] ? ' &middot; ' . e(payment_method_label($order['payment_method'])) : '' ?></dt><dd><?= e(money($paidNet)) ?></dd>
                    <dt class="ov-sum__balance<?= $balance > 0.004 ? ' is-owing' : '' ?>"><?= $balance < -0.004 ? 'To refund' : 'Balance' ?></dt><dd class="ov-sum__balance<?= $balance > 0.004 ? ' is-owing' : '' ?>"><?= e(money(abs($balance))) ?></dd>
                </dl>
                <?php if ($order['payment_proof']): ?>
                    <?php if ($proofIsImage): ?>
                        <?php $proofUrl = admin_url('file', ['path' => $order['payment_proof']]); ?>
                        <a class="ov-proof" href="<?= e($proofUrl) ?>" target="_blank" data-lightbox title="Click to see it full size"><img src="<?= e($proofUrl) ?>" alt="Payment screenshot"><span><?= icon('search', '', 12) ?> Zoom</span><img class="ov-proof__peek" src="<?= e($proofUrl) ?>" alt="" aria-hidden="true"></a>
                    <?php else: ?>
                        <a class="ov-proof ov-proof--file" href="<?= e(admin_url('file', ['path' => $order['payment_proof']])) ?>" target="_blank"><?= icon('download', '', 22) ?><span>Receipt file</span></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php if ($order['payment_reference']): ?><p class="ov-muted-line ov-pad">Reference <?= e($order['payment_reference']) ?></p><?php endif; ?>

            <?php if (!$cancelled && ($balance > 0.004 || $paidNet > 0)): ?>
            <div class="ov-actions">
                <?php if ($balance > 0.004): ?>
                    <?php if ($confirmPays): ?>
                        <form method="post"><?= $idField ?><input type="hidden" name="action" value="status"><input type="hidden" name="order_status" value="confirmed"><input type="hidden" name="notify" value="1">
                            <button class="btn btn--primary" type="submit" title="Confirms the order, records the payment and sends the customer one email"><?= icon('check', '', 16) ?> Money received, confirm</button></form>
                    <?php else: ?>
                        <form method="post"><?= $idField ?><input type="hidden" name="action" value="record_payment"><input type="hidden" name="amount" value="<?= e(number_format($balance, 2, '.', '')) ?>"><input type="hidden" name="method" value="<?= e($order['payment_method']) ?>"><?php if ($hasEmail): ?><input type="hidden" name="notify" value="1"><?php endif; ?>
                            <button class="btn btn--primary" type="submit" title="Records the full amount<?= $hasEmail ? ' and emails a receipt' : '' ?>"><?= icon('check', '', 16) ?> Mark as paid</button></form>
                    <?php endif; ?>
                    <button class="btn btn--secondary" type="button" data-open="ov-pay-other">Other amount</button>
                    <?php if (in_array($order['payment_status'], ['pending', 'awaiting_verification'], true)): ?><button class="btn btn--secondary btn--danger-text" type="button" data-open="ov-pay-reject">Not received</button><?php endif; ?>
                    <?php if ($order['payment_method'] === PAYMENT_STRIPE && $hasEmail): ?>
                        <form method="post"><?= $idField ?><input type="hidden" name="action" value="send_payment_link"><button class="btn btn--secondary" type="submit"><?= icon('card', '', 15) ?> Email card link</button></form>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($paidNet > 0): ?><button class="btn btn--secondary" type="button" data-open="ov-pay-refund">Refund</button><?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($balance > 0.004 && !$cancelled): ?>
            <div class="ov-panel ov-panel--inline" id="ov-pay-other" hidden>
                <form method="post" class="form-stack">
                    <?= $idField ?><input type="hidden" name="action" value="record_payment">
                    <div class="form-grid">
                        <label class="field"><span>Amount</span><div class="input-prefix"><span>$</span><input class="input" name="amount" inputmode="decimal" value="<?= e(number_format($balance, 2, '.', '')) ?>"></div></label>
                        <label class="field"><span>Paid by</span>
                            <select class="input" name="method">
                                <?php foreach (staff_payment_methods() as $key => $label): ?><option value="<?= e($key) ?>" <?= selected($key === $order['payment_method']) ?>><?= e($label) ?></option><?php endforeach; ?>
                            </select></label>
                        <label class="field"><span>Reference <small>optional</small></span><input class="input" name="reference" maxlength="120" placeholder="Bank receipt number"></label>
                        <label class="field"><span>Note <small>optional</small></span><input class="input" name="note" maxlength="200" placeholder="Deposit"></label>
                    </div>
                    <label class="switch"><input type="checkbox" name="notify" value="1" <?= checked($hasEmail) ?>><span class="switch__track"></span><span>Email the customer a receipt</span></label>
                    <div class="form-actions-row"><button class="btn btn--primary" type="submit">Record payment</button><button class="btn btn--ghost" type="button" data-open="ov-pay-other">Cancel</button></div>
                </form>
            </div>
            <?php if (in_array($order['payment_status'], ['pending', 'awaiting_verification'], true)): ?>
            <div class="ov-panel ov-panel--inline" id="ov-pay-reject" hidden>
                <form method="post" class="form-stack" data-confirm="Tell the customer their payment was not received?">
                    <?= $idField ?><input type="hidden" name="action" value="reject_payment">
                    <textarea class="input" name="reason" rows="2" placeholder="Message to the customer (optional), e.g. the transfer has not arrived yet"></textarea>
                    <div class="form-actions-row"><button class="btn btn--danger" type="submit">Tell the customer</button><button class="btn btn--ghost" type="button" data-open="ov-pay-reject">Cancel</button></div>
                </form>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($paidNet > 0): ?>
            <div class="ov-panel ov-panel--inline" id="ov-pay-refund"<?= $balance < -0.004 ? '' : ' hidden' ?>>
                <form method="post" class="form-stack" data-confirm="Record this refund?">
                    <?= $idField ?><input type="hidden" name="action" value="refund">
                    <?php if ($balance < -0.004): ?><p class="hint" style="margin:0"><?= e(money(-$balance)) ?> is owed back to the customer.</p><?php endif; ?>
                    <div class="form-grid">
                        <label class="field"><span>Amount <small>up to <?= e(money($paidNet)) ?></small></span><div class="input-prefix"><span>$</span><input class="input" name="amount" inputmode="decimal" value="<?= e(number_format($balance < 0 ? -$balance : $paidNet, 2, '.', '')) ?>"></div></label>
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
                    <label class="switch"><input type="checkbox" name="notify" value="1" <?= checked($hasEmail) ?>><span class="switch__track"></span><span>Email the customer</span></label>
                    <div class="form-actions-row"><button class="btn btn--danger" type="submit">Record refund</button><button class="btn btn--ghost" type="button" data-open="ov-pay-refund">Cancel</button></div>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($payments): ?>
                <table class="money-table ov-payments">
                    <?php foreach ($payments as $p): ?>
                        <tr class="<?= $p['kind'] === 'refund' ? 'is-refund' : '' ?>">
                            <td><?= $p['kind'] === 'refund' ? 'Refund' : 'Payment' ?> &middot; <?= e(payment_method_label($p['method'])) ?><div class="muted"><?= e(date('j M Y, g:ia', strtotime($p['created_at']))) ?><?= $p['admin_name'] ? ' by ' . e($p['admin_name']) : '' ?><?= $p['note'] ? ' &middot; ' . e($p['note']) : '' ?></div></td>
                            <td><?= $p['kind'] === 'refund' ? '&minus;' : '' ?><?= e(money($p['amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </section>

        <section class="card ov__history">
            <div class="card__head"><h2>Timeline</h2></div>
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
        <section class="card ov__customer">
            <div class="card__head"><h2>Customer</h2><button class="link-btn" type="button" data-open="ov-edit-customer"><?= icon('pen', '', 15) ?> Edit</button></div>
            <div class="card__body">
                <p class="ov-customer__name">
                    <?php if ($customer): ?><a href="<?= e(admin_url('customers/view', ['id' => $customer['id']])) ?>"><?= e($order['customer_name']) ?></a><?php else: ?><strong><?= e($order['customer_name']) ?></strong><?php endif; ?>
                    <small><?= $orderCount >= 2 ? e($ordinal($orderCount)) . ' order' : 'First order' ?></small>
                </p>
                <ul class="ov-contact">
                    <?php if ($order['customer_phone'] !== ''): ?><li><?= icon('phone', '', 15) ?> <a href="tel:<?= e($order['customer_phone']) ?>"><?= e($order['customer_phone']) ?></a></li><?php endif; ?>
                    <li><?= icon('mail', '', 15) ?> <?= $order['customer_email'] !== '' ? '<a href="mailto:' . e($order['customer_email']) . '">' . e($order['customer_email']) . '</a>' : '<span class="muted">No email</span>' ?></li>
                </ul>
                <?php if ($phoneDigits !== ''): ?>
                <div class="ov-contact__buttons">
                    <a class="btn btn--secondary btn--sm" href="tel:<?= e($order['customer_phone']) ?>"><?= icon('phone', '', 15) ?> Call</a>
                    <a class="btn btn--secondary btn--sm ov-wa" href="https://wa.me/<?= e($waDigits) ?>" target="_blank" rel="noopener"><?= icon('whatsapp', '', 15) ?> WhatsApp</a>
                </div>
                <?php endif; ?>
                <div class="ov-panel ov-panel--inline ov-panel--flush" id="ov-edit-customer" hidden>
                    <form method="post" class="form-stack">
                        <?= $idField ?><input type="hidden" name="action" value="details">
                        <label class="field"><span>Name</span><input class="input" name="customer_name" maxlength="120" value="<?= e($order['customer_name']) ?>" required></label>
                        <label class="field"><span>Phone</span><input class="input" name="customer_phone" maxlength="40" value="<?= e($order['customer_phone']) ?>"></label>
                        <label class="field"><span>Email <small>optional</small></span><input class="input" type="email" name="customer_email" maxlength="190" value="<?= e($order['customer_email']) ?>"></label>
                        <?php if ($delivery): ?>
                            <label class="field"><span>Street address</span><input class="input" name="delivery_address" maxlength="255" value="<?= e((string) $order['delivery_address']) ?>"></label>
                            <div class="form-grid">
                                <label class="field"><span>Suburb</span><input class="input" name="delivery_suburb" maxlength="80" value="<?= e((string) $order['delivery_suburb']) ?>"></label>
                                <label class="field"><span>Postcode</span><input class="input" name="delivery_postcode" maxlength="4" inputmode="numeric" value="<?= e((string) $order['delivery_postcode']) ?>"></label>
                            </div>
                        <?php endif; ?>
                        <div class="form-actions-row"><button class="btn btn--primary btn--sm" type="submit">Save</button><button class="btn btn--ghost btn--sm" type="button" data-open="ov-edit-customer">Cancel</button></div>
                    </form>
                </div>
            </div>
        </section>

        <section class="card ov__when<?= $needsTime ? ' card--attention' : '' ?>" id="when">
            <div class="card__head"><h2><?= $delivery ? 'Delivery' : 'Pickup' ?></h2><button class="link-btn" type="button" data-open="ov-when"><?= icon('pen', '', 15) ?> Edit</button></div>
            <div class="card__body">
                <p class="ov-when"><?= e($needsTime ? date('D j M', strtotime($order['pickup_date'])) . ' · time not set' : order_when($order)) ?></p>
                <?php if ($delivery): ?>
                    <p class="ov-muted-line"><?= e(order_address($order)) ?> &middot; <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode(order_address($order))) ?>" target="_blank" rel="noopener">Map</a></p>
                    <p class="ov-muted-line">Prefers <?= e(delivery_window_label($order['delivery_window'] ?? null)) ?></p>
                <?php elseif ($orderBranch): ?>
                    <p class="ov-muted-line"><?= $manyBranches ? e($orderBranch['name']) . ' &middot; ' : '' ?><?= e(branch_address_line($orderBranch)) ?></p>
                <?php endif; ?>
                <div class="ov-panel ov-panel--inline ov-panel--flush" id="ov-when"<?= $needsTime ? '' : ' hidden' ?>>
                    <form class="form-stack" method="post">
                        <?= $idField ?><input type="hidden" name="action" value="pickup">
                        <label class="field"><span>Date</span><input class="input" type="date" name="pickup_date" value="<?= e($order['pickup_date']) ?>" required></label>
                        <div class="form-grid">
                            <label class="field"><span><?= $delivery ? 'From' : 'Time' ?></span><input class="input" type="time" name="pickup_time" value="<?= e(substr((string) $order['pickup_time'], 0, 5)) ?>" step="300" required></label>
                            <?php if ($delivery): ?>
                                <label class="field"><span>To <small>optional</small></span><input class="input" type="time" name="delivery_time_to" value="<?= e(substr((string) ($order['delivery_time_to'] ?? ''), 0, 5)) ?>" step="300"></label>
                            <?php endif; ?>
                        </div>
                        <label class="switch"><input type="checkbox" name="notify" value="1" checked><span class="switch__track"></span><span>Email the time to the customer</span></label>
                        <div class="form-actions-row"><button class="btn btn--primary btn--sm" type="submit"><?= $delivery && !$order['pickup_time'] ? 'Confirm time' : 'Save' ?></button><?php if (!$needsTime): ?><button class="btn btn--ghost btn--sm" type="button" data-open="ov-when">Cancel</button><?php endif; ?></div>
                    </form>
                    <?php if ($manyBranches && $order['order_status'] !== 'completed'): ?>
                        <form method="post" class="form-stack ov-move" data-confirm="Move this order to the other shop? Its staff will be emailed.">
                            <?= csrf_field() ?><input type="hidden" name="action" value="move_branch">
                            <label class="field"><span>Move to another shop</span><select class="input" name="branch_id"><?php foreach (branches() as $b): if ((int) $b['id'] === (int) $order['branch_id']) continue; ?><option value="<?= (int) $b['id'] ?>"><?= e($b['name']) ?><?= $b['is_active'] ? '' : ' (hidden)' ?></option><?php endforeach; ?></select></label>
                            <?php if (!$delivery): ?><label class="inline-check"><input type="checkbox" name="tell_customer" value="1" checked> Email the customer the new pickup address</label><?php endif; ?>
                            <div><button class="btn btn--secondary btn--sm" type="submit">Move order</button></div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="card ov__notes">
            <div class="card__head"><h2>Staff notes</h2></div>
            <form class="card__body form-stack" method="post">
                <?= $idField ?><input type="hidden" name="action" value="notes">
                <textarea class="input" name="admin_notes" rows="3" placeholder="Only staff see this"><?= e((string) $order['admin_notes']) ?></textarea>
                <div><button class="btn btn--secondary btn--sm" type="submit">Save note</button></div>
            </form>
        </section>
    </div>
</div>
