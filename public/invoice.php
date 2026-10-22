<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// Printable tax invoice (or a receipt when the shop is not registered for GST).
// Customers open it from their order page; staff open it from the admin order page.

$order = get_order_by_number(input('number'));
$isStaff = !empty($_SESSION['admin_id']) && db_value('SELECT 1 FROM admins WHERE id = ? AND is_active = 1', [(int) $_SESSION['admin_id']]);
if (!$order || (!$isStaff && !hash_equals($order['access_token'], input('t')))) {
    not_found();
}
header('X-Robots-Tag: noindex, nofollow');

$items = get_order_items((int) $order['id']);
$payments = get_order_payments((int) $order['id']);
$gst = setting_bool('gst_registered') && setting('store_abn') !== '';
$docTitle = $gst ? 'Tax invoice' : 'Receipt';
$gstAmount = round(((float) $order['total']) / 11, 2);
$balance = order_balance($order);
?>
<!doctype html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($docTitle . ' ' . $order['order_number'] . ' | ' . setting('store_short_name')) ?></title>
<style>
    :root { --brand: #1E9B82; --brand-dark: #146B5A; --brand-deep: #0E5447; --brand-soft: #E6F7F2; --ink: #16181B; --muted: #646A70; --line: #E2E8E6; --amber: #8A5A00; --amber-bg: #FFF4DB; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #EEF2F1; color: var(--ink); font: 14px/1.55 "Helvetica Neue", Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .actions { max-width: 820px; margin: 0 auto; padding: 18px 16px 0; display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; border: 0; border-radius: 999px; background: var(--brand); color: #fff; font: 700 14px/1 inherit; font-family: inherit; text-decoration: none; cursor: pointer; box-shadow: 0 6px 16px rgba(30, 155, 130, .25); }
    .btn--plain { background: #fff; color: var(--ink); border: 1px solid var(--line); box-shadow: none; }
    .sheet { position: relative; max-width: 820px; margin: 16px auto 32px; overflow: hidden; background: #fff; border-radius: 18px; box-shadow: 0 18px 50px rgba(14, 84, 71, .12); }
    .band { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 18px; padding: 28px 36px; background: linear-gradient(135deg, var(--brand-deep), var(--brand) 70%); color: #fff; }
    .shop { display: flex; align-items: center; gap: 16px; }
    .shop img { width: 70px; height: 70px; padding: 6px; border-radius: 50%; background: #fff; }
    .shop strong { display: block; font-size: 18px; letter-spacing: .01em; }
    .shop span { display: block; opacity: .85; font-size: 13px; }
    .doc { text-align: right; }
    .doc h1 { margin: 0; font-size: 28px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .doc span { display: block; opacity: .9; font-size: 13px; }
    .doc strong { font-size: 15px; }
    .body { padding: 30px 36px 34px; }
    .stamp { position: absolute; top: 132px; right: 34px; padding: 6px 16px; border: 3px solid var(--brand); border-radius: 10px; color: var(--brand); font-size: 22px; font-weight: 900; letter-spacing: .14em; transform: rotate(-8deg); opacity: .9; background: rgba(255, 255, 255, .85); }
    .stamp--due { border-color: #C2410C; color: #C2410C; }
    .cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-bottom: 26px; }
    .info { padding: 14px 16px; border: 1px solid var(--line); border-radius: 14px; background: #FAFCFB; }
    .label { margin: 0 0 6px; font-size: 11px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--brand-dark); }
    .info strong { display: block; font-size: 15px; }
    .info span { display: block; color: var(--muted); overflow-wrap: anywhere; }
    table { width: 100%; border-collapse: collapse; }
    .items th { padding: 10px 12px; background: var(--brand-soft); color: var(--brand-deep); font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; text-align: left; }
    .items th:first-child { border-radius: 10px 0 0 10px; }
    .items th:last-child { border-radius: 0 10px 10px 0; }
    .items td { padding: 13px 12px; border-bottom: 1px solid var(--line); vertical-align: top; }
    .items td strong { font-size: 14.5px; }
    .items td small { display: block; margin-top: 2px; color: var(--muted); }
    .num { text-align: right; white-space: nowrap; }
    .bottom { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 24px; margin-top: 22px; align-items: start; }
    .thanks { padding: 16px 18px; border-radius: 14px; background: #FAFCFB; border: 1px dashed var(--line); color: var(--muted); font-size: 13px; }
    .thanks strong { display: block; margin-bottom: 4px; color: var(--ink); font-size: 15px; }
    .totals { padding: 6px 18px 14px; border: 1px solid var(--line); border-radius: 14px; }
    .totals td { padding: 7px 0; border: 0; color: var(--muted); }
    .totals td.num { color: var(--ink); font-weight: 600; }
    .totals .grand td { padding-top: 12px; border-top: 2px solid var(--ink); color: var(--ink); font-size: 17px; font-weight: 800; }
    .totals .grand td.num { color: var(--brand-dark); font-size: 22px; }
    .totals .paid td { font-size: 13px; }
    .totals .owing td { padding: 10px 12px; background: var(--amber-bg); color: var(--amber); font-weight: 800; }
    .totals .owing td:first-child { border-radius: 10px 0 0 10px; }
    .totals .owing td:last-child { border-radius: 0 10px 10px 0; }
    .totals .settled td { color: var(--brand-dark); font-weight: 800; }
    .foot { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 6px 16px; padding: 14px 36px; border-top: 1px solid var(--line); background: #FAFCFB; color: var(--muted); font-size: 12px; }
    @media (max-width: 680px) {
        .actions { justify-content: stretch; } .actions .btn { flex: 1; justify-content: center; }
        .sheet { margin: 12px 0 0; border-radius: 0; }
        .band { padding: 22px 18px; } .doc { text-align: left; } .doc h1 { font-size: 22px; }
        .body { padding: 22px 18px; } .stamp { top: 168px; right: 16px; font-size: 16px; }
        .cards { grid-template-columns: 1fr; } .bottom { grid-template-columns: 1fr; }
        .items th:nth-child(3), .items td:nth-child(3) { display: none; }
        .foot { padding: 14px 18px; }
    }
    @page { margin: 12mm; }
    @media print { body { background: #fff; } .actions { display: none; } .sheet { margin: 0; max-width: none; border-radius: 0; box-shadow: none; } .band { border-radius: 12px; } }
</style>
</head>
<body>
<div class="actions">
    <a class="btn btn--plain" href="<?= e($isStaff ? url('admin/orders/view?id=' . $order['id']) : order_url($order)) ?>">Back to the order</a>
    <button class="btn" type="button" onclick="window.print()">Print or save as PDF</button>
</div>
<div class="sheet">
    <header class="band">
        <div class="shop">
            <img src="<?= e(url('assets/img/logo-email.png')) ?>" alt="">
            <div>
                <strong><?= e(setting('store_name')) ?></strong>
                <?php if (setting('store_abn') !== ''): ?><span>ABN <?= e(setting('store_abn')) ?></span><?php endif; ?>
                <span><?= e(store_address_line()) ?></span>
            </div>
        </div>
        <div class="doc">
            <h1><?= e($docTitle) ?></h1>
            <span>No. <strong><?= e($order['order_number']) ?></strong></span>
            <span>Issued <?= e(date('j F Y', strtotime($order['created_at']))) ?></span>
        </div>
    </header>
    <?php if ($order['order_status'] === 'cancelled'): ?>
        <div class="stamp stamp--due">CANCELLED</div>
    <?php elseif ($balance <= 0.004 && ($payments || $order['payment_status'] === 'paid')): ?>
        <div class="stamp">PAID</div>
    <?php endif; ?>

    <div class="body">
        <div class="cards">
            <div class="info">
                <p class="label">Billed to</p>
                <strong><?= e($order['customer_name']) ?></strong>
                <?php if ($order['customer_email'] !== ''): ?><span><?= e($order['customer_email']) ?></span><?php endif; ?>
                <span><?= e($order['customer_phone']) ?></span>
            </div>
            <div class="info">
                <p class="label"><?= is_delivery($order) ? 'Delivery' : 'Pickup' ?></p>
                <strong><?= e(date('l j F Y', strtotime($order['pickup_date']))) ?></strong>
                <span><?= e($order['pickup_time'] ? format_time($order['pickup_time']) : 'Time to be confirmed') ?></span>
                <span><?= e(order_address($order)) ?></span>
            </div>
            <div class="info">
                <p class="label">Payment</p>
                <strong><?= e(payment_method_label($order['payment_method'])) ?></strong>
                <span><?= e(PAYMENT_STATUSES[$order['payment_status']] ?? $order['payment_status']) ?></span>
            </div>
        </div>

        <table class="items">
            <thead><tr><th>Description</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Amount</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><strong><?= e($item['product_name']) ?></strong><?php if (order_item_detail($item) !== ''): ?><small><?= e(order_item_detail($item)) ?></small><?php endif; ?></td>
                    <td class="num"><?= (int) $item['quantity'] ?></td>
                    <td class="num"><?= e(money($item['unit_price'])) ?></td>
                    <td class="num"><strong><?= e(money($item['line_total'])) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="bottom">
            <div class="thanks">
                <strong>Thank you for choosing <?= e(setting('store_short_name')) ?>!</strong>
                <?= $gst ? 'The total price includes GST. ' : '' ?>Questions about this <?= e(strtolower($docTitle)) ?>? Call <?= e(setting('store_phone')) ?> or email <?= e(setting('store_email')) ?>.
            </div>
            <div class="totals"><table>
                <tr><td>Subtotal</td><td class="num"><?= e(money($order['subtotal'])) ?></td></tr>
                <?php if ((float) $order['discount'] > 0): ?><tr><td>Discount<?= $order['coupon_code'] ? ' (' . e((string) $order['coupon_code']) . ')' : '' ?></td><td class="num">-<?= e(money($order['discount'])) ?></td></tr><?php endif; ?>
                <?php if ((float) $order['delivery_fee'] > 0): ?><tr><td>Delivery</td><td class="num"><?= e(money($order['delivery_fee'])) ?></td></tr><?php endif; ?>
                <tr class="grand"><td>Total<?= $gst ? ' (incl. GST)' : '' ?></td><td class="num"><?= e(money($order['total'])) ?></td></tr>
                <?php if ($gst): ?><tr><td>GST included</td><td class="num"><?= e(money($gstAmount)) ?></td></tr><?php endif; ?>
                <?php foreach ($payments as $p): ?>
                    <tr class="paid"><td><?= $p['kind'] === 'refund' ? 'Refunded' : 'Paid' ?> <?= e(date('j M Y', strtotime($p['created_at']))) ?> &middot; <?= e(payment_method_label($p['method'])) ?></td><td class="num"><?= $p['kind'] === 'refund' ? '+' : '-' ?><?= e(money($p['amount'])) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$payments && $order['payment_status'] === 'paid'): ?><tr class="paid"><td>Paid</td><td class="num">-<?= e(money($order['total'])) ?></td></tr><?php endif; ?>
                <?php if ($order['order_status'] !== 'cancelled'): ?>
                    <?php if ($balance > 0.004): ?>
                        <tr class="owing"><td>Balance due</td><td class="num"><?= e(money($balance)) ?></td></tr>
                    <?php elseif ($payments || $order['payment_status'] === 'paid'): ?>
                        <tr class="settled"><td>Balance</td><td class="num"><?= e(money(0)) ?></td></tr>
                    <?php endif; ?>
                <?php endif; ?>
            </table></div>
        </div>
    </div>
    <footer class="foot">
        <span><?= e(setting('store_name')) ?><?= setting('store_abn') !== '' ? ' &middot; ABN ' . e(setting('store_abn')) : '' ?></span>
        <span><?= e(setting('store_phone')) ?> &middot; <?= e(setting('store_email')) ?> &middot; <?= e(preg_replace('#^https?://#', '', rtrim(url(), '/'))) ?></span>
    </footer>
</div>
</body>
</html>
