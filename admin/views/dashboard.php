<?php /** @var int $days @var array $glance @var ?array $sales @var ?array $prev @var ?array $byDay @var ?array $top @var ?array $methods @var ?array $shops @var ?array $statuses @var bool $canOrders @var bool $canReports @var array $admin */
$hour = (int) date('G');
$short = static fn (float $n): string => $n >= 1000 ? '$' . rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k' : '$' . number_format($n, 0);
// Change against the same number of days before: [text, up?] or null when there is nothing to compare.
$change = static function (float $now, float $before): ?array {
    if ($before <= 0) {
        return null;
    }
    $pct = (int) round(($now - $before) / $before * 100);
    return [($pct >= 0 ? '+' : '') . $pct . '%', $pct >= 0];
};
$bar = static fn (float $value, float $max): string => 'width:' . ($max > 0 && $value > 0 ? max(1.5, round($value / $max * 100, 1)) : 0) . '%';
?>
<div class="page-head page-head--compact">
    <div>
        <h1>Dashboard</h1>
        <p>Good <?= $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening') ?>, <?= e(strtok($admin['name'], ' ')) ?> &middot; <?= e(date('l j F')) ?></p>
    </div>
    <div class="page-head__actions">
        <?php if ($canReports): ?>
            <nav class="seg" aria-label="Period">
                <?php foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days'] as $n => $label): ?>
                    <a href="<?= e(admin_url('', $n === 30 ? [] : ['days' => $n])) ?>" class="<?= $days === $n ? 'is-active' : '' ?>"<?= $days === $n ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
        <?php if ($canOrders): ?><a class="btn btn--primary" href="<?= e(admin_url('orders/new')) ?>"><?= icon('plus', '', 17) ?> New order</a><?php endif; ?>
    </div>
</div>

<div data-live="dashboard">
<?php if ($admin['role'] === 'owner' && !available_payment_methods()): ?>
    <div class="alert alert--error" style="margin-bottom:16px"><strong>Customers cannot pay at checkout yet.</strong> Add your PayID, bank details or Stripe key and switch the method on in <a href="<?= e(admin_url('settings', ['tab' => 'payments'])) ?>">Settings &gt; Payments</a>.</div>
<?php endif; ?>

