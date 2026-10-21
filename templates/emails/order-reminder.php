<?php /** @var array $order @var array $items */
$balance = order_balance($order);
?>
<p style="margin:0 0 12px">Hi <?= e(strtok($order['customer_name'], ' ')) ?>,</p>
<p style="margin:0 0 12px">A quick reminder that your order <strong><?= e($order['order_number']) ?></strong> is <?= is_delivery($order) ? 'being delivered' : 'ready to collect' ?> tomorrow.</p>
<?php require __DIR__ . '/partials/pickup.php'; ?>
<?php if (!is_delivery($order)): ?>
    <p style="margin:0 0 12px;font-size:14px;color:#66666E">Please carry the box flat and keep the cake in the fridge until you serve it. <a href="<?= e(store_maps_url()) ?>" style="color:#1E9B82">Get directions</a></p>
<?php else: ?>
    <p style="margin:0 0 12px;font-size:14px;color:#66666E">Please make sure someone is home to receive the cake, and keep it in the fridge until you serve it.</p>
<?php endif; ?>
<?php if ($balance > 0 && $order['payment_status'] !== 'awaiting_verification'): ?>
    <p style="margin:0 0 12px;padding:12px 14px;background:#FFF8E6;border-radius:10px;color:#8A5A00"><strong><?= e(money($balance)) ?> still to pay.</strong>
        <?= $order['payment_method'] === PAYMENT_STRIPE ? 'You can pay by card from your order page.' : 'You can pay when you ' . (is_delivery($order) ? 'receive the cake' : 'collect the cake') . ', or by bank transfer before then.' ?></p>
<?php endif; ?>
<?php require __DIR__ . '/partials/items.php'; ?>
<?= email_button(order_url($order), $balance > 0 && $order['payment_method'] === PAYMENT_STRIPE ? 'View and pay' : 'View your order') ?>
<p style="margin:0;font-size:14px;color:#66666E">Need to change something? Call us on <?= e(setting('store_phone')) ?> as soon as you can.</p>
