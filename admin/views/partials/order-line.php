<?php
/**
 * One order on a single line (the dashboard's short lists): time or date, customer, cakes,
 * status and money. The whole line opens the order; with $quick the next step (Confirm,
 * Mark ready, Collected) is one tap on the right.
 *
 * @var array  $o      order row with a "summary" of its cakes (optional)
 * @var string $show   'time' (today) or 'date'
 * @var bool   $quick  show the next-step button
 */
$show ??= 'time';
$quick ??= false;
$delivery = is_delivery($o);
$balance = order_balance($o);
$open = !in_array($o['order_status'], ['completed', 'cancelled'], true);
$late = $open && $o['pickup_date'] < date('Y-m-d');
$next = $quick ? order_next_step($o) : null;
$owing = $o['order_status'] !== 'cancelled' && $balance > 0.004;
?>
<div class="order-line order-line--<?= e($o['order_status']) ?><?= $late ? ' is-late' : '' ?><?= $quick ? ' order-line--quick' : '' ?>">
    <span class="order-line__when">
        <?php if ($show === 'date'): ?>
            <strong><?= e(date('D j', strtotime($o['pickup_date']))) ?></strong><small><?= e(date('M', strtotime($o['pickup_date']))) ?></small>
        <?php else: ?>
            <strong><?= e($o['pickup_time'] ? format_time($o['pickup_time']) : ($delivery ? 'Time?' : '')) ?></strong><small><?= $delivery ? 'Delivery' : 'Pickup' ?></small>
        <?php endif; ?>
    </span>
    <a class="order-line__main" href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>">
        <strong><?= e($o['customer_name']) ?> <small><?= e($o['order_number']) ?></small></strong>
        <small><?= e((string) (($o['summary'] ?? '') ?: ($delivery ? 'Delivery' . ($o['delivery_suburb'] ? ' to ' . $o['delivery_suburb'] : '') : 'Pickup'))) ?></small>
    </a>
    <span class="order-line__end">
        <?= status_badge($o['order_status'], order_status_label($o)) ?>
        <small class="<?= $owing ? 'is-due' : 'is-paid' ?>"><?= $owing ? e(money($balance)) . ' to pay' : e(money((float) $o['total'])) ?></small>
    </span>
    <?php if ($quick): ?>
        <span class="order-line__act">
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
                <span class="order-line__done"><?= icon('check', '', 15) ?> Done</span>
            <?php endif; ?>
        </span>
    <?php endif; ?>
</div>
