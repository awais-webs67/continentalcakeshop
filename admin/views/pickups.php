<?php /** @var string $start @var string $end @var int $days @var array $byDay */ ?>
<div class="page-head">
    <div><h1>Pickups & deliveries</h1><p><?= e(date('D j M', strtotime($start))) ?> to <?= e(date('D j M Y', strtotime($end))) ?></p></div>
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
            <div class="card__body"><p class="hint">Nothing booked.</p></div>
        <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Time</th><th>Order</th><th>Cakes</th><th>Customer</th><th>Payment</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($list as $o): ?>
                    <tr>
                        <td><strong><?= e(order_time_label($o)) ?></strong><?= is_delivery($o) ? '<div class="muted">Delivery to ' . e((string) $o['delivery_suburb']) . '</div>' : '<div class="muted">Pickup</div>' ?></td>
                        <td><a class="row-link" href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>"><?= e($o['order_number']) ?></a></td>
                        <td><?= e((string) $o['summary']) ?></td>
                        <td><?= e($o['customer_name']) ?><div class="muted"><?= e($o['customer_phone']) ?></div></td>
                        <td><?= status_badge($o['payment_status'], PAYMENT_STATUSES[$o['payment_status']]) ?></td>
                        <td><?= status_badge($o['order_status'], order_status_label($o)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </section>
<?php endfor; ?>
