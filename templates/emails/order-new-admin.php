<?php /** @var array $order @var array $items */ ?>
<p style="margin:0 0 12px"><strong><?= e($order['customer_name']) ?></strong> placed order <strong><?= e($order['order_number']) ?></strong>.</p>
<p style="margin:0 0 4px">Phone: <a href="tel:<?= e($order['customer_phone']) ?>" style="color:#1A8670"><?= e($order['customer_phone']) ?></a><br>
Email: <a href="mailto:<?= e($order['customer_email']) ?>" style="color:#1A8670"><?= e($order['customer_email']) ?></a><br>
Payment: <?= e(payment_method_label($order['payment_method'])) ?> (<?= e(PAYMENT_STATUSES[$order['payment_status']]) ?>)</p>
<?php require __DIR__ . '/partials/pickup.php'; ?>
<?php require __DIR__ . '/partials/items.php'; ?>
<?php if ($order['notes']): ?>
    <p style="margin:16px 0 0;padding:12px 14px;background:#FFF8E6;border-radius:10px"><strong>Customer notes:</strong> <?= nl2br(e($order['notes'])) ?></p>
<?php endif; ?>
<?= email_button(email_admin_url('orders/view?id=' . $order['id']), 'Open in admin panel') ?>
