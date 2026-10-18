<?php /** @var string $from @var string $to @var array $summary @var array $days @var array $methods @var array $products @var array $categories */
$query = ['from' => $from, 'to' => $to];
// Long ranges are drawn per month so the bars stay readable.
$bars = $days;
if (count($days) > 62) {
    $months = [];
    foreach ($days as $d) {
        $key = substr($d['day'], 0, 7);
        $months[$key] ??= ['day' => $key . '-01', 'orders' => 0, 'total' => 0.0, 'paid' => 0.0, 'label' => date('M Y', strtotime($key . '-01'))];
        $months[$key]['orders'] += $d['orders'];
        $months[$key]['total'] += $d['total'];
        $months[$key]['paid'] += $d['paid'];
    }
    $bars = array_values($months);
}
$max = max(1, ...array_map(static fn ($b) => $b['total'], $bars ?: [['total' => 1]]));
$presets = ['today' => 'Today', 'yesterday' => 'Yesterday', 'week' => 'This week', 'month' => 'This month', 'last-month' => 'Last month', 'year' => 'This year'];
?>
<div class="page-head">
    <div><h1>Sales reports</h1><p><?= e(date('j M Y', strtotime($from))) ?> to <?= e(date('j M Y', strtotime($to))) ?> &middot; by the day orders were placed. Cancelled orders are not counted.</p></div>
    <div class="page-head__actions">
        <details class="row-menu">
            <summary class="btn btn--primary"><?= icon('download', '', 18) ?> Export to Excel (CSV)</summary>
            <div class="row-menu__panel export-menu">
                <a href="<?= e(admin_url('reports/export', $query + ['type' => 'orders'])) ?>">Orders <small>one row per order</small></a>
                <a href="<?= e(admin_url('reports/export', $query + ['type' => 'items'])) ?>">Order items <small>one row per cake</small></a>
                <a href="<?= e(admin_url('reports/export', $query + ['type' => 'daily'])) ?>">Daily sales</a>
                <a href="<?= e(admin_url('reports/export', $query + ['type' => 'products'])) ?>">Cakes sold</a>
            </div>
        </details>
    </div>
</div>

<section class="card">
    <form class="toolbar report-filter" method="get" action="<?= e(admin_url('reports')) ?>">
        <div class="preset-links">
            <?php foreach ($presets as $key => $label): ?>
                <a class="chip<?= ($_GET['range'] ?? (isset($_GET['from']) ? '' : 'month')) === $key ? ' is-active' : '' ?>" href="<?= e(admin_url('reports', ['range' => $key])) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
        <label class="hint">From <input class="input" type="date" name="from" value="<?= e($from) ?>"></label>
        <label class="hint">to <input class="input" type="date" name="to" value="<?= e($to) ?>"></label>
        <button class="btn btn--secondary" type="submit">Show</button>
    </form>
</section>

<div class="stats stats--report">
    <div class="stat stat--brand"><span class="stat__label"><?= icon('chart', '', 16) ?> Sales</span><span class="stat__value"><?= e(money($summary['total'])) ?></span><span class="stat__note"><?= (int) $summary['orders'] ?> orders &middot; average <?= e(money($summary['average'])) ?></span></div>
    <div class="stat"><span class="stat__label"><?= icon('check', '', 16) ?> Paid</span><span class="stat__value"><?= e(money($summary['paid'])) ?></span><span class="stat__note"><?= e(money($summary['unpaid'])) ?> waiting for payment</span></div>
    <div class="stat"><span class="stat__label"><?= icon('tag', '', 16) ?> Discounts</span><span class="stat__value"><?= e(money($summary['discounts'])) ?></span><span class="stat__note"><?= (int) $summary['coupon_orders'] ?> orders used a coupon</span></div>
    <div class="stat"><span class="stat__label"><?= icon('truck', '', 16) ?> Delivery fees</span><span class="stat__value"><?= e(money($summary['delivery'])) ?></span><span class="stat__note"><?= (int) $summary['deliveries'] ?> deliveries</span></div>
</div>

