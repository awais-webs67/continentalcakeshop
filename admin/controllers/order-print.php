<?php
// Kitchen ticket: the slip the baker works from and tapes to the cake box. Big date and time,
// exactly what goes on each cake, extras to pack, notes, and how much to collect.
//   orders/print?id=12            one order
//   orders/print?date=2026-10-24  every order for that day, one ticket per page
//   &size=80                      80 mm receipt printer instead of A4/A5 paper
require_admin();

$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['date'] ?? '')) ? $_GET['date'] : '';
if ($date !== '') {
    $orders = db_all("SELECT * FROM orders WHERE pickup_date = ? AND order_status <> 'cancelled' ORDER BY pickup_time IS NULL, pickup_time, id", [$date]);
} else {
    $one = get_order(query_int('id'));
    $orders = $one ? [$one] : [];
}
if (!$orders && $date === '') {
    http_response_code(404);
    exit('Order not found');
}
$receipt = ($_GET['size'] ?? '') === '80';
$switch = static fn (bool $toReceipt): string => admin_url('orders/print', array_filter([
    'id' => $date === '' ? ($orders[0]['id'] ?? null) : null, 'date' => $date ?: null, 'size' => $toReceipt ? '80' : null,
]));
$title = $date !== '' ? 'Tickets for ' . date('D j M', strtotime($date)) : 'Ticket ' . $orders[0]['order_number'];
?>
<!doctype html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?></title>
<style>
    :root { --brand: #1E9B82; --brand-soft: #E6F7F2; --ink: #111315; --muted: #5E636A; --line: #D9E1DF; --warn: #B42318; --warn-bg: #FEF0EF; --amber: #8A5A00; --amber-bg: #FFF4DB; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #ECEFEE; color: var(--ink); font: 14px/1.45 "DM Sans", Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .toolbar { position: sticky; top: 0; z-index: 5; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 12px 16px; background: #fff; border-bottom: 1px solid var(--line); }
    .toolbar strong { font-size: 15px; }
    .toolbar__actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border: 1px solid var(--line); border-radius: 999px; background: #fff; color: var(--ink); font: 700 13px/1 inherit; font-family: inherit; text-decoration: none; cursor: pointer; }
    .btn--primary { border-color: var(--brand); background: var(--brand); color: #fff; }
    .btn.is-on { border-color: var(--brand); color: var(--brand); background: var(--brand-soft); }
    .empty { max-width: 520px; margin: 60px auto; padding: 30px; background: #fff; border-radius: 14px; text-align: center; }

    .ticket { width: 100%; max-width: 680px; margin: 20px auto; padding: 26px 28px; background: #fff; border-radius: 14px; box-shadow: 0 8px 28px rgba(0, 0, 0, .08); }
    .ticket + .ticket { margin-top: 26px; }
    .t-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; padding-bottom: 14px; border-bottom: 3px solid var(--ink); }
    .t-shop { font-size: 12px; color: var(--muted); font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
    .t-number { margin: 2px 0 0; font-size: 26px; font-weight: 800; letter-spacing: .01em; }
    .t-kind { display: inline-block; padding: 6px 14px; border-radius: 999px; background: var(--ink); color: #fff; font-weight: 800; font-size: 14px; letter-spacing: .08em; text-transform: uppercase; white-space: nowrap; }
    .t-kind--delivery { background: #3A3F9E; }
    .t-when { display: grid; grid-template-columns: 1fr auto; gap: 4px 18px; align-items: end; margin: 16px 0; padding: 14px 16px; border: 2px solid var(--ink); border-radius: 12px; }
    .t-when__day { font-size: 22px; font-weight: 800; line-height: 1.15; }
    .t-when__time { font-size: 30px; font-weight: 800; line-height: 1; text-align: right; }
    .t-when small { grid-column: 1 / -1; color: var(--muted); font-weight: 600; }
    .t-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px; }
    .t-label { margin: 0 0 3px; color: var(--muted); font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .t-value { font-size: 15px; font-weight: 700; }
    .t-value span { display: block; font-weight: 500; color: var(--muted); }
    .t-cakes { display: flex; flex-direction: column; gap: 12px; }
    .t-cake { padding: 14px 16px; border: 1.5px solid var(--line); border-radius: 12px; break-inside: avoid; }
    .t-cake__top { display: flex; align-items: baseline; gap: 10px; }
    .t-cake__qty { flex: 0 0 auto; display: inline-grid; place-items: center; min-width: 38px; height: 38px; padding: 0 8px; border-radius: 10px; background: var(--ink); color: #fff; font-size: 18px; font-weight: 800; }
    .t-cake__name { font-size: 19px; font-weight: 800; line-height: 1.25; }
    .t-cake__size { margin: 4px 0 0 48px; font-size: 15px; font-weight: 700; color: var(--muted); }
    .t-flag { display: inline-block; margin: 8px 6px 0 48px; padding: 3px 10px; border-radius: 6px; background: var(--amber-bg); color: var(--amber); font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: .03em; }
    .t-flag + .t-flag { margin-left: 0; }
    .t-message { margin: 10px 0 0 48px; padding: 10px 14px; border: 2px dashed var(--brand); border-radius: 10px; background: var(--brand-soft); }
    .t-message .t-label { color: var(--brand); }
    .t-message p:not(.t-label) { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: 22px; font-style: italic; font-weight: 700; line-height: 1.3; }
    .t-checks { display: flex; flex-wrap: wrap; gap: 6px 16px; margin: 12px 0 0 48px; color: var(--muted); font-size: 13px; font-weight: 700; }
    .t-checks span::before { content: ""; display: inline-block; width: 14px; height: 14px; margin-right: 6px; border: 2px solid var(--ink); border-radius: 3px; vertical-align: -2px; }
    .t-extras { margin-top: 12px; padding: 12px 16px; border-radius: 12px; background: #F5F7F6; }
    .t-extras ul { margin: 4px 0 0; padding-left: 18px; font-weight: 700; }
    .t-notes { margin-top: 12px; padding: 12px 16px; border-left: 5px solid var(--warn); border-radius: 8px; background: var(--warn-bg); }
    .t-notes p:not(.t-label) { margin: 2px 0 0; font-size: 15px; font-weight: 600; white-space: pre-line; }
    .t-notes--staff { border-left-color: var(--brand); background: var(--brand-soft); }
    .t-pay { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 16px; padding: 14px 16px; border-radius: 12px; border: 2px solid var(--ink); }
    .t-pay strong { font-size: 22px; font-weight: 800; }
    .t-pay--due { border-color: var(--warn); background: var(--warn-bg); color: var(--warn); }
    .t-pay--paid { border-color: var(--brand); background: var(--brand-soft); color: #0E5447; }
    .t-pay small { display: block; font-size: 12px; font-weight: 700; opacity: .85; }
    .t-foot { display: flex; justify-content: space-between; gap: 10px; margin-top: 14px; padding-top: 10px; border-top: 1px solid var(--line); color: var(--muted); font-size: 11.5px; }

    /* 80 mm receipt printers: one narrow column, black only */
    body.is-receipt .ticket { max-width: 302px; padding: 12px 10px; border-radius: 0; font-size: 12.5px; }
    body.is-receipt .t-head { flex-direction: column; gap: 6px; }
    body.is-receipt .t-number { font-size: 21px; }
    body.is-receipt .t-when { grid-template-columns: 1fr; padding: 10px; }
    body.is-receipt .t-when__day { font-size: 17px; }
    body.is-receipt .t-when__time { font-size: 24px; text-align: left; }
    body.is-receipt .t-grid { grid-template-columns: 1fr; gap: 8px; }
    body.is-receipt .t-cake { padding: 10px; }
    body.is-receipt .t-cake__name { font-size: 16px; }
    body.is-receipt .t-cake__size, body.is-receipt .t-flag, body.is-receipt .t-message, body.is-receipt .t-checks { margin-left: 0; }
    body.is-receipt .t-message p:not(.t-label) { font-size: 18px; }
    body.is-receipt .t-pay strong { font-size: 18px; }

    @media (max-width: 600px) { .ticket { margin: 12px; padding: 18px; } .t-grid { grid-template-columns: 1fr; } .t-when__time { font-size: 24px; } }
    @page { margin: 10mm; }
    @media print {
        body { background: #fff; }
        .toolbar { display: none; }
        .ticket { max-width: none; margin: 0; padding: 0; border-radius: 0; box-shadow: none; page-break-after: always; break-after: page; }
        .ticket:last-child { page-break-after: auto; break-after: auto; }
        body.is-receipt .ticket { max-width: 72mm; }
        body.is-receipt .t-pay--due, body.is-receipt .t-pay--paid, body.is-receipt .t-message { background: #fff; color: #000; }
    }
</style>
<?php if ($receipt): ?><style>@page { size: 80mm auto; margin: 3mm; }</style><?php endif; ?>
</head>
<body class="<?= $receipt ? 'is-receipt' : '' ?>">
<div class="toolbar">
    <strong><?= e($title) ?><?= $date !== '' ? ' (' . count($orders) . ')' : '' ?></strong>
    <div class="toolbar__actions">
        <a class="btn <?= $receipt ? '' : 'is-on' ?>" href="<?= e($switch(false)) ?>">A4 paper</a>
        <a class="btn <?= $receipt ? 'is-on' : '' ?>" href="<?= e($switch(true)) ?>">80 mm receipt printer</a>
        <a class="btn" href="<?= e($date !== '' ? admin_url('pickups') : admin_url('orders/view', ['id' => $orders[0]['id']])) ?>">Back</a>
        <?php if ($orders): ?><button class="btn btn--primary" type="button" onclick="window.print()">Print</button><?php endif; ?>
    </div>
</div>

<?php if (!$orders): ?>
    <div class="empty"><strong>No orders on <?= e(date('l j F', strtotime($date))) ?>.</strong></div>
<?php endif; ?>

<?php foreach ($orders as $order):
    $items = get_order_items((int) $order['id']);
    $cakes = array_filter($items, static fn ($i) => empty($i['addon_id']));
    $extras = array_filter($items, static fn ($i) => !empty($i['addon_id']));
    $delivery = is_delivery($order);
    $balance = order_balance($order);
    ?>
    <article class="ticket">
        <header class="t-head">
            <div>
                <div class="t-shop"><?= e(setting('store_short_name')) ?> &middot; Kitchen ticket</div>
                <h1 class="t-number"><?= e($order['order_number']) ?></h1>
            </div>
            <span class="t-kind<?= $delivery ? ' t-kind--delivery' : '' ?>"><?= $delivery ? 'Delivery' : 'Pickup' ?></span>
        </header>

        <div class="t-when">
            <div class="t-when__day"><?= e(date('l j F', strtotime($order['pickup_date']))) ?></div>
            <div class="t-when__time"><?= e($order['pickup_time'] ? format_time($order['pickup_time']) : 'Time TBC') ?></div>
            <?php if ($order['order_status'] === 'cancelled'): ?><small style="color:var(--warn)">CANCELLED</small><?php elseif ($delivery && !$order['pickup_time']): ?><small>Delivery time still to be set<?= ($order['delivery_window'] ?? '') !== '' ? ' (prefers ' . e(delivery_window_label($order['delivery_window'])) . ')' : '' ?></small><?php endif; ?>
        </div>

        <div class="t-grid">
            <div>
                <p class="t-label">Customer</p>
                <div class="t-value"><?= e($order['customer_name']) ?><span><?= e($order['customer_phone']) ?></span></div>
            </div>
            <div>
                <p class="t-label"><?= $delivery ? 'Deliver to' : 'Collect from' ?></p>
                <div class="t-value"><?= e(order_address($order)) ?></div>
            </div>
        </div>

        <div class="t-cakes">
            <?php foreach ($cakes as $item): ?>
                <section class="t-cake">
                    <div class="t-cake__top"><span class="t-cake__qty"><?= (int) $item['quantity'] ?>&times;</span><span class="t-cake__name"><?= e($item['product_name']) ?></span></div>
                    <?php if ($item['size_label'] !== ''): ?><p class="t-cake__size"><?= e($item['size_label']) ?></p><?php endif; ?>
                    <?php foreach (array_filter(array_map('trim', explode(',', (string) $item['options']))) as $option): ?><span class="t-flag"><?= e($option) ?></span><?php endforeach; ?>
                    <?php if (trim((string) $item['cake_message']) !== ''): ?>
                        <div class="t-message"><p class="t-label">Write on the cake</p><p>&ldquo;<?= e($item['cake_message']) ?>&rdquo;</p></div>
                    <?php endif; ?>
                    <div class="t-checks"><span>Baked</span><span>Decorated</span><span>Boxed</span><span>Checked</span></div>
                </section>
            <?php endforeach; ?>
        </div>

        <?php if ($extras): ?>
            <div class="t-extras">
                <p class="t-label">Extras to pack</p>
                <ul><?php foreach ($extras as $item): ?><li><?= (int) $item['quantity'] ?> &times; <?= e($item['product_name']) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <?php if (trim((string) $order['notes']) !== ''): ?>
            <div class="t-notes"><p class="t-label">Customer notes</p><p><?= e($order['notes']) ?></p></div>
        <?php endif; ?>
        <?php if (trim((string) $order['admin_notes']) !== ''): ?>
            <div class="t-notes t-notes--staff"><p class="t-label">Staff notes</p><p><?= e($order['admin_notes']) ?></p></div>
        <?php endif; ?>

        <?php if ($order['order_status'] !== 'cancelled'): ?>
            <div class="t-pay <?= $balance > 0.004 ? 't-pay--due' : 't-pay--paid' ?>">
                <div><small><?= $balance > 0.004 ? 'Collect from customer' : 'Payment' ?></small><strong><?= $balance > 0.004 ? e(money($balance)) : 'PAID' ?></strong></div>
                <div style="text-align:right"><small>Order total</small><strong style="font-size:16px"><?= e(money((float) $order['total'])) ?></strong></div>
            </div>
        <?php endif; ?>

        <footer class="t-foot">
            <span>Placed <?= e(date('j M Y, g:ia', strtotime($order['created_at']))) ?> &middot; <?= e(ORDER_CHANNELS[$order['channel'] ?? 'online'] ?? 'Website') ?></span>
            <span>Printed <?= e(date('j M, g:ia')) ?></span>
        </footer>
    </article>
<?php endforeach; ?>
</body>
</html>
