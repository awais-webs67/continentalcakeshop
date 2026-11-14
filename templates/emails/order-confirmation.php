<?php /** @var array $order @var array $items */ ?>
<p style="margin:0 0 12px">Hi <?= e(email_first_name($order['customer_name'])) ?>,</p>
<p style="margin:0 0 12px">Thank you for ordering from <?= e(setting('store_short_name')) ?>.</p>
<?php if ($order['payment_status'] === 'awaiting_verification'): ?>
    <p style="margin:0 0 12px;padding:12px 14px;background:#EEF3FB;border-radius:10px;color:#1F4C8A">We have your payment screenshot and will confirm your order as soon as we have checked it.</p>
<?php elseif ($order['payment_status'] === 'paid'): ?>
    <p style="margin:0 0 12px;padding:12px 14px;background:#E8F6EE;border-radius:10px;color:#1D6A43">Your payment has been received and your order is confirmed.</p>
<?php elseif ($order['payment_method'] === PAYMENT_SHOP && order_balance($order) > 0): ?>
    <p style="margin:0 0 12px;padding:12px 14px;background:#F4F4F5;border-radius:10px;color:#27272A">Nothing to pay now. Please pay <?= e(money(order_balance($order))) ?> by cash or card when you pick up your order.</p>
<?php elseif ($order['payment_method'] === PAYMENT_CALL && order_balance($order) > 0): ?>
    <p style="margin:0 0 12px;padding:12px 14px;background:#F4F4F5;border-radius:10px;color:#27272A">We will call you on <?= e($order['customer_phone']) ?> to take <?= e(money(order_balance($order))) ?> by card, then confirm your order.</p>
<?php endif; ?>
<?php require __DIR__ . '/partials/pickup.php'; ?>
<?php require __DIR__ . '/partials/items.php'; ?>
<?= email_button(order_url($order), 'View your order') ?>
<p style="margin:0;font-size:14px;color:#71717A">Need to change something? Call us on <?= e(setting('store_phone')) ?> and quote your order number.</p>
