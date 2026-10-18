<?php /** @var array $order */ ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;background:#F1FCF9;border-radius:12px">
    <tr><td style="padding:16px 18px;font-size:14px;line-height:1.6;color:#2E2E33">
        <strong style="display:block;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#1A8670"><?= $order['fulfilment'] === 'delivery' ? 'Delivery' : 'Pickup' ?></strong>
        <strong style="font-size:16px;color:#1B1B1F"><?= e(format_pickup($order['pickup_date'], $order['pickup_time'])) ?></strong><br>
        <?= $order['fulfilment'] === 'delivery' ? e((string) $order['delivery_address']) : e(store_address_line()) ?>
    </td></tr>
</table>
