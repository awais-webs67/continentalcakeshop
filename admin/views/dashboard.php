<?php /** @var array $stats @var array $todayPickups @var array $toVerify @var array $recent @var array $requests @var int $unread @var array $admin */ ?>
<div class="page-head">
    <div>
        <h1>Good <?= (int) date('G') < 12 ? 'morning' : ((int) date('G') < 17 ? 'afternoon' : 'evening') ?>, <?= e(strtok($admin['name'], ' ')) ?></h1>
        <p><?= e(date('l j F Y')) ?> &middot; <?= e(today_hours_label()) ?></p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= e(admin_url('pickups')) ?>"><?= icon('calendar', '', 18) ?> Pickup schedule</a>
        <a class="btn btn--primary" href="<?= e(admin_url('products/edit')) ?>"><?= icon('plus', '', 18) ?> Add product</a>
    </div>
</div>

<div class="stats">
    <a class="stat" href="<?= e(admin_url('pickups')) ?>">
        <span class="stat__label"><?= icon('calendar', '', 18) ?> Pickups today</span>
        <span class="stat__value"><?= $stats['pickups_today'] ?></span>
        <span class="stat__note">Orders to have ready today</span>
    </a>
    <a class="stat <?= $stats['to_verify'] ? 'stat--alert' : '' ?>" href="<?= e(admin_url('orders', ['payment' => 'awaiting_verification'])) ?>">
        <span class="stat__label"><?= icon('card', '', 18) ?> Payments to check</span>
        <span class="stat__value"><?= $stats['to_verify'] ?></span>
        <span class="stat__note">PayID and bank screenshots</span>
    </a>
    <a class="stat" href="<?= e(admin_url('orders', ['status' => 'new'])) ?>">
        <span class="stat__label"><?= icon('bag', '', 18) ?> New orders</span>
        <span class="stat__value"><?= $stats['new_orders'] ?></span>
        <span class="stat__note">Not confirmed yet</span>
    </a>
    <div class="stat">
        <span class="stat__label"><?= icon('star', '', 18) ?> Paid sales this month</span>
        <span class="stat__value"><?= e(money($stats['month_sales'])) ?></span>
        <span class="stat__note"><?= $stats['month_orders'] ?> orders in <?= e(date('F')) ?></span>
    </div>
</div>

<div class="grid-main">
    <div class="stack">
        <section class="card">
            <div class="card__head"><h2>Today's pickups</h2><a href="<?= e(admin_url('pickups')) ?>">Full schedule</a></div>
            <?php if (!$todayPickups): ?>
                <div class="empty"><?= icon('calendar', '', 32) ?><p>No pickups booked for today.</p></div>
            <?php else: ?>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Time</th><th>Order</th><th>Customer</th><th>Status</th><th>Payment</th></tr></thead>
                    <tbody>
                    <?php foreach ($todayPickups as $o): ?>
                        <tr>
                            <td><strong><?= e(format_time($o['pickup_time'])) ?></strong></td>
                            <td><a class="row-link" href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>"><?= e($o['order_number']) ?></a><div class="muted"><?= (int) $o['item_count'] ?> item<?= (int) $o['item_count'] === 1 ? '' : 's' ?></div></td>
                            <td><?= e($o['customer_name']) ?><div class="muted"><?= e($o['customer_phone']) ?></div></td>
                            <td><?= status_badge($o['order_status'], ORDER_STATUSES[$o['order_status']]) ?></td>
                            <td><?= status_badge($o['payment_status'], PAYMENT_STATUSES[$o['payment_status']]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__head"><h2>Recent orders</h2><a href="<?= e(admin_url('orders')) ?>">All orders</a></div>
            <?php if (!$recent): ?>
                <div class="empty"><?= icon('bag', '', 32) ?><p>Orders will appear here as soon as customers start ordering.</p></div>
            <?php else: ?>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Pickup</th><th>Status</th><th class="num">Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $o): ?>
                        <tr>
                            <td><a class="row-link" href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>"><?= e($o['order_number']) ?></a><div class="muted"><?= e(time_ago($o['created_at'])) ?></div></td>
                            <td><?= e($o['customer_name']) ?></td>
                            <td><?= e(date('D j M', strtotime($o['pickup_date']))) ?>, <?= e(format_time($o['pickup_time'])) ?></td>
                            <td><?= status_badge($o['order_status'], ORDER_STATUSES[$o['order_status']]) ?></td>
                            <td class="num"><strong><?= e(money($o['total'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card__head"><h2>Payments to check</h2></div>
            <div class="card__body">
                <?php if (!$toVerify): ?>
                    <p class="hint">All payment screenshots have been checked.</p>
                <?php endif; ?>
                <?php foreach ($toVerify as $o): ?>
                    <p><a class="row-link" href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>"><strong><?= e($o['order_number']) ?></strong></a> &middot; <?= e(money($o['total'])) ?><br><span class="hint"><?= e($o['customer_name']) ?>, <?= e(payment_method_label($o['payment_method'])) ?></span></p>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="card">
            <div class="card__head"><h2>New custom requests</h2><a href="<?= e(admin_url('custom-requests')) ?>">View all</a></div>
            <div class="card__body">
                <?php if (!$requests): ?><p class="hint">No new requests.</p><?php endif; ?>
                <?php foreach ($requests as $r): ?>
                    <p><a class="row-link" href="<?= e(admin_url('custom-requests/view', ['id' => $r['id']])) ?>"><strong><?= e($r['name']) ?></strong></a><br><span class="hint"><?= e($r['occasion']) ?> &middot; needed <?= e(date('D j M', strtotime($r['event_date']))) ?></span></p>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="card">
            <div class="card__head"><h2>Messages</h2><a href="<?= e(admin_url('messages')) ?>">Open</a></div>
            <div class="card__body"><p class="hint"><?= $unread ? $unread . ' unread message' . ($unread === 1 ? '' : 's') . ' from the contact form.' : 'No unread messages.' ?></p></div>
        </section>
    </div>
</div>
