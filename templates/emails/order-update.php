<?php /** @var array $order @var array $items @var string $message */ ?>
<p style="margin:0 0 12px">Hi <?= e(strtok($order['customer_name'], ' ')) ?>,</p>
<p style="margin:0 0 12px"><?= nl2br(e($message)) ?></p>
<p style="margin:0 0 4px;font-size:14px;color:#66666E">Order <strong style="color:#1B1B1F"><?= e($order['order_number']) ?></strong>
    &middot; <?= e(ORDER_STATUSES[$order['order_status']]) ?> &middot; <?= e(PAYMENT_STATUSES[$order['payment_status']]) ?></p>
<?php require __DIR__ . '/partials/pickup.php'; ?>
<?= email_button(order_url($order), 'View your order') ?>
<p style="margin:0;font-size:14px;color:#66666E">Questions? Call us on <?= e(setting('store_phone')) ?>.</p>
