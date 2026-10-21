<?php /** @var array $result @var array $counts */ ?>
<div class="page-head">
    <div><h1>Orders</h1><p>Orders from the website, by phone and in the shop.</p></div>
    <div class="page-head__actions"><a class="btn btn--primary" href="<?= e(admin_url('orders/new')) ?>"><?= icon('plus', '', 18) ?> New order</a><a class="btn btn--secondary" href="<?= e(admin_url('pickups')) ?>"><?= icon('calendar', '', 18) ?> Pickups & deliveries</a></div>
</div>
<section class="card">
    <nav class="tabs">
        <a href="<?= e(admin_url('orders')) ?>" class="<?= $status === '' ? 'is-active' : '' ?>">All<span><?= array_sum($counts) ?></span></a>
        <?php foreach (ORDER_STATUSES as $key => $label): ?>
            <a href="<?= e(admin_url('orders', ['status' => $key])) ?>" class="<?= $status === $key ? 'is-active' : '' ?>"><?= e($label) ?><span><?= (int) ($counts[$key] ?? 0) ?></span></a>
        <?php endforeach; ?>
    </nav>
    <form class="toolbar" method="get" action="<?= e(admin_url('orders')) ?>">
        <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
        <input class="input grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Order number, name, email or phone">
        <select class="input" name="payment" data-auto-submit>
            <option value="">Any payment</option>
            <?php foreach (PAYMENT_STATUSES as $key => $label): ?><option value="<?= e($key) ?>" <?= selected($payment === $key) ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
        <label class="hint">Pickup from <input class="input" type="date" name="from" value="<?= e($from) ?>"></label>
        <label class="hint">to <input class="input" type="date" name="to" value="<?= e($to) ?>"></label>
        <button class="btn btn--secondary" type="submit">Filter</button>
        <?php if ($q || $payment || $from || $to): ?><a class="btn btn--ghost" href="<?= e(admin_url('orders', $status ? ['status' => $status] : [])) ?>">Clear</a><?php endif; ?>
    </form>
    <?php if (!$result['rows']): ?>
        <div class="empty"><?= icon('bag', '', 32) ?><p>No orders match these filters.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Order</th><th>Customer</th><th>Pickup / delivery</th><th>Payment</th><th>Status</th><th class="num">Total</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $o): ?>
                <tr>
                    <td><a class="row-link" href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>"><?= e($o['order_number']) ?></a><div class="muted"><?= e(date('j M Y, g:ia', strtotime($o['created_at']))) ?><?= ($o['channel'] ?? 'online') !== 'online' ? ' &middot; ' . e(ORDER_CHANNELS[$o['channel']] ?? '') : '' ?></div></td>
                    <td><?= e($o['customer_name']) ?><div class="muted"><?= e($o['customer_phone']) ?></div></td>
                    <td><?= e(date('D j M', strtotime($o['pickup_date']))) ?><div class="muted"><?= is_delivery($o) ? 'Delivery, ' : '' ?><?= e(order_time_label($o)) ?> &middot; <?= (int) $o['item_count'] ?> item<?= (int) $o['item_count'] === 1 ? '' : 's' ?></div></td>
                    <td><?= status_badge($o['payment_status'], PAYMENT_STATUSES[$o['payment_status']]) ?><div class="muted"><?= e(payment_method_label($o['payment_method'])) ?></div></td>
                    <td><?= status_badge($o['order_status'], order_status_label($o)) ?></td>
                    <td class="num"><strong><?= e(money($o['total'])) ?></strong><?php if ((float) $o['amount_paid'] > 0 && order_balance($o) > 0): ?><div class="muted"><?= e(money(order_balance($o))) ?> owing</div><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
    <?php require __DIR__ . '/partials/pager.php'; ?>
</section>
