<?php
/**
 * One order in a list: the whole card opens the order, with the next step one tap away.
 *
 * @var array  $o     order row with a "summary" of its cakes
 * @var string $show  'time' (schedule lists) or 'date' (order lists)
 */
$show ??= 'time';
$next = order_next_step($o);
$balance = order_balance($o);
$delivery = is_delivery($o);
$overdue = $o['pickup_date'] < date('Y-m-d') && !in_array($o['order_status'], ['completed', 'cancelled'], true);
?>
<article class="order-row order-row--<?= e($o['order_status']) ?><?= $overdue ? ' is-overdue' : '' ?>">
    <div class="order-row__when">
        <?php if ($show === 'date'): ?>
            <strong><?= e(date('D j M', strtotime($o['pickup_date']))) ?></strong>
            <span><?= e($o['pickup_time'] ? format_time($o['pickup_time']) : ($delivery ? 'Time to set' : '')) ?></span>
        <?php else: ?>
            <strong><?= e($o['pickup_time'] ? format_time($o['pickup_time']) : 'Time?') ?></strong>
            <span><?= $delivery ? 'Delivery' : 'Pickup' ?></span>
        <?php endif; ?>
    </div>
    <div class="order-row__main">
        <a class="order-row__link" href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>">
            <strong><?= e($o['customer_name']) ?></strong>
            <span class="order-row__number"><?= e($o['order_number']) ?></span>
        </a>
        <p class="order-row__items"><?= e((string) ($o['summary'] ?: 'No items')) ?></p>
        <div class="order-row__tags">
            <span class="tag tag--<?= $delivery ? 'delivery' : 'pickup' ?>"><?= icon($delivery ? 'truck' : 'store', '', 13) ?> <?= $delivery ? e('Delivery' . ($o['delivery_suburb'] ? ' · ' . $o['delivery_suburb'] : '')) : 'Pickup' ?></span>
            <?= status_badge($o['order_status'], order_status_label($o)) ?>
            <?= status_badge($o['payment_status'], PAYMENT_STATUSES[$o['payment_status']]) ?>
            <?php if (($o['channel'] ?? 'online') !== 'online'): ?><span class="tag"><?= e(ORDER_CHANNELS[$o['channel']] ?? $o['channel']) ?></span><?php endif; ?>
            <?php if ($delivery && !$o['pickup_time'] && !in_array($o['order_status'], ['completed', 'cancelled'], true)): ?><span class="tag tag--warn"><?= icon('clock', '', 13) ?> Set delivery time</span><?php endif; ?>
            <?php if ($overdue): ?><span class="tag tag--warn">Not marked done</span><?php endif; ?>
        </div>
    </div>
    <div class="order-row__money">
        <strong><?= e(money((float) $o['total'])) ?></strong>
        <?php if ($o['order_status'] !== 'cancelled' && $balance > 0.004): ?><span class="is-due"><?= e(money($balance)) ?> to pay</span><?php elseif ($o['order_status'] !== 'cancelled'): ?><span class="is-paid">Paid</span><?php endif; ?>
    </div>
    <div class="order-row__actions">
        <?php if ($next): ?>
            <form method="post" action="<?= e(admin_url('orders/view')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="order_status" value="<?= e($next[0]) ?>">
                <input type="hidden" name="back" value="<?= e(admin_back_path()) ?>">
                <button class="btn btn--secondary btn--sm" type="submit"><?= icon('check', '', 15) ?> <?= e($next[1]) ?></button>
            </form>
        <?php endif; ?>
        <a class="btn btn--primary btn--sm order-row__open" href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>">Open <?= icon('arrow', '', 15) ?></a>
    </div>
</article>
