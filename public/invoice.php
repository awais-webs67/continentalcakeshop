<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// The order's receipt (a tax invoice when the shop is registered for GST).
//   /order/<number>/invoice?t=...                  a digital receipt: on screen, or printed on A4 / saved as PDF
//   /order/<number>/invoice?t=...&format=thermal   the same receipt for an 80mm receipt printer
// Customers open it from their order page; staff open it from the admin order page (Print menu).

$order = get_order_by_number(input('number'));
$isStaff = !empty($_SESSION['admin_id']) && db_value('SELECT 1 FROM admins WHERE id = ? AND is_active = 1', [(int) $_SESSION['admin_id']]);
if (!$order || (!$isStaff && !hash_equals($order['access_token'], input('t')))) {
    not_found();
}
// The receipt shows the address, phone and email of the shop that made the order.
use_branch(get_branch((int) $order['branch_id']));
header('X-Robots-Tag: noindex, nofollow');

$thermal = input('format') === 'thermal';
$items = get_order_items((int) $order['id']);
$payments = get_order_payments((int) $order['id']);
$gst = setting_bool('gst_registered') && setting('store_abn') !== '';
$docTitle = $gst ? 'Tax invoice' : 'Receipt';
$gstAmount = round(((float) $order['total']) / 11, 2);
$balance = order_balance($order);
$cancelled = $order['order_status'] === 'cancelled';
$settled = !$cancelled && $balance <= 0.004 && ($payments || $order['payment_status'] === 'paid');
$delivery = is_delivery($order);
$when = date('D j M Y', strtotime($order['pickup_date'])) . ($order['pickup_time'] ? ', ' . format_time($order['pickup_time']) : '');
$contact = trim(setting('store_phone') . (shop_email() !== '' ? ' · ' . shop_email() : ''), ' ·');
$site = preg_replace('#^https?://#', '', rtrim(url(), '/'));
$back = $isStaff ? url('admin/orders/view?id=' . $order['id']) : order_url($order);
$self = invoice_url($order);
[$statusClass, $statusLabel] = match (true) {
    $cancelled      => ['void', 'Cancelled'],
    $settled        => ['paid', 'Paid'],
    $balance > 0.004 && order_net_paid($order) > 0 => ['due', money($balance) . ' to pay'],
    default         => ['due', 'Not paid yet'],
};
?>
<!doctype html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($docTitle . ' ' . $order['order_number'] . ' | ' . setting('store_short_name')) ?></title>
<?php if ($thermal): ?>
<style>
    /* 80mm thermal paper: about 72mm can be printed. Plain black, no colours or images that waste ink. */
    @page { size: 80mm auto; margin: 0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; background: #E4E4E7; }
    body { color: #000; font: 12.5px/1.4 "Courier New", ui-monospace, Menlo, Consolas, monospace; }
    .bar { display: flex; gap: 8px; justify-content: center; padding: 14px; font-family: Arial, Helvetica, sans-serif; }
    .bar a, .bar button { padding: 9px 16px; border: 1px solid #D4D4D8; border-radius: 8px; background: #fff; color: #111; font: 600 14px Arial, Helvetica, sans-serif; text-decoration: none; cursor: pointer; }
    .bar button { background: #111; border-color: #111; color: #fff; }
    .paper { width: 80mm; margin: 0 auto 24px; padding: 5mm 4mm 8mm; background: #fff; box-shadow: 0 6px 24px rgba(0, 0, 0, .12); }
    .c { text-align: center; }
    .shop { font: 700 16px/1.25 Arial, Helvetica, sans-serif; letter-spacing: .02em; }
    .small { font-size: 11px; }
    .rule { border: 0; border-top: 1px dashed #000; margin: 7px 0; }
    .rule--solid { border-top-style: solid; border-top-width: 2px; }
    .doc { margin: 2px 0; font: 700 14px Arial, Helvetica, sans-serif; letter-spacing: .12em; text-transform: uppercase; }
    .row { display: flex; justify-content: space-between; gap: 8px; }
    .row span:last-child { text-align: right; white-space: nowrap; }
    .item { margin: 0 0 5px; }
    .item .detail { padding-left: 3ch; font-size: 11px; }
    .total { font: 700 16px Arial, Helvetica, sans-serif; }
    .box { margin: 6px 0; padding: 5px 6px; border: 1.5px solid #000; text-align: center; font: 700 13px Arial, Helvetica, sans-serif; letter-spacing: .08em; }
    @media print { html, body { background: #fff; } .bar { display: none; } .paper { width: auto; margin: 0; box-shadow: none; } }
</style>
</head>
<body>
<div class="bar"><a href="<?= e($self) ?>">Normal receipt</a><button type="button" onclick="window.print()">Print</button></div>
<div class="paper">
    <div class="c shop"><?= e(setting('store_name')) ?></div>
    <div class="c small"><?= e(store_address_line()) ?></div>
    <?php if ($contact !== ''): ?><div class="c small"><?= e($contact) ?></div><?php endif; ?>
    <?php if (setting('store_abn') !== ''): ?><div class="c small">ABN <?= e(setting('store_abn')) ?></div><?php endif; ?>
    <hr class="rule">
    <div class="c doc"><?= e($docTitle) ?></div>
    <div class="row"><span>Order</span><span><?= e($order['order_number']) ?></span></div>
    <div class="row"><span>Date</span><span><?= e(date('j M Y g:ia', strtotime($order['created_at']))) ?></span></div>
    <div class="row"><span>Customer</span><span><?= e($order['customer_name']) ?></span></div>
    <hr class="rule">
    <?php foreach ($items as $item): ?>
        <div class="item">
            <div class="row"><span><?= (int) $item['quantity'] ?> x <?= e($item['product_name']) ?></span><span><?= e(money($item['line_total'])) ?></span></div>
            <?php if (order_item_detail($item) !== ''): ?><div class="detail"><?= e(order_item_detail($item)) ?></div><?php endif; ?>
            <?php if ((int) $item['quantity'] > 1): ?><div class="detail">@ <?= e(money($item['unit_price'])) ?> each</div><?php endif; ?>
        </div>
    <?php endforeach; ?>
    <hr class="rule">
    <div class="row"><span>Subtotal</span><span><?= e(money($order['subtotal'])) ?></span></div>
    <?php if ((float) $order['discount'] > 0): ?><div class="row"><span>Discount<?= $order['coupon_code'] ? ' ' . e((string) $order['coupon_code']) : '' ?></span><span>-<?= e(money($order['discount'])) ?></span></div><?php endif; ?>
    <?php if ($delivery): ?><div class="row"><span>Delivery</span><span><?= (float) $order['delivery_fee'] > 0 ? e(money($order['delivery_fee'])) : 'Free' ?></span></div><?php endif; ?>
    <hr class="rule rule--solid">
    <div class="row total"><span>TOTAL</span><span><?= e(money($order['total'])) ?></span></div>
    <?php if ($gst): ?><div class="row small"><span>Includes GST</span><span><?= e(money($gstAmount)) ?></span></div><?php endif; ?>
    <hr class="rule">
    <?php foreach ($payments as $p): ?>
        <div class="row"><span><?= $p['kind'] === 'refund' ? 'Refund' : 'Paid' ?> <?= e(payment_method_label($p['method'])) ?></span><span><?= $p['kind'] === 'refund' ? '+' : '-' ?><?= e(money($p['amount'])) ?></span></div>
    <?php endforeach; ?>
    <?php if (!$payments && $order['payment_status'] === 'paid'): ?><div class="row"><span>Paid <?= e(payment_method_label($order['payment_method'])) ?></span><span>-<?= e(money($order['total'])) ?></span></div><?php endif; ?>
    <?php if (!$cancelled): ?><div class="row" style="font-weight:700"><span>Balance due</span><span><?= e(money(max(0, $balance))) ?></span></div><?php endif; ?>
    <div class="box"><?= e(strtoupper($statusLabel)) ?></div>
    <div class="row"><span><?= $delivery ? 'Delivery' : 'Pickup' ?></span><span><?= e($when) ?></span></div>
    <?php if ($delivery): ?><div class="small"><?= e(order_address($order)) ?></div><?php endif; ?>
    <hr class="rule">
    <div class="c">Thank you!</div>
    <div class="c small"><?= e($site) ?></div>
</div>
</body>
</html>
<?php return; endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&family=Outfit:wght@600;700&display=swap">
<style>
    :root { --ink: #111113; --text: #27272A; --muted: #71717A; --line: #E4E4E7; --soft: #F4F4F5; --brand: #1E9B82; --green: #15803D; --green-bg: #DCFCE7; --amber: #92400E; --amber-bg: #FEF3C7; --red: #B91C1C; --red-bg: #FEE2E2; }
    * { box-sizing: border-box; }
    body { margin: 0; background: var(--soft); color: var(--text); font: 14px/1.55 "DM Sans", "Helvetica Neue", Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bar { max-width: 640px; margin: 0 auto; padding: 16px 16px 0; display: flex; flex-wrap: wrap; gap: 8px; justify-content: space-between; }
    .bar div { display: flex; flex-wrap: wrap; gap: 8px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border: 1px solid #D4D4D8; border-radius: 8px; background: #fff; color: var(--ink); font: 600 14px/1.2 inherit; font-family: inherit; text-decoration: none; cursor: pointer; }
    .btn--dark { background: var(--ink); border-color: var(--ink); color: #fff; }
    .receipt { max-width: 640px; margin: 14px auto 40px; background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 1px 2px rgba(0, 0, 0, .04), 0 12px 40px -12px rgba(0, 0, 0, .12); overflow: hidden; }
    .top { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 22px 28px; border-bottom: 1px solid var(--line); }
    .shop { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .shop img { flex: none; width: 46px; height: 46px; border-radius: 50%; border: 1px solid var(--line); }
    .shop strong { display: block; color: var(--ink); font: 700 16px/1.25 Outfit, "DM Sans", sans-serif; }
    .shop span { display: block; color: var(--muted); font-size: 12.5px; }
    .doc { text-align: right; font-size: 12.5px; color: var(--muted); white-space: nowrap; }
    .doc b { display: block; color: var(--ink); font-size: 11px; letter-spacing: .12em; text-transform: uppercase; }
    .hero { padding: 30px 28px 26px; text-align: center; }
    .hero small { display: block; color: var(--muted); font-size: 13px; }
    .hero .amount { display: block; margin: 4px 0 10px; color: var(--ink); font: 700 44px/1.1 Outfit, "DM Sans", sans-serif; letter-spacing: -.02em; }
    .status { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px; font-size: 13px; font-weight: 700; }
    .status::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .status--paid { background: var(--green-bg); color: var(--green); }
    .status--due { background: var(--amber-bg); color: var(--amber); }
    .status--void { background: var(--red-bg); color: var(--red); }
    .facts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
    .facts div { padding: 14px 18px; min-width: 0; }
    .facts div + div { border-left: 1px solid var(--line); }
    .facts b { display: block; margin-bottom: 2px; color: var(--muted); font-size: 11px; letter-spacing: .1em; text-transform: uppercase; }
    .facts span { display: block; color: var(--ink); overflow-wrap: anywhere; }
    .facts small { display: block; color: var(--muted); font-size: 12.5px; overflow-wrap: anywhere; }
    .section { padding: 18px 28px; }
    .section h2 { margin: 0 0 8px; color: var(--muted); font-size: 11px; letter-spacing: .1em; text-transform: uppercase; }
    .line { display: flex; justify-content: space-between; gap: 16px; padding: 10px 0; border-bottom: 1px solid #EFEFF1; }
    .line:last-child { border-bottom: 0; }
    .line strong { color: var(--ink); font-weight: 600; }
    .line small { display: block; color: var(--muted); font-size: 12.5px; }
    .line .money { white-space: nowrap; color: var(--ink); font-weight: 600; text-align: right; }
    .qty { display: inline-grid; place-items: center; min-width: 24px; height: 22px; margin-right: 8px; padding: 0 6px; border-radius: 6px; background: var(--soft); color: var(--ink); font-size: 12px; font-weight: 700; vertical-align: 1px; }
    .sums { padding: 6px 28px 20px; }
    .sum { display: flex; justify-content: space-between; gap: 16px; padding: 4px 0; color: var(--muted); }
    .sum span:last-child { color: var(--ink); white-space: nowrap; }
    .sum--total { margin-top: 6px; padding-top: 12px; border-top: 1px solid var(--ink); color: var(--ink); font-weight: 700; font-size: 17px; }
    .sum--due { margin-top: 8px; padding: 10px 12px; border-radius: 10px; background: var(--amber-bg); color: var(--amber); font-weight: 700; }
    .sum--due span:last-child { color: var(--amber); }
    .sum--paid span:last-child { color: var(--green); font-weight: 700; }
    .thanks { padding: 20px 28px 24px; border-top: 1px solid var(--line); background: #FAFAFA; text-align: center; color: var(--muted); font-size: 13px; }
    .thanks strong { display: block; margin-bottom: 2px; color: var(--ink); font-size: 15px; }
    @media (max-width: 600px) {
        .bar { padding: 12px 12px 0; } .bar div { width: 100%; } .bar .btn { flex: 1; justify-content: center; }
        .receipt { margin: 10px 0 0; border-radius: 0; border-left: 0; border-right: 0; }
        .top, .hero, .section, .sums, .thanks { padding-left: 18px; padding-right: 18px; }
        .doc { display: none; }
        .hero .amount { font-size: 38px; }
        .facts { grid-template-columns: 1fr; } .facts div + div { border-left: 0; border-top: 1px solid var(--line); }
    }
    @page { size: A4; margin: 14mm; }
    @media print { body { background: #fff; } .bar { display: none; } .receipt { margin: 0 auto; border: 0; box-shadow: none; max-width: 600px; } }
</style>
</head>
<body>
<div class="bar">
    <a class="btn" href="<?= e($back) ?>">&larr; Back to the order</a>
    <div>
        <?php if ($isStaff): ?><a class="btn" href="<?= e($self . '&format=thermal') ?>">Thermal (80mm)</a><?php endif; ?>
        <button class="btn btn--dark" type="button" onclick="window.print()">Print or save PDF</button>
    </div>
</div>
<main class="receipt">
    <header class="top">
        <div class="shop">
            <img src="<?= e(url('assets/img/logo-email.png')) ?>" alt="">
            <div>
                <strong><?= e(setting('store_name')) ?></strong>
                <span><?= e(store_address_line()) ?></span>
                <?php if (setting('store_abn') !== ''): ?><span>ABN <?= e(setting('store_abn')) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="doc"><b><?= e($docTitle) ?></b><?= e($order['order_number']) ?><br><?= e(date('j M Y', strtotime($order['created_at']))) ?></div>
    </header>

    <section class="hero">
        <small><?= e($docTitle) ?> <?= e($order['order_number']) ?> &middot; <?= e(date('j F Y', strtotime($order['created_at']))) ?></small>
        <span class="amount"><?= e(money($order['total'])) ?></span>
        <span class="status status--<?= e($statusClass) ?>"><?= e($statusLabel) ?></span>
    </section>

    <div class="facts">
        <div><b>Customer</b><span><?= e($order['customer_name']) ?></span><?php if ($order['customer_email'] !== ''): ?><small><?= e($order['customer_email']) ?></small><?php endif; ?><?php if ($order['customer_phone'] !== ''): ?><small><?= e($order['customer_phone']) ?></small><?php endif; ?></div>
        <div><b><?= $delivery ? 'Delivery' : 'Pickup' ?></b><span><?= e($when) ?></span><small><?= e(order_address($order)) ?></small></div>
        <div><b>Payment</b><span><?= e(payment_method_label($order['payment_method'])) ?></span><small><?= e(PAYMENT_STATUSES[$order['payment_status']] ?? $order['payment_status']) ?></small></div>
    </div>

    <section class="section">
        <h2>Items</h2>
        <?php foreach ($items as $item): ?>
            <div class="line">
                <div><strong><span class="qty"><?= (int) $item['quantity'] ?></span><?= e($item['product_name']) ?></strong><small><?= e(trim(order_item_detail($item) . ((int) $item['quantity'] > 1 ? ' · ' . money($item['unit_price']) . ' each' : ''), ' ·')) ?></small></div>
                <span class="money"><?= e(money($item['line_total'])) ?></span>
            </div>
        <?php endforeach; ?>
    </section>

    <div class="sums">
        <div class="sum"><span>Subtotal</span><span><?= e(money($order['subtotal'])) ?></span></div>
        <?php if ((float) $order['discount'] > 0): ?><div class="sum"><span>Discount<?= $order['coupon_code'] ? ' (' . e((string) $order['coupon_code']) . ')' : '' ?></span><span>-<?= e(money($order['discount'])) ?></span></div><?php endif; ?>
        <?php if ($delivery): ?><div class="sum"><span>Delivery</span><span><?= (float) $order['delivery_fee'] > 0 ? e(money($order['delivery_fee'])) : 'Free' ?></span></div><?php endif; ?>
        <div class="sum sum--total"><span>Total<?= $gst ? ' (incl. GST)' : '' ?></span><span><?= e(money($order['total'])) ?></span></div>
        <?php if ($gst): ?><div class="sum"><span>GST included</span><span><?= e(money($gstAmount)) ?></span></div><?php endif; ?>
        <?php foreach ($payments as $p): ?>
            <div class="sum"><span><?= $p['kind'] === 'refund' ? 'Refunded' : 'Paid' ?> <?= e(date('j M', strtotime($p['created_at']))) ?> &middot; <?= e(payment_method_label($p['method'])) ?></span><span><?= $p['kind'] === 'refund' ? '+' : '-' ?><?= e(money($p['amount'])) ?></span></div>
        <?php endforeach; ?>
        <?php if (!$payments && $order['payment_status'] === 'paid'): ?><div class="sum"><span>Paid</span><span>-<?= e(money($order['total'])) ?></span></div><?php endif; ?>
        <?php if (!$cancelled && $balance > 0.004): ?>
            <div class="sum sum--due"><span>Balance due</span><span><?= e(money($balance)) ?></span></div>
        <?php elseif ($settled): ?>
            <div class="sum sum--paid"><span>Balance</span><span><?= e(money(0)) ?></span></div>
        <?php endif; ?>
    </div>

    <footer class="thanks">
        <strong>Thank you for choosing <?= e(setting('store_short_name')) ?></strong>
        <?php if ($contact !== ''): ?><?= e($contact) ?> &middot; <?php endif; ?><?= e($site) ?>
    </footer>
</main>
</body>
</html>
