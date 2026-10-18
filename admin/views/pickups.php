<?php /** @var string $start @var string $end @var int $days @var array $byDay */ ?>
<div class="page-head">
    <div><h1>Pickup schedule</h1><p><?= e(date('D j M', strtotime($start))) ?> to <?= e(date('D j M Y', strtotime($end))) ?></p></div>
    <div class="page-head__actions">
        <a class="btn btn--ghost" href="<?= e(admin_url('pickups', ['date' => date('Y-m-d', strtotime($start . ' -' . $days . ' days')), 'days' => $days])) ?>">Previous</a>
        <a class="btn btn--secondary" href="<?= e(admin_url('pickups')) ?>">Today</a>
        <a class="btn btn--ghost" href="<?= e(admin_url('pickups', ['date' => date('Y-m-d', strtotime($start . ' +' . $days . ' days')), 'days' => $days])) ?>">Next</a>
        <button class="btn btn--primary" type="button" onclick="window.print()">Print</button>
    </div>
</div>
<?php for ($i = 0; $i < $days; $i++): $day = date('Y-m-d', strtotime($start . " +{$i} days")); $list = $byDay[$day] ?? []; ?>
    <section class="card schedule-day">
        <div class="card__head"><h2><?= e(date('l j F', strtotime($day))) ?> <span><?= count($list) ?> order<?= count($list) === 1 ? '' : 's' ?></span></h2></div>
        <?php if (!$list): ?>
            <div class="card__body"><p class="hint">No pickups.</p></div>
        <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Time</th><th>Order</th><th>Cakes</th><th>Customer</th><th>Payment</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($list as $o): ?>
                    <tr>
                        <td><strong><?= e(format_time($o['pickup_time'])) ?></strong><?= $o['fulfilment'] === 'delivery' ? '<div class="muted">Delivery</div>' : '' ?></td>
                        <td><a class="row-link" href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>"><?= e($o['order_number']) ?></a></td>
                        <td><?= e((string) $o['summary']) ?></td>
                        <td><?= e($o['customer_name']) ?><div class="muted"><?= e($o['customer_phone']) ?></div></td>
                        <td><?= status_badge($o['payment_status'], PAYMENT_STATUSES[$o['payment_status']]) ?></td>
                        <td><?= status_badge($o['order_status'], ORDER_STATUSES[$o['order_status']]) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </section>
<?php endfor; ?>
