<?php /** @var array $order @var array $items */ ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0 6px;border-collapse:collapse">
    <?php foreach ($items as $item): ?>
        <tr>
            <td style="padding:10px 0;border-bottom:1px solid #E3EAE8;font-size:15px;color:#1B1B1F">
                <strong><?= (int) $item['quantity'] ?> &times; <?= e($item['product_name']) ?></strong><br>
                <span style="font-size:13px;color:#66666E"><?= e($item['size_label']) ?><?= $item['cake_message'] ? ' &middot; Message: &ldquo;' . e($item['cake_message']) . '&rdquo;' : '' ?></span>
            </td>
            <td align="right" valign="top" style="padding:10px 0;border-bottom:1px solid #E3EAE8;font-size:15px;color:#1B1B1F;white-space:nowrap"><?= e(money($item['line_total'])) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if ((float) ($order['discount'] ?? 0) > 0): ?>
        <tr><td style="padding:8px 0;color:#1C8F77">Discount (<?= e((string) $order['coupon_code']) ?>)</td><td align="right" style="padding:8px 0;color:#1C8F77">-<?= e(money($order['discount'])) ?></td></tr>
    <?php endif; ?>
    <?php if ((float) $order['delivery_fee'] > 0): ?>
        <tr><td style="padding:8px 0;color:#66666E">Delivery</td><td align="right" style="padding:8px 0;color:#66666E"><?= e(money($order['delivery_fee'])) ?></td></tr>
    <?php endif; ?>
    <tr>
        <td style="padding:12px 0 0;font-size:16px;font-weight:bold;color:#1B1B1F">Total<?= setting_bool('gst_registered') ? ' <span style="font-size:12px;font-weight:normal;color:#66666E">(includes GST of ' . e(money(round((float) $order['total'] / 11, 2))) . ')</span>' : '' ?></td>
        <td align="right" style="padding:12px 0 0;font-size:18px;font-weight:bold;color:#1B1B1F"><?= e(money($order['total'])) ?></td>
    </tr>
</table>
