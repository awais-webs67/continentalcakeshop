<?php
/** @var string $date @var array $pickups @var array $deliveries @var int $to_verify @var int $new_orders @var int $to_schedule @var array $requests @var int $unread @var int $tomorrow @var float $yesterday_sales */
$row = 'padding:9px 0;border-bottom:1px solid #E3EAE8;font-size:14px;color:#2E2E33;vertical-align:top';
$head = 'margin:22px 0 6px;font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:#58DCBF';
$alerts = array_filter([
    $to_verify ? $to_verify . ' payment screenshot' . ($to_verify === 1 ? '' : 's') . ' to check' : null,
    $new_orders ? $new_orders . ' new order' . ($new_orders === 1 ? '' : 's') . ' to confirm' : null,
    $to_schedule ? $to_schedule . ' deliver' . ($to_schedule === 1 ? 'y needs' : 'ies need') . ' a time' : null,
    $unread ? $unread . ' unread customer message' . ($unread === 1 ? '' : 's') : null,
]);
?>
<p style="margin:0 0 6px"><?= e(date('l j F Y', strtotime($date))) ?> &middot; <?= e(today_hours_label()) ?></p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:14px 0 4px"><tr>
    <td width="33%" style="padding:12px;background:#F1FCF9;border-radius:12px;text-align:center"><span style="font-size:26px;font-weight:bold;color:#1B1B1F"><?= count($pickups) ?></span><br><span style="font-size:12px;color:#66666E">Pickups</span></td>
    <td width="4"></td>
    <td width="33%" style="padding:12px;background:#F1FCF9;border-radius:12px;text-align:center"><span style="font-size:26px;font-weight:bold;color:#1B1B1F"><?= count($deliveries) ?></span><br><span style="font-size:12px;color:#66666E">Deliveries</span></td>
    <td width="4"></td>
    <td width="33%" style="padding:12px;background:#F1FCF9;border-radius:12px;text-align:center"><span style="font-size:26px;font-weight:bold;color:#1B1B1F"><?= e(money($yesterday_sales)) ?></span><br><span style="font-size:12px;color:#66666E">Paid yesterday</span></td>
</tr></table>

<?php if ($alerts): ?>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:14px 0 0"><tr><td style="padding:12px 16px;background:#FFF4DB;border-radius:12px;font-size:14px;color:#8A5A00">
        <strong>Needs attention:</strong> <?= e(implode(' · ', $alerts)) ?>
    </td></tr></table>
<?php endif; ?>

<p style="<?= $head ?>">Pickups</p>
<?php if (!$pickups): ?><p style="margin:0;font-size:14px;color:#66666E">No pickups today.</p><?php else: ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <?php foreach ($pickups as $o): ?>
        <tr><td width="70" style="<?= $row ?>;font-weight:bold"><?= e(order_time_label($o)) ?></td>
            <td style="<?= $row ?>"><strong><?= e($o['customer_name']) ?></strong> &middot; <?= e($o['order_number']) ?><br><span style="color:#66666E"><?= e((string) $o['summary']) ?></span></td>
            <td align="right" style="<?= $row ?>;white-space:nowrap;color:<?= $o['payment_status'] === 'paid' ? '#1C6B43' : '#8A5A00' ?>"><?= e(PAYMENT_STATUSES[$o['payment_status']]) ?></td></tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<p style="<?= $head ?>">Deliveries</p>
<?php if (!$deliveries): ?><p style="margin:0;font-size:14px;color:#66666E">No deliveries today.</p><?php else: ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <?php foreach ($deliveries as $o): ?>
        <tr><td width="90" style="<?= $row ?>;font-weight:bold"><?= e($o['pickup_time'] ? order_time_label($o) : 'Set time') ?></td>
            <td style="<?= $row ?>"><strong><?= e($o['customer_name']) ?></strong> &middot; <?= e($o['customer_phone']) ?><br><span style="color:#66666E"><?= e(order_address($o)) ?><br><?= e((string) $o['summary']) ?></span></td>
            <td align="right" style="<?= $row ?>;white-space:nowrap;color:<?= $o['payment_status'] === 'paid' ? '#1C6B43' : '#8A5A00' ?>"><?= e(PAYMENT_STATUSES[$o['payment_status']]) ?></td></tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<?php if ($requests): ?>
<p style="<?= $head ?>">Custom cakes this week</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <?php foreach ($requests as $r): ?>
        <tr><td width="90" style="<?= $row ?>;font-weight:bold"><?= e(date('D j M', strtotime($r['event_date']))) ?></td>
            <td style="<?= $row ?>"><?= e($r['occasion']) ?> cake for <?= e($r['name']) ?><?= $r['servings'] ? ' &middot; ' . e($r['servings']) : '' ?></td>
            <td align="right" style="<?= $row ?>;white-space:nowrap"><?= e(ucfirst($r['status'])) ?></td></tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<p style="margin:18px 0 0;font-size:14px;color:#66666E">Tomorrow: <?= $tomorrow ?> order<?= $tomorrow === 1 ? '' : 's' ?> booked.</p>
<?= email_button(email_admin_url(), 'Open the admin panel') ?>
<p style="margin:0;font-size:12px;color:#8A8A92">This email is sent every open day before the shop opens. If it does not arrive, check Admin &gt; Settings &gt; Emails.</p>
