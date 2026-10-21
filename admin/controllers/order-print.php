<?php
require_admin();
$order = get_order(query_int('id'));
if (!$order) {
    exit('Order not found');
}
$items = get_order_items((int) $order['id']);
?>
<!doctype html>
<html lang="en-AU"><head><meta charset="utf-8"><title>Ticket <?= e($order['order_number']) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>"></head>
<body onload="window.print()">
<div class="print-sheet">
    <p class="no-print"><button class="btn btn--primary" onclick="window.print()">Print</button></p>
    <h1><?= e($order['order_number']) ?></h1>
    <p><strong><?= $order['fulfilment'] === 'delivery' ? 'Delivery' : 'Pickup' ?>:</strong> <?= e(order_when($order)) ?><br>
    <?php if (is_delivery($order)): ?><strong>Address:</strong> <?= e(order_address($order)) ?><br><?php endif; ?>
    <strong>Customer:</strong> <?= e($order['customer_name']) ?>, <?= e($order['customer_phone']) ?><br>
    <strong>Payment:</strong> <?= e(payment_method_label($order['payment_method'])) ?> (<?= e(PAYMENT_STATUSES[$order['payment_status']]) ?>)</p>
    <table>
        <tr><th>Qty</th><th>Cake</th><th>Size</th><th>Message on cake</th></tr>
        <?php foreach ($items as $item): ?>
            <tr><td><?= (int) $item['quantity'] ?></td><td><?= e($item['product_name']) ?></td><td><?= e(trim($item['size_label'] . ($item['options'] ? ', ' . $item['options'] : ''), ', ')) ?></td><td><?= e((string) $item['cake_message']) ?></td></tr>
        <?php endforeach; ?>
    </table>
    <?php if ((float) $order['discount'] > 0): ?><p><strong>Coupon:</strong> <?= e((string) $order['coupon_code']) ?> (-<?= e(money($order['discount'])) ?>)</p><?php endif; ?>
    <?php if ($order['notes']): ?><p><strong>Customer notes:</strong> <?= nl2br(e($order['notes'])) ?></p><?php endif; ?>
    <?php if ($order['admin_notes']): ?><p><strong>Staff notes:</strong> <?= nl2br(e($order['admin_notes'])) ?></p><?php endif; ?>
</div>
</body></html>
