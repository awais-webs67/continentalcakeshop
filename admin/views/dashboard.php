<?php /** @var array $stats @var array $todayOrders @var array $toSchedule @var array $toVerify @var array $recent @var ?array $requests @var ?array $threads @var ?array $chart @var bool $canOrders @var array $admin */
$hour = (int) date('G');
?>
<div class="page-head">
    <div>
        <h1>Good <?= $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening') ?>, <?= e(strtok($admin['name'], ' ')) ?></h1>
        <p><?= e(date('l j F Y')) ?> &middot; <?= e(today_hours_label()) ?></p>
    </div>
    <div class="page-head__actions">
        <?php if ($canOrders): ?><a class="btn btn--secondary" href="<?= e(admin_url('pickups')) ?>"><?= icon('calendar', '', 18) ?> Pickups & deliveries</a><?php endif; ?>
        <?php if (admin_can('products', $admin)): ?><a class="btn btn--primary" href="<?= e(admin_url('products/edit')) ?>"><?= icon('plus', '', 18) ?> Add product</a><?php endif; ?>
    </div>
</div>

<div data-live="dashboard">
<?php if ($admin['role'] === 'owner' && !available_payment_methods()): ?>
    <div class="alert alert--error" style="margin-bottom:16px"><strong>Customers cannot pay at checkout yet.</strong> Add your PayID, bank details or Stripe key and switch the method on in <a href="<?= e(admin_url('settings', ['tab' => 'payments'])) ?>">Settings &gt; Payments</a>.</div>
<?php endif; ?>

<?php if ($canOrders): ?>
<div class="stats">
    <a class="stat" href="<?= e(admin_url('pickups')) ?>">
        <span class="stat__label"><?= icon('calendar', '', 18) ?> Due today</span>
        <span class="stat__value"><?= $stats['due_today'] ?></span>
        <span class="stat__note">Pickups and deliveries</span>
    </a>
    <a class="stat <?= $stats['to_verify'] ? 'stat--alert' : '' ?>" href="<?= e(admin_url('orders', ['payment' => 'awaiting_verification'])) ?>">
        <span class="stat__label"><?= icon('card', '', 18) ?> Payments to check</span>
        <span class="stat__value"><?= $stats['to_verify'] ?></span>
        <span class="stat__note">PayID and bank screenshots</span>
    </a>
    <a class="stat <?= $stats['new_orders'] ? 'stat--alert' : '' ?>" href="<?= e(admin_url('orders', ['status' => 'new'])) ?>">
        <span class="stat__label"><?= icon('bag', '', 18) ?> New orders</span>
        <span class="stat__value"><?= $stats['new_orders'] ?></span>
        <span class="stat__note">Not confirmed yet</span>
    </a>
    <a class="stat stat--brand" href="<?= e(admin_can('reports', $admin) ? admin_url('reports') : admin_url('orders')) ?>">
        <span class="stat__label"><?= icon('chart', '', 18) ?> Sales this month</span>
        <span class="stat__value"><?= e(money($stats['month_sales'])) ?></span>
        <span class="stat__note"><?= $stats['month_orders'] ?> orders in <?= e(date('F')) ?></span>
    </a>
</div>
<?php endif; ?>

