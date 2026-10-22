<?php
/** @var string $tab @var string $type @var string $on @var bool $openOnly @var array $counts @var array $byDay @var ?array $stats @var string $today */
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$link = static fn (array $q): string => admin_url('pickups', array_filter($q + ['tab' => $tab, 'type' => $type], static fn ($v) => $v !== '' && $v !== null && $v !== 'today'));
$dayLabel = static function (string $day) use ($today): string {
    $diff = (int) round((strtotime($day) - strtotime($today)) / 86400);
    return match (true) {
        $diff === 0  => 'Today',
        $diff === 1  => 'Tomorrow',
        $diff === -1 => 'Yesterday',
        $diff > 1    => 'In ' . $diff . ' days',
        default      => abs($diff) . ' days ago',
    };
};
?>
<div class="page-head">
    <div>
        <h1>Pickups &amp; deliveries</h1>
        <p><?= e(date('l j F Y')) ?> &middot; <?= e(today_hours_label()) ?></p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--primary" href="<?= e(admin_url('orders/new', ['date' => $tab === 'advance' && $on !== '' ? $on : $tomorrow])) ?>"><?= icon('plus', '', 18) ?> Add advance order</a>
        <button class="btn btn--secondary" type="button" onclick="window.print()"><?= icon('download', '', 18) ?> Print</button>
    </div>
</div>

<nav class="seg-tabs" data-live="tabs" aria-label="Which orders">
    <a href="<?= e(admin_url('pickups')) ?>" class="<?= $tab === 'today' ? 'is-active' : '' ?>"><?= icon('calendar', '', 17) ?> Today <span><?= $counts['today'] ?></span></a>
    <a href="<?= e(admin_url('pickups', ['tab' => 'advance'])) ?>" class="<?= $tab === 'advance' ? 'is-active' : '' ?>"><?= icon('clock', '', 17) ?> Advance<span class="hide-sm">&nbsp;orders</span> <span><?= $counts['advance'] ?></span></a>
    <a href="<?= e(admin_url('pickups', ['tab' => 'past'])) ?>" class="<?= $tab === 'past' ? 'is-active' : '' ?>"><?= icon('check', '', 17) ?> Past<?php if ($counts['past']): ?> <span class="is-warn" title="Not marked done"><?= $counts['past'] ?></span><?php endif; ?></a>
</nav>

<div class="schedule-tools">
    <div class="pill-filter" role="group" aria-label="Pickup or delivery">
        <?php foreach (['' => 'All', 'pickup' => 'Pickups', 'delivery' => 'Deliveries'] as $key => $label): ?>
            <a href="<?= e($link(['type' => $key])) ?>" class="<?= $type === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <?php if ($tab === 'advance'): ?>
        <form class="schedule-tools__date" method="get" action="<?= e(admin_url('pickups')) ?>">
            <input type="hidden" name="tab" value="advance">
            <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif; ?>
            <label><span class="sr-only">Show one day</span><input class="input input--sm" type="date" name="on" value="<?= e($on) ?>" min="<?= e($tomorrow) ?>" data-auto-submit></label>
            <?php if ($on !== ''): ?><a class="btn btn--ghost btn--sm" href="<?= e($link(['on' => null])) ?>">All days</a><?php endif; ?>
        </form>
    <?php elseif ($tab === 'past'): ?>
        <a class="check-pill<?= $openOnly ? ' is-active' : '' ?>" href="<?= e($link(['open' => $openOnly ? null : '1'])) ?>"><?= icon('check', '', 15) ?> Only not done</a>
    <?php endif; ?>
</div>

<div data-live="schedule">
<?php if ($tab === 'today' && $stats): ?>
    <div class="mini-stats">
        <div><strong><?= $stats['total'] ?></strong><span>Orders today</span></div>
        <div><strong><?= $stats['pickup'] ?></strong><span>Pickups</span></div>
        <div><strong><?= $stats['delivery'] ?></strong><span>Deliveries</span></div>
        <div><strong><?= $stats['done'] ?>/<?= $stats['total'] ?></strong><span>Done</span></div>
        <div class="<?= $stats['to_pay'] ? 'is-warn' : '' ?>"><strong><?= $stats['to_pay'] ?></strong><span>Still to pay</span></div>
    </div>
    <?php if ($counts['past']): ?>
        <a class="alert alert--warning alert--link" href="<?= e(admin_url('pickups', ['tab' => 'past', 'open' => '1'])) ?>"><?= icon('info', '', 18) ?> <?= $counts['past'] ?> order<?= $counts['past'] === 1 ? '' : 's' ?> from earlier days <?= $counts['past'] === 1 ? 'is' : 'are' ?> not marked collected or delivered. <strong>Check them</strong></a>
    <?php endif; ?>
<?php endif; ?>

<?php if (!array_filter($byDay)): ?>
    <section class="card">
        <div class="empty">
            <?= icon('calendar', '', 32) ?>
            <p><?= match ($tab) {
                'today'   => 'No pickups or deliveries today' . ($type !== '' ? ' of this type' : '') . '.',
                'advance' => $on !== '' ? 'Nothing booked on ' . e(date('l j F', strtotime($on))) . '.' : 'No advance orders yet.',
                default   => $openOnly ? 'Every past order is marked done.' : 'No orders in the last 30 days.',
            } ?></p>
            <?php if ($tab !== 'past'): ?><a class="btn btn--secondary btn--sm" href="<?= e(admin_url('orders/new', ['date' => $tab === 'today' ? $today : ($on ?: $tomorrow)])) ?>"><?= icon('plus', '', 16) ?> Add an order</a><?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php foreach ($byDay as $day => $list): if (!$list) continue; ?>
    <section class="card schedule-day">
        <div class="schedule-day__head">
            <div>
                <h2><?= e(date('l j F', strtotime($day))) ?></h2>
                <span><?= e($dayLabel($day)) ?> &middot; <?= count($list) ?> order<?= count($list) === 1 ? '' : 's' ?></span>
            </div>
            <?php if ($day >= $today): ?><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('orders/new', ['date' => $day])) ?>"><?= icon('plus', '', 15) ?> Add</a><?php endif; ?>
        </div>
        <div class="order-list">
            <?php foreach ($list as $o): ?>
                <?php render_admin_partial('order-row', ['o' => $o, 'show' => 'time']); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
</div>
