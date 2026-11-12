<?php
/**
 * One order in the Orders table: number and when it was placed, customer and cakes, pickup,
 * status, payment, total and the next step. The whole row opens the order.
 *
 * @var array $o order row with a "summary" of its cakes
 */
$next = order_next_step($o);
$balance = order_balance($o);
$delivery = is_delivery($o);
$closed = in_array($o['order_status'], ['completed', 'cancelled'], true);
$overdue = !$closed && $o['pickup_date'] < date('Y-m-d');
$noTime = $delivery && !$o['pickup_time'] && !$closed;
$diff = (int) round((strtotime($o['pickup_date']) - strtotime(date('Y-m-d'))) / 86400);
$day = match ($diff) { 0 => 'Today', 1 => 'Tomorrow', -1 => 'Yesterday', default => date('D j M', strtotime($o['pickup_date'])) };
$time = $o['pickup_time'] ? format_time($o['pickup_time']) : '';
[$payClass, $payLabel] = match (true) {
    $o['order_status'] === 'cancelled'                 => ['muted', PAYMENT_STATUSES[$o['payment_status']] ?? ''],
    $o['payment_status'] === 'awaiting_verification'   => ['check', 'Check payment'],
    $balance < -0.004                                  => ['due', money(-$balance) . ' to refund'],
    $balance <= 0.004                                  => ['paid', 'Paid'],
    order_net_paid($o) > 0                             => ['due', money($balance) . ' owing'],
    default                                            => ['due', 'Not paid'],
};
$url = admin_url('orders/view', ['id' => $o['id']]);
?>
<div class="otr otr--<?= e($o['order_status']) ?><?= $overdue ? ' is-overdue' : '' ?>">
    <span class="otr__placed">
        <strong><?php if ($o['order_status'] === 'new'): ?><i class="otr__dot" aria-label="New"></i><?php endif; ?><?= e($o['order_number']) ?></strong>
        <small title="<?= e(date('j M Y, g:ia', strtotime($o['created_at']))) ?>"><?= e(time_ago($o['created_at'])) ?></small>
    </span>
    <a class="otr__who" href="<?= e($url) ?>">
        <strong><?= e($o['customer_name']) ?></strong>
        <span><?= e((string) ($o['summary'] ?: 'No items')) ?></span>
    </a>
    <span class="otr__when">
        <strong><?= e($day) ?></strong>
        <small class="<?= $noTime ? 'is-warn' : '' ?>"><?= e($noTime ? 'Delivery, set a time' : trim($time . ' ' . ($delivery ? 'delivery' . ($o['delivery_suburb'] ? ', ' . $o['delivery_suburb'] : '') : 'pickup'))) ?></small>
        <?php $tags = branch_tag(isset($o['branch_id']) ? (int) $o['branch_id'] : null) . (($o['channel'] ?? 'online') !== 'online' ? '<span class="tag">' . e(ORDER_CHANNELS[$o['channel']] ?? $o['channel']) . '</span>' : ''); ?>
        <?php if ($tags !== ''): ?><span class="otr__tags"><?= $tags ?></span><?php endif; ?>
    </span>
    <span class="otr__status">
        <?= status_badge($o['order_status'], order_status_label($o)) ?>
        <?php if ($overdue): ?><small class="is-warn">Not marked done</small><?php endif; ?>
    </span>
    <span class="otr__pay otr__pay--<?= e($payClass) ?>"><?= e($payLabel) ?></span>
    <strong class="otr__total"><?= e(money((float) $o['total'])) ?></strong>
    <span class="otr__act">
        <?php if ($next): ?>
            <form method="post" action="<?= e(admin_url('orders/view')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="order_status" value="<?= e($next[0]) ?>">
                <input type="hidden" name="notify" value="1">
                <input type="hidden" name="back" value="<?= e(admin_back_path()) ?>">
                <button class="btn btn--sm <?= $o['order_status'] === 'new' ? 'btn--primary' : 'btn--secondary' ?>" type="submit" title="<?= valid_email((string) $o['customer_email']) ? 'Also emails the customer' : 'No email address on this order' ?>"><?= e($next[1]) ?></button>
            </form>
        <?php else: ?>
            <a class="btn btn--ghost btn--sm" href="<?= e($url) ?>" tabindex="-1">Open</a>
        <?php endif; ?>
    </span>
</div>