<div class="grid-main">
    <div class="stack">
        <?php if ($toSchedule): ?>
        <section class="card card--attention">
            <div class="card__head"><h2>Deliveries to schedule</h2><span class="hint">Set a time and the customer is emailed</span></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Order</th><th>Date</th><th>Suburb</th><th class="num"></th></tr></thead>
                <tbody>
                <?php foreach ($toSchedule as $o): ?>
                    <tr>
                        <td><a class="row-link" href="<?= e(admin_url('orders/view', ['id' => $o['id']]) . '#when') ?>"><?= e($o['order_number']) ?></a><div class="muted"><?= e($o['customer_name']) ?></div></td>
                        <td><strong><?= e(date('D j M', strtotime($o['pickup_date']))) ?></strong><div class="muted"><?= e(delivery_window_label($o['delivery_window'] ?? null)) ?></div></td>
                        <td><?= e((string) $o['delivery_suburb']) ?> <?= e((string) $o['delivery_postcode']) ?></td>
                        <td class="num"><a class="btn btn--primary btn--sm" href="<?= e(admin_url('orders/view', ['id' => $o['id']]) . '#when') ?>">Set time</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </section>
        <?php endif; ?>

        <?php if ($canOrders): ?>
        <section class="card">
            <div class="card__head"><h2>Today</h2><a href="<?= e(admin_url('pickups')) ?>">Full schedule</a></div>
            <?php if (!$todayOrders): ?>
                <div class="empty"><?= icon('calendar', '', 32) ?><p>No pickups or deliveries today.</p></div>
            <?php else: ?>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Time</th><th>Customer</th><th>Cakes</th><th>Payment</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($todayOrders as $o): ?>
                        <tr>
                            <td><strong><?= e(order_time_label($o)) ?></strong><div class="muted"><?= is_delivery($o) ? 'Delivery' : 'Pickup' ?></div></td>
                            <td><a class="row-link" href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>"><?= e($o['customer_name']) ?></a><div class="muted"><?= e($o['order_number']) ?></div></td>
                            <td class="muted"><?= e((string) $o['summary']) ?></td>
                            <td><?= status_badge($o['payment_status'], PAYMENT_STATUSES[$o['payment_status']]) ?></td>
                            <td><?= status_badge($o['order_status'], order_status_label($o)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($chart !== null): ?>
        <section class="card">
            <div class="card__head"><h2>Last 14 days</h2><a href="<?= e(admin_url('reports')) ?>">Sales reports</a></div>
            <div class="card__body">
                <?php $max = max(1, ...array_map(static fn ($d) => $d['total'], $chart)); ?>
                <div class="bar-chart bar-chart--small">
                    <?php foreach ($chart as $d): ?>
                        <div class="bar-chart__col" title="<?= e(date('D j M', strtotime($d['day'])) . ': ' . money($d['total']) . ', ' . $d['orders'] . ' orders') ?>">
                            <div class="bar-chart__bar" style="height:<?= round($d['total'] / $max * 100, 1) ?>%"><span style="height:<?= $d['total'] > 0 ? round($d['paid'] / $d['total'] * 100, 1) : 0 ?>%"></span></div>
                            <small><?= e(date('D', strtotime($d['day']))[0] . ' ' . date('j', strtotime($d['day']))) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($canOrders): ?>
        <section class="card">
            <div class="card__head"><h2>Recent orders</h2><a href="<?= e(admin_url('orders')) ?>">All orders</a></div>
            <?php if (!$recent): ?>
                <div class="empty"><?= icon('bag', '', 32) ?><p>Orders will appear here as soon as customers start ordering.</p></div>
            <?php else: ?>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Order</th><th>Customer</th><th>When</th><th>Status</th><th class="num">Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $o): ?>
                        <tr>
                            <td><a class="row-link" href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>"><?= e($o['order_number']) ?></a><div class="muted"><?= e(time_ago($o['created_at'])) ?></div></td>
                            <td><?= e($o['customer_name']) ?></td>
                            <td><?= e(date('D j M', strtotime($o['pickup_date']))) ?>, <?= e(order_time_label($o)) ?></td>
                            <td><?= status_badge($o['order_status'], order_status_label($o)) ?></td>
                            <td class="num"><strong><?= e(money($o['total'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </div>

    <div class="stack">
        <?php if ($canOrders): ?>
        <section class="card">
            <div class="card__head"><h2>Payments to check</h2></div>
            <div class="card__body">
                <?php if (!$toVerify): ?><p class="hint">All payment screenshots have been checked.</p><?php endif; ?>
                <ul class="mini-list">
                <?php foreach ($toVerify as $o): ?>
                    <li><a href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>"><strong><?= e($o['order_number']) ?></strong><span><?= e($o['customer_name']) ?> &middot; <?= e(payment_method_label($o['payment_method'])) ?></span></a><strong><?= e(money($o['total'])) ?></strong></li>
                <?php endforeach; ?>
                </ul>
            </div>
        </section>
        <?php endif; ?>
        <?php if ($threads !== null): ?>
        <section class="card">
            <div class="card__head"><h2>Unread messages</h2><a href="<?= e(admin_url('messages')) ?>">Inbox</a></div>
            <div class="card__body">
                <?php if (!$threads): ?><p class="hint">You are all caught up.</p><?php endif; ?>
                <ul class="mini-list">
                <?php foreach ($threads as $t): ?>
                    <li><a href="<?= e(admin_url('messages/view', ['id' => $t['id']])) ?>"><strong><?= e($t['customer_name']) ?></strong><span><?= e(mb_strimwidth((string) $t['last_body'], 0, 60, '...')) ?></span></a><span class="muted"><?= e(time_ago($t['last_message_at'])) ?></span></li>
                <?php endforeach; ?>
                </ul>
            </div>
        </section>
        <?php endif; ?>
        <?php if ($requests !== null): ?>
        <section class="card">
            <div class="card__head"><h2>New custom requests</h2><a href="<?= e(admin_url('custom-requests')) ?>">View all</a></div>
            <div class="card__body">
                <?php if (!$requests): ?><p class="hint">No new requests.</p><?php endif; ?>
                <ul class="mini-list">
                <?php foreach ($requests as $r): ?>
                    <li><a href="<?= e(admin_url('custom-requests/view', ['id' => $r['id']])) ?>"><strong><?= e($r['name']) ?></strong><span><?= e($r['occasion']) ?> &middot; <?= e((string) $r['servings']) ?></span></a><span class="muted"><?= e(date('D j M', strtotime($r['event_date']))) ?></span></li>
                <?php endforeach; ?>
                </ul>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>
</div>
