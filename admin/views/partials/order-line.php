<?php
/**
 * One order on a single line (the dashboard's short lists): time or date, customer, cakes,
 * status and money. The whole line opens the order.
 *
 * @var array  $o     order row with a "summary" of its cakes (optional)
 * @var string $show  'time' (today) or 'date'
 */
$show ??= 'time';
$delivery = is_delivery($o);
$balance = order_balance($o);
$open = !in_array($o['order_status'], ['completed', 'cancelled'], true);
$late = $open && $o['pickup_date'] < date('Y-m-d');
?>
<a class="order-line order-line--<?= e($o['order_status']) ?><?= $late ? ' is-late' : '' ?>" href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>">
    <span class="order-line__when">
        <?php if ($show === 'date'): ?>
            <strong><?= e(date('D j', strtotime($o['pickup_date']))) ?></strong><small><?= e(date('M', strtotime($o['pickup_date']))) ?></small>
        <?php else: ?>
            <strong><?= e($o['pickup_time'] ? format_time($o['pickup_time']) : ($delivery ? 'Time?' : '')) ?></strong><small><?= $delivery ? 'Delivery' : 'Pickup' ?></small>
        <?php endif; ?>
    </span>
    <span class="order-line__main">
        <strong><?= e($o['customer_name']) ?> <small><?= e($o['order_number']) ?></small></strong>
        <small><?= e((string) (($o['summary'] ?? '') ?: ($delivery ? 'Delivery' . ($o['delivery_suburb'] ? ' to ' . $o['delivery_suburb'] : '') : 'Pickup'))) ?></small>
    </span>
    <span class="order-line__end">
        <?= status_badge($o['order_status'], order_status_label($o)) ?>
        <small class="<?= $o['order_status'] !== 'cancelled' && $balance > 0.004 ? 'is-due' : 'is-paid' ?>"><?= $o['order_status'] !== 'cancelled' && $balance > 0.004 ? e(money($balance)) . ' to pay' : e(money((float) $o['total'])) ?></small>
    </span>
</a>
