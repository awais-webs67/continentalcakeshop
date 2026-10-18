<?php /** @var array $customer @var array $orders @var float $paid @var array $requests */ ?>
<a class="crumb" href="<?= e(admin_url('customers')) ?>"><?= icon('arrow', '', 14) ?> Customers</a>
<div class="page-head">
    <div><h1><?= e($customer['name']) ?></h1><p>Customer since <?= e(date('j F Y', strtotime($customer['created_at']))) ?></p></div>
    <div class="page-head__actions"><a class="btn btn--secondary" href="mailto:<?= e($customer['email']) ?>"><?= icon('mail', '', 18) ?> Email</a></div>
</div>
<div class="stats">
    <div class="stat"><span class="stat__label"><?= icon('bag', '', 16) ?> Orders</span><span class="stat__value"><?= count($orders) ?></span></div>
    <div class="stat"><span class="stat__label"><?= icon('card', '', 16) ?> Paid total</span><span class="stat__value"><?= e(money($paid)) ?></span></div>
    <div class="stat"><span class="stat__label"><?= icon('gift', '', 16) ?> Custom requests</span><span class="stat__value"><?= count($requests) ?></span></div>
    <div class="stat"><span class="stat__label"><?= icon('clock', '', 16) ?> Last login</span><span class="stat__value" style="font-size:18px"><?= $customer['last_login_at'] ? e(time_ago($customer['last_login_at'])) : 'Never' ?></span></div>
</div>
<div class="grid-main">
    <section class="card">
        <div class="card__head"><h2>Orders</h2></div>
        <?php if (!$orders): ?>
            <div class="empty"><p>No orders yet.</p></div>
        <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Order</th><th>Pickup</th><th>Payment</th><th>Status</th><th class="num">Total</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><a class="row-link" href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>"><?= e($o['order_number']) ?></a><div class="muted"><?= e(date('j M Y', strtotime($o['created_at']))) ?><?= $o['user_id'] ? '' : ' &middot; guest' ?></div></td>
                    <td><?= e(date('D j M Y', strtotime($o['pickup_date']))) ?></td>
                    <td><?= status_badge($o['payment_status'], PAYMENT_STATUSES[$o['payment_status']]) ?></td>
                    <td><?= status_badge($o['order_status'], order_status_label($o)) ?></td>
                    <td class="num"><strong><?= e(money($o['total'])) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </section>
    <div class="stack">
        <section class="card">
            <div class="card__head"><h2>Contact</h2></div>
            <div class="card__body"><dl class="dl">
                <dt>Email</dt><dd><a href="mailto:<?= e($customer['email']) ?>"><?= e($customer['email']) ?></a></dd>
                <dt>Phone</dt><dd><?= $customer['phone'] ? '<a href="tel:' . e(preg_replace('/[^\d+]/', '', $customer['phone'])) . '">' . e($customer['phone']) . '</a>' : 'Not given' ?></dd>
            </dl></div>
        </section>
        <?php if ($requests): ?>
        <section class="card">
            <div class="card__head"><h2>Custom requests</h2></div>
            <div class="card__body"><ul class="check-list">
                <?php foreach ($requests as $r): ?>
                    <li><a href="<?= e(admin_url('custom-requests/view', ['id' => $r['id']])) ?>"><?= e($r['occasion']) ?>, <?= e(date('j M Y', strtotime($r['event_date']))) ?></a><?= status_badge($r['status'], ucfirst($r['status'])) ?></li>
                <?php endforeach; ?>
            </ul></div>
        </section>
        <?php endif; ?>
    </div>
</div>