<?php if ($glance): ?>
<section class="glance" aria-label="Waiting now">
    <?php foreach ($glance as [$label, $n, $sub, $url, $urgent]): ?>
        <a class="glance__item<?= $urgent && $n ? ' is-waiting' : '' ?>" href="<?= e($url) ?>">
            <span><?= e($label) ?></span>
            <strong><?= $n ?></strong>
            <small><?= e($sub) ?></small>
        </a>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if ($canReports): ?>
    <?php
    $kpis = [
        ['Sales', money((float) $sales['total']), $change((float) $sales['total'], (float) $prev['total'])],
        ['Orders', (string) (int) $sales['orders'], $change((float) $sales['orders'], (float) $prev['orders'])],
        ['Average order', money((float) $sales['average']), $change((float) $sales['average'], (float) $prev['average'])],
        ['Money received', money((float) $sales['paid']), $change((float) $sales['paid'], (float) $prev['paid'])],
    ];
    $chartMax = max(1.0, ...array_map(static fn ($d) => (float) $d['total'], $byDay));
    $scale = (float) (10 ** max(1, (int) floor(log10($chartMax))));
    // A round number above the highest day whose half is round too, for the grid labels.
    $chartTop = max($scale, ceil($chartMax / ($scale / 2)) * ($scale / 2));
    if (fmod($chartTop / 2, $scale / 2) > 0) {
        $chartTop += $scale / 2;
    }
    $labelEvery = $days > 30 ? 14 : ($days > 7 ? 5 : 1);
    ?>
    <section class="kpi-cards" aria-label="Last <?= $days ?> days">
        <?php foreach ($kpis as [$label, $value, $diff]): ?>
            <div class="kpi-card">
                <span><?= e($label) ?></span>
                <strong><?= e($value) ?></strong>
                <?php if ($diff): ?>
                    <small class="trend <?= $diff[1] ? 'trend--up' : 'trend--down' ?>"><b><?= $diff[1] ? '&#9650;' : '&#9660;' ?> <?= e($diff[0]) ?></b> vs previous <?= $days ?> days</small>
                <?php else: ?>
                    <small class="trend">Last <?= $days ?> days</small>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>

    <div class="dash-grid">
        <section class="card dash-chart">
            <div class="card__head"><h2>Sales by day</h2><a href="<?= e(admin_url('reports')) ?>">Reports</a></div>
            <div class="card__body">
                <?php if ((float) $sales['total'] <= 0): ?>
                    <div class="empty empty--small"><?= icon('chart', '', 28) ?><p>No sales in the last <?= $days ?> days yet.</p></div>
                <?php else: ?>
                <div class="vchart" role="img" aria-label="Sales for each of the last <?= $days ?> days, highest <?= e(money($chartMax)) ?>">
                    <div class="vchart__grid" aria-hidden="true">
                        <span data-v="<?= e($short($chartTop)) ?>"></span><span data-v="<?= e($short($chartTop / 2)) ?>"></span><span data-v="$0"></span>
                    </div>
                    <div class="vchart__bars vchart__bars--<?= $days ?>">
                        <?php foreach ($byDay as $i => $d): $ts = strtotime($d['day']); ?>
                            <div class="vchart__col" tabindex="0">
                                <div class="vchart__bar" style="height:<?= round((float) $d['total'] / $chartTop * 100, 2) ?>%"></div>
                                <div class="vchart__tip" role="tooltip"><strong><?= e(money((float) $d['total'])) ?></strong><?= e(date('D j M', $ts)) ?> &middot; <?= (int) $d['orders'] ?> order<?= (int) $d['orders'] === 1 ? '' : 's' ?></div>
                                <?php if (($days - 1 - $i) % $labelEvery === 0): ?><small><?= e($days > 7 ? date('j M', $ts) : date('D', $ts)) ?></small><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><h2>Orders by status</h2><span class="hint"><?= $days ?> days</span></div>
            <div class="card__body">
                <?php $statusMax = max(1, ...array_map('intval', array_values($statuses ?: [0]))); ?>
                <ul class="hbars">
                    <?php foreach (ORDER_STATUSES as $key => $label): $n = (int) ($statuses[$key] ?? 0); ?>
                        <li><span class="hbars__label"><?= e($label) ?></span><span class="hbars__track"><i style="<?= $bar($n, $statusMax) ?>"></i></span><strong><?= $n ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    </div>

    <div class="dash-grid dash-grid--even">
        <section class="card">
            <div class="card__head"><h2>Top cakes</h2><span class="hint">By sales, <?= $days ?> days</span></div>
            <?php if (!$top): ?>
                <div class="empty empty--small"><?= icon('cake', '', 28) ?><p>No cakes sold yet.</p></div>
            <?php else: $topMax = max(1.0, ...array_map(static fn ($t) => (float) $t['revenue'], $top)); ?>
                <ol class="toplist">
                    <?php foreach ($top as $i => $t): ?>
                        <li>
                            <span class="toplist__rank"><?= $i + 1 ?></span>
                            <span class="toplist__main"><strong><?= e($t['product_name']) ?></strong><span class="hbars__track"><i style="<?= $bar((float) $t['revenue'], $topMax) ?>"></i></span></span>
                            <span class="toplist__num"><strong><?= e(money((float) $t['revenue'])) ?></strong><small><?= (int) $t['qty'] ?> sold</small></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__head"><h2>How customers order</h2><span class="hint"><?= $days ?> days</span></div>
            <div class="card__body split-stack">
                <?php
                $deliveries = (int) $sales['deliveries'];
                $pickups = max(0, (int) $sales['orders'] - $deliveries);
                $orderMax = max(1, $pickups, $deliveries);
                ?>
                <div>
                    <p class="split-title">Pickup or delivery</p>
                    <ul class="hbars">
                        <li><span class="hbars__label">Pickup</span><span class="hbars__track"><i style="<?= $bar($pickups, $orderMax) ?>"></i></span><strong><?= $pickups ?></strong></li>
                        <li><span class="hbars__label">Delivery</span><span class="hbars__track"><i style="<?= $bar($deliveries, $orderMax) ?>"></i></span><strong><?= $deliveries ?></strong></li>
                    </ul>
                </div>
                <?php if ($methods): $methodMax = max(1.0, ...array_map(static fn ($m) => (float) $m['total'], $methods)); ?>
                <div>
                    <p class="split-title">Payment</p>
                    <ul class="hbars">
                        <?php foreach ($methods as $m): ?>
                            <li><span class="hbars__label"><?= e(payment_method_label((string) $m['payment_method'])) ?></span><span class="hbars__track"><i style="<?= $bar((float) $m['total'], $methodMax) ?>"></i></span><strong><?= e($short((float) $m['total'])) ?></strong></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if ($shops): $shopMax = max(1.0, ...array_map(static fn ($s) => (float) $s['total'], $shops)); ?>
                <div>
                    <p class="split-title">Shops</p>
                    <ul class="hbars">
                        <?php foreach ($shops as $s): ?>
                            <li><span class="hbars__label"><?= e(branch_name((int) $s['branch_id'])) ?></span><span class="hbars__track"><i style="<?= $bar((float) $s['total'], $shopMax) ?>"></i></span><strong><?= e($short((float) $s['total'])) ?></strong></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
<?php endif; ?>
</div>
