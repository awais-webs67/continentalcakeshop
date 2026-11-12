<?php /** @var array $stats @var array $counts @var array $todayOrders @var array $toSchedule @var array $toVerify @var array $recent @var ?array $requests @var ?array $threads @var ?array $chart @var bool $canOrders @var array $admin */
$hour = (int) date('G');
?>
<div class="page-head page-head--compact">
    <div>
        <h1>Good <?= $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening') ?>, <?= e(strtok($admin['name'], ' ')) ?></h1>
        <p><?= e(date('l j F')) ?> &middot; <?= e(today_hours_label()) ?></p>
    </div>
    <div class="page-head__actions">
        <?php if ($canOrders): ?><a class="btn btn--secondary" href="<?= e(admin_url('pickups')) ?>"><?= icon('calendar', '', 17) ?> Pickups &amp; deliveries</a><?php endif; ?>
        <?php if ($canOrders): ?><a class="btn btn--primary" href="<?= e(admin_url('orders/new')) ?>"><?= icon('plus', '', 18) ?> New order</a><?php elseif (admin_can('products', $admin)): ?><a class="btn btn--primary" href="<?= e(admin_url('products/edit')) ?>"><?= icon('plus', '', 18) ?> Add product</a><?php endif; ?>
    </div>
</div>

<div data-live="dashboard">
<?php if ($admin['role'] === 'owner' && !available_payment_methods()): ?>
    <div class="alert alert--error" style="margin-bottom:16px"><strong>Customers cannot pay at checkout yet.</strong> Add your PayID, bank details or Stripe key and switch the method on in <a href="<?= e(admin_url('settings', ['tab' => 'payments'])) ?>">Settings &gt; Payments</a>.</div>
<?php endif; ?>

<?php if ($canOrders): ?>
<div class="kpis">
    <a class="kpi" href="<?= e(admin_url('orders', ['view' => 'today'])) ?>"><span>Today</span><strong><?= $stats['due_today'] ?></strong><small><?= $counts['today_done'] ?> done</small></a>
    <a class="kpi<?= $stats['new_orders'] ? ' kpi--alert' : '' ?>" href="<?= e(admin_url('orders', ['status' => 'new'])) ?>"><span>To confirm</span><strong><?= $stats['new_orders'] ?></strong><small>New orders</small></a>
    <a class="kpi<?= $stats['to_verify'] ? ' kpi--alert' : '' ?>" href="<?= e(admin_url('orders', ['view' => 'all', 'payment' => 'awaiting_verification'])) ?>"><span>Payments to check</span><strong><?= $stats['to_verify'] ?></strong><small>Screenshots</small></a>
    <a class="kpi" href="<?= e(admin_can('reports', $admin) ? admin_url('reports') : admin_url('orders')) ?>"><span><?= e(date('F')) ?></span><strong><?= e(money($stats['month_sales'])) ?></strong><small><?= $stats['month_orders'] ?> orders</small></a>
</div>
<?php endif; ?>

<?php
// A card's footer link: "See all 40 for today".
$more = static fn (int $shown, int $total, string $url, string $what): string => $total > $shown
    ? '<a class="card__more" href="' . e($url) . '">See all ' . $total . ' ' . e($what) . ' ' . icon('arrow', '', 14) . '</a>' : '';

// Everything that waits on staff, one line each: how many, and the first one or two.
$waiting = [];
if ($canOrders) {
    $waiting[] = [$stats['new_orders'], 'New orders to confirm', $newest ? 'Latest: ' . $newest['customer_name'] . ', ' . time_ago($newest['created_at']) : '', admin_url('orders', ['status' => 'new']), 'blue'];
    $waiting[] = [$stats['to_verify'], 'Payments to check', implode(' · ', array_map(static fn ($o) => $o['customer_name'] . ' ' . money((float) $o['total']), $toVerify)), admin_url('orders', ['view' => 'all', 'payment' => 'awaiting_verification']), 'amber'];
    $waiting[] = [$counts['to_schedule'], 'Deliveries without a time', $toSchedule ? $toSchedule[0]['customer_name'] . ' · ' . date('D j M', strtotime($toSchedule[0]['pickup_date'])) : '', admin_url('orders', ['view' => 'action', 'fulfilment' => 'delivery']), 'red'];
}
if ($threads !== null) {
    $waiting[] = [$counts['unread'], 'Unread messages', $threads ? $threads[0]['customer_name'] . ': ' . $threads[0]['last_body'] : '', admin_url('messages', ['filter' => 'unread']), 'brand'];
}
if ($requests !== null) {
    $waiting[] = [$counts['requests'], 'Custom cake requests', $requests ? $requests[0]['occasion'] . ' · serves ' . $requests[0]['servings'] . ' · ' . date('D j M', strtotime($requests[0]['event_date'])) : '', admin_url('custom-requests', ['status' => 'new']), 'purple'];
}
$waitingTotal = array_sum(array_column($waiting, 0));
$done = $counts['today_done'] ?? 0;
$due = $stats['due_today'] ?? 0;
?>
<div class="dash">
    <div class="stack">
        <?php if ($canOrders): ?>
        <section class="card dash__today">
            <div class="dash-today__head">
                <div class="card__head"><h2>Today</h2><span class="hint"><?= $done ?> of <?= $due ?> done</span></div>
                <?php if ($due): ?><div class="progress" role="progressbar" aria-label="Done today" aria-valuemin="0" aria-valuemax="<?= $due ?>" aria-valuenow="<?= $done ?>"><span class="progress__bar" style="width:<?= round($done / $due * 100, 1) ?>%"></span></div><?php endif; ?>
            </div>
            <?php if (!$todayOrders): ?>
                <div class="empty empty--small"><?= icon('calendar', '', 28) ?><p>No pickups or deliveries today.</p></div>
            <?php else: ?>
                <div class="order-lines">
                    <?php foreach ($todayOrders as $o): ?><?php render_admin_partial('order-line', ['o' => $o, 'show' => 'time', 'quick' => true]); ?><?php endforeach; ?>
                </div>
                <?= $more(count($todayOrders), $due, admin_url('pickups'), 'for today') ?>
            <?php endif; ?>
        </section>

        <section class="card dash__latest">
            <div class="card__head"><h2>Latest orders</h2><a href="<?= e(admin_url('orders')) ?>">All orders</a></div>
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
        <?php if ($waiting): ?>
        <section class="card dash__waiting">
            <div class="card__head"><h2>To do</h2></div>
            <?php if (!$waitingTotal): ?>
                <div class="empty empty--small"><?= icon('check', '', 28) ?><p>All caught up. Nothing is waiting.</p></div>
            <?php else: ?>
                <ul class="wait-list">
                    <?php foreach ($waiting as [$n, $label, $sub, $url, $tone]): if (!$n) continue; ?>
                        <li><a href="<?= e($url) ?>">
                            <span class="wait-list__n wait-list__n--<?= e($tone) ?>"><?= $n ?></span>
                            <span class="wait-list__text"><strong><?= e($label) ?></strong><?php if ($sub !== ''): ?><small><?= e($sub) ?></small><?php endif; ?></span>
                            <?= icon('arrow', '', 16) ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($chart !== null): ?>
        <section class="card dash__chart">
            <div class="card__head"><h2>Sales, last 14 days</h2><a href="<?= e(admin_url('reports')) ?>">Reports</a></div>
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
    </div>
</div>
</div>