<div class="grid-main">
    <div class="stack">
        <section class="card">
            <div class="card__head"><h2>Sales by <?= count($days) > 62 ? 'month' : 'day' ?></h2><span class="hint">Dark bar = paid</span></div>
            <div class="card__body">
                <?php if (!(float) $summary['total']): ?>
                    <div class="empty"><?= icon('chart', '', 32) ?><p>No sales in these dates.</p></div>
                <?php else: ?>
                <div class="bar-chart" role="img" aria-label="Sales chart">
                    <?php $step = max(1, (int) ceil(count($bars) / 10)); foreach ($bars as $i => $b): ?>
                        <div class="bar-chart__col" title="<?= e(($b['label'] ?? date('D j M', strtotime($b['day']))) . ': ' . money($b['total']) . ', ' . $b['orders'] . ' orders') ?>">
                            <div class="bar-chart__bar" style="height:<?= round($b['total'] / $max * 100, 1) ?>%"><span style="height:<?= $b['total'] > 0 ? round($b['paid'] / $b['total'] * 100, 1) : 0 ?>%"></span></div>
                            <?php if ($i % $step === 0 || isset($b['label'])): ?><small><?= e($b['label'] ?? date('j M', strtotime($b['day']))) ?></small><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <section class="card">
            <div class="card__head"><h2>Best selling cakes</h2><a href="<?= e(admin_url('reports/export', $query + ['type' => 'products'])) ?>">Export all</a></div>
            <?php if (!$products): ?><div class="card__body"><p class="hint">No cakes sold in these dates.</p></div><?php else: ?>
            <div class="table-wrap"><table class="table table--stack">
                <thead><tr><th>Cake</th><th>Category</th><th class="num">Sold</th><th class="num">Revenue</th></tr></thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr><td data-label="Cake"><strong><?= e($p['product_name']) ?></strong></td><td data-label="Category" class="muted"><?= e($p['category']) ?></td><td data-label="Sold" class="num"><?= (int) $p['qty'] ?></td><td data-label="Revenue" class="num"><?= e(money($p['revenue'])) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </section>
    </div>
    <div class="stack">
        <section class="card">
            <div class="card__head"><h2>Summary</h2></div>
            <div class="card__body"><dl class="dl dl--money">
                <dt>Cakes (before discounts)</dt><dd><?= e(money($summary['gross'])) ?></dd>
                <dt>Discounts</dt><dd>-<?= e(money($summary['discounts'])) ?></dd>
                <dt>Delivery fees</dt><dd><?= e(money($summary['delivery'])) ?></dd>
                <dt><strong>Total sales</strong></dt><dd><strong><?= e(money($summary['total'])) ?></strong></dd>
                <dt>Refunded</dt><dd>-<?= e(money($summary['refunded'])) ?></dd>
                <dt><strong>Net sales</strong></dt><dd><strong><?= e(money($summary['net'])) ?></strong></dd>
                <?php if ($summary['gst'] !== null): ?><dt>GST included</dt><dd><?= e(money($summary['gst'])) ?></dd><?php endif; ?>
                <dt>Cancelled orders</dt><dd><?= (int) $summary['cancelled'] ?></dd>
            </dl></div>
        </section>
        <section class="card">
            <div class="card__head"><h2>By payment method</h2></div>
            <div class="card__body"><ul class="check-list">
                <?php foreach ($methods as $m): ?>
                    <li><span><strong><?= e(payment_method_label($m['payment_method'])) ?></strong><br><span class="hint"><?= (int) $m['orders'] ?> orders &middot; <?= e(money($m['paid'])) ?> paid</span></span><strong><?= e(money($m['total'])) ?></strong></li>
                <?php endforeach; ?>
                <?php if (!$methods): ?><li class="hint">No orders.</li><?php endif; ?>
            </ul></div>
        </section>
        <section class="card">
            <div class="card__head"><h2>By category</h2></div>
            <div class="card__body"><ul class="check-list">
                <?php foreach ($categories as $c): ?>
                    <li><span><?= e($c['category']) ?> <span class="hint">&middot; <?= (int) $c['qty'] ?> sold</span></span><strong><?= e(money($c['revenue'])) ?></strong></li>
                <?php endforeach; ?>
                <?php if (!$categories): ?><li class="hint">No orders.</li><?php endif; ?>
            </ul></div>
        </section>
    </div>
</div>
