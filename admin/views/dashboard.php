<?php /** @var array $stats @var array $counts @var array $todayOrders @var array $toSchedule @var array $toVerify @var array $recent @var ?array $requests @var ?array $threads @var ?array $chart @var bool $canOrders @var array $admin */
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

<?php
// A card's footer link: "See all 40 payments to check".
$more = static fn (int $shown, int $total, string $url, string $what): string => $total > $shown
    ? '<a class="card__more" href="' . e($url) . '">See all ' . $total . ' ' . e($what) . ' ' . icon('arrow', '', 14) . '</a>' : '';
?>
<div class="grid-main dash">
    <div class="stack">
        <?php if ($canOrders): ?>
        <section class="card">
            <div class="card__head"><h2>Today <span class="count"><?= $stats['due_today'] ?></span></h2><span class="hint"><?= $counts['today_done'] ?> of <?= $stats['due_today'] ?> done &middot; <a href="<?= e(admin_url('pickups')) ?>">Full schedule</a></span></div>
            <?php if (!$todayOrders): ?>
                <div class="empty empty--small"><?= icon('calendar', '', 28) ?><p>No pickups or deliveries today.</p></div>
            <?php else: ?>
                <div class="order-lines">
                    <?php foreach ($todayOrders as $o): ?><?php render_admin_partial('order-line', ['o' => $o, 'show' => 'time']); ?><?php endforeach; ?>
                </div>
                <?= $more(count($todayOrders), $stats['due_today'], admin_url('pickups'), 'for today') ?>
            <?php endif; ?>
        </section>

        <?php if ($toSchedule): ?>
        <section class="card card--attention">
            <div class="card__head"><h2>Deliveries to schedule <span class="count count--warn"><?= $counts['to_schedule'] ?></span></h2><span class="hint">Set a time and the customer is emailed</span></div>
            <ul class="schedule-list">
                <?php foreach ($toSchedule as $o): ?>
                    <li>
                        <span class="schedule-list__date"><strong><?= e(date('D j', strtotime($o['pickup_date']))) ?></strong><span><?= e(date('M', strtotime($o['pickup_date']))) ?></span></span>
                        <span class="schedule-list__main">
                            <a href="<?= e(admin_url('orders/view', ['id' => $o['id']]) . '#when') ?>"><?= e($o['customer_name']) ?></a>
                            <span><?= e(trim((string) $o['delivery_suburb'] . ' ' . (string) $o['delivery_postcode'])) ?> &middot; <?= e(delivery_window_label($o['delivery_window'] ?? null)) ?></span>
                        </span>
                        <a class="btn btn--primary btn--sm" href="<?= e(admin_url('orders/view', ['id' => $o['id']]) . '#when') ?>"><?= icon('clock', '', 15) ?> Set time</a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?= $more(count($toSchedule), $counts['to_schedule'], admin_url('orders', ['view' => 'action', 'fulfilment' => 'delivery']), 'deliveries to schedule') ?>
        </section>
        <?php endif; ?>
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
            <div class="card__head"><h2>Latest orders</h2><a href="<?= e(admin_url('orders', ['view' => 'all'])) ?>">All orders</a></div>
            <?php if (!$recent): ?>
                <div class="empty empty--small"><?= icon('bag', '', 28) ?><p>Orders will appear here as soon as customers start ordering.</p></div>
            <?php else: ?>
                <div class="order-lines">
                    <?php foreach ($recent as $o): ?><?php render_admin_partial('order-line', ['o' => $o, 'show' => 'date']); ?><?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </div>

    <div class="stack">
        <?php if ($canOrders): ?>
        <section class="card">
            <div class="card__head"><h2>Payments to check <?php if ($stats['to_verify']): ?><span class="count count--warn"><?= $stats['to_verify'] ?></span><?php endif; ?></h2></div>
            <div class="card__body">
                <?php if (!$toVerify): ?><p class="hint">All payment screenshots have been checked.</p><?php endif; ?>
                <ul class="mini-list">
                <?php foreach ($toVerify as $o): ?>
                    <li><a href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>"><strong><?= e($o['customer_name']) ?></strong><span><?= e($o['order_number']) ?> &middot; <?= e(payment_method_label($o['payment_method'])) ?> &middot; <?= e(date('D j M', strtotime($o['pickup_date']))) ?></span></a><strong><?= e(money($o['total'])) ?></strong></li>
                <?php endforeach; ?>
                </ul>
            </div>
            <?= $more(count($toVerify), $stats['to_verify'], admin_url('orders', ['view' => 'all', 'payment' => 'awaiting_verification']), 'payments to check') ?>
        </section>
        <?php endif; ?>
        <?php if ($threads !== null): ?>
        <section class="card">
            <div class="card__head"><h2>Unread messages <?php if ($counts['unread']): ?><span class="count count--warn"><?= $counts['unread'] ?></span><?php endif; ?></h2><a href="<?= e(admin_url('messages')) ?>">Messages</a></div>
            <div class="card__body">
                <?php if (!$threads): ?><p class="hint">You are all caught up.</p><?php endif; ?>
                <ul class="mini-list">
                <?php foreach ($threads as $t): ?>
                    <li><a href="<?= e(admin_url('messages/view', ['id' => $t['id']])) ?>"><strong><?= e($t['customer_name']) ?></strong><span><?= e((string) $t['last_body']) ?></span></a><span class="muted"><?= e(time_ago($t['last_message_at'])) ?></span></li>
                <?php endforeach; ?>
                </ul>
            </div>
            <?= $more(count($threads), $counts['unread'], admin_url('messages', ['filter' => 'unread']), 'unread messages') ?>
        </section>
        <?php endif; ?>
        <?php if ($requests !== null): ?>
        <section class="card">
            <div class="card__head"><h2>New custom requests <?php if ($counts['requests']): ?><span class="count"><?= $counts['requests'] ?></span><?php endif; ?></h2><a href="<?= e(admin_url('custom-requests')) ?>">Requests</a></div>
            <div class="card__body">
                <?php if (!$requests): ?><p class="hint">No new requests.</p><?php endif; ?>
                <ul class="mini-list">
                <?php foreach ($requests as $r): ?>
                    <li><a href="<?= e(admin_url('custom-requests/view', ['id' => $r['id']])) ?>"><strong><?= e($r['name']) ?></strong><span><?= e($r['occasion']) ?> &middot; serves <?= e((string) $r['servings']) ?></span></a><span class="muted"><?= e(date('D j M', strtotime($r['event_date']))) ?></span></li>
                <?php endforeach; ?>
                </ul>
            </div>
            <?= $more(count($requests), $counts['requests'], admin_url('custom-requests', ['status' => 'new']), 'new requests') ?>
        </section>
        <?php endif; ?>
    </div>
</div>
</div>
