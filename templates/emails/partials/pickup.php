<?php /** @var array $order */ ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;background:#F1FCF9;border-radius:12px">
    <tr><td style="padding:16px 18px;font-size:14px;line-height:1.6;color:#2E2E33">
        <strong style="display:block;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#58DCBF"><?= $order['fulfilment'] === 'delivery' ? 'Delivery' : 'Pickup' ?></strong>
        <strong style="font-size:16px;color:#1B1B1F"><?= e(order_when($order)) ?></strong><br>
        <?= e(order_address($order)) ?>
        <?php if (is_delivery($order) && !$order['pickup_time']): ?>
            <br><span style="color:#66666E">We will email you the delivery time before the day.<?= ($order['delivery_window'] ?? 'any') !== 'any' && $order['delivery_window'] ? ' Your preferred time: ' . e(delivery_window_label($order['delivery_window'])) . '.' : '' ?></span>
        <?php endif; ?>
    </td></tr>
</table>
