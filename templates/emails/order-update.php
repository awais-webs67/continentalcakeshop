<?php /** @var array $order @var array $items @var string $message */ ?>
<p style="margin:0 0 12px">Hi <?= e(email_first_name($order['customer_name'])) ?>,</p>
<p style="margin:0 0 12px"><?= nl2br(e($message)) ?></p>
<?php require __DIR__ . '/partials/pickup.php'; ?>
<?= email_button(order_url($order), 'View your order') ?>
<p style="margin:0;font-size:14px;color:#71717A">Questions? Call us on <?= e(setting('store_phone')) ?>.</p>
