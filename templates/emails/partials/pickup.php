<?php /** @var array $order */ ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="stack" style="margin:18px 0;border:1px solid #E4E4E7;border-radius:10px;border-collapse:separate">
    <tr>
        <td valign="top" width="60%" style="padding:14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#27272A">
            <span style="display:block;font-size:11px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase;color:#71717A"><?= $order['fulfilment'] === 'delivery' ? 'Delivery' : 'Pickup' ?></span>
            <strong style="font-size:15px;color:#111113"><?= e(order_when($order)) ?></strong><br>
            <span style="color:#52525B"><?= e(order_address($order)) ?></span>
            <?php if (is_delivery($order) && !$order['pickup_time']): ?>
                <br><span style="color:#71717A">We will email you the delivery time as soon as it is planned.<?= ($order['delivery_window'] ?? 'any') !== 'any' && $order['delivery_window'] ? ' Your preferred time: ' . e(delivery_window_label($order['delivery_window'])) . '.' : '' ?></span>
            <?php endif; ?>
        </td>
        <td valign="top" style="padding:14px 16px;border-left:1px solid #EFEFF1;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#27272A">
            <span style="display:block;font-size:11px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase;color:#71717A">Order</span>
            <strong style="font-size:15px;color:#111113"><?= e($order['order_number']) ?></strong><br>
            <span style="color:#52525B"><?= e(order_status_label($order)) ?> &middot; <?= e(PAYMENT_STATUSES[$order['payment_status']] ?? '') ?></span>
        </td>
    </tr>
</table>
