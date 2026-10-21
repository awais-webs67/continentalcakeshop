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
    :root { --brand: #1E9B82; --ink: #1B1B1F; --muted: #66666E; --line: #E3EAE8; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #F3F6F5; color: var(--ink); font: 14px/1.5 Arial, Helvetica, sans-serif; }
    .sheet { max-width: 780px; margin: 24px auto; padding: 40px; background: #fff; border-radius: 12px; box-shadow: 0 6px 24px rgba(0, 0, 0, .06); }
    .top { display: flex; justify-content: space-between; gap: 24px; flex-wrap: wrap; padding-bottom: 20px; border-bottom: 3px solid var(--brand); }
    .shop img { width: 72px; height: 72px; }
    .shop strong { display: block; font-size: 17px; margin-top: 6px; }
    .shop span, .meta span { display: block; color: var(--muted); }
    h1 { margin: 0 0 6px; font-size: 26px; text-transform: uppercase; letter-spacing: .04em; color: var(--brand); text-align: right; }
    .meta { text-align: right; }
    .meta strong { color: var(--ink); }
    .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin: 24px 0; }
    .label { margin: 0 0 4px; font-size: 11px; font-weight: bold; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
    table { width: 100%; border-collapse: collapse; }
    th { padding: 8px 6px; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); text-align: left; border-bottom: 1px solid var(--line); }
    td { padding: 10px 6px; border-bottom: 1px solid var(--line); vertical-align: top; }
    td small { display: block; color: var(--muted); }
    .num { text-align: right; white-space: nowrap; }
    .totals { width: 320px; margin: 16px 0 0 auto; }
    .totals td { border: 0; padding: 4px 6px; }
    .totals .grand td { padding-top: 10px; border-top: 2px solid var(--ink); font-size: 17px; font-weight: bold; }
    .totals .owing td { color: #8A5A00; font-weight: bold; }
    .note { margin-top: 28px; padding-top: 16px; border-top: 1px solid var(--line); color: var(--muted); font-size: 12px; }
    .actions { max-width: 780px; margin: 0 auto; padding: 0 0 24px; display: flex; gap: 10px; justify-content: flex-end; }
    .btn { padding: 10px 18px; border: 0; border-radius: 999px; background: var(--brand); color: #fff; font-weight: bold; text-decoration: none; cursor: pointer; font-size: 14px; }
    .btn--plain { background: #fff; color: var(--ink); border: 1px solid var(--line); }
    @media (max-width: 600px) { .sheet { margin: 0; padding: 22px; border-radius: 0; } .parties { grid-template-columns: 1fr; } h1, .meta { text-align: left; } .totals { width: 100%; } }
    @media print { body { background: #fff; } .sheet { margin: 0; padding: 0; box-shadow: none; } .actions { display: none; } }
</style>
</head>
<body>
<div class="sheet">
    <div class="top">
        <div class="shop">
            <img src="<?= e(url('assets/img/logo-email.png')) ?>" alt="">
            <strong><?= e(setting('store_name')) ?></strong>
            <?php if (setting('store_abn') !== ''): ?><span>ABN <?= e(setting('store_abn')) ?></span><?php endif; ?>
            <span><?= e(store_address_line()) ?></span>
            <span><?= e(setting('store_phone')) ?> &middot; <?= e(setting('store_email')) ?></span>
        </div>
        <div class="meta">
            <h1><?= e($docTitle) ?></h1>
            <span>Number <strong><?= e($order['order_number']) ?></strong></span>
            <span>Date of issue <strong><?= e(date('j F Y', strtotime($order['created_at']))) ?></strong></span>
            <span><?= is_delivery($order) ? 'Delivery' : 'Pickup' ?> <strong><?= e(date('j F Y', strtotime($order['pickup_date']))) ?></strong></span>
        </div>
    </div>

    <div class="parties">
        <div>
            <p class="label">Customer</p>
            <strong><?= e($order['customer_name']) ?></strong><br>
            <?php if ($order['customer_email'] !== ''): ?><?= e($order['customer_email']) ?><br><?php endif; ?>
            <?= e($order['customer_phone']) ?>
            <?php if (is_delivery($order)): ?><br><?= e(order_address($order)) ?><?php endif; ?>
        </div>
        <div>
            <p class="label">Payment</p>
            <?= e(payment_method_label($order['payment_method'])) ?><br>
            <?= e(PAYMENT_STATUSES[$order['payment_status']] ?? $order['payment_status']) ?>
        </div>
    </div>

    <table>
        <thead><tr><th>Description</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Amount</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['product_name']) ?><?php if (order_item_detail($item) !== ''): ?><small><?= e(order_item_detail($item)) ?></small><?php endif; ?></td>
                <td class="num"><?= (int) $item['quantity'] ?></td>
                <td class="num"><?= e(money($item['unit_price'])) ?></td>
                <td class="num"><?= e(money($item['line_total'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num"><?= e(money($order['subtotal'])) ?></td></tr>
        <?php if ((float) $order['discount'] > 0): ?><tr><td>Discount<?= $order['coupon_code'] ? ' (' . e((string) $order['coupon_code']) . ')' : '' ?></td><td class="num">-<?= e(money($order['discount'])) ?></td></tr><?php endif; ?>
        <?php if ((float) $order['delivery_fee'] > 0): ?><tr><td>Delivery</td><td class="num"><?= e(money($order['delivery_fee'])) ?></td></tr><?php endif; ?>
        <tr class="grand"><td>Total<?= $gst ? ' (incl. GST)' : '' ?></td><td class="num"><?= e(money($order['total'])) ?></td></tr>
        <?php if ($gst): ?><tr><td>GST included</td><td class="num"><?= e(money($gstAmount)) ?></td></tr><?php endif; ?>
        <?php foreach ($payments as $p): ?>
            <tr><td><?= $p['kind'] === 'refund' ? 'Refunded' : 'Paid' ?> <?= e(date('j M Y', strtotime($p['created_at']))) ?> (<?= e(payment_method_label($p['method'])) ?>)</td><td class="num"><?= $p['kind'] === 'refund' ? '' : '-' ?><?= e(money($p['amount'])) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$payments && $order['payment_status'] === 'paid'): ?><tr><td>Paid</td><td class="num">-<?= e(money($order['total'])) ?></td></tr><?php endif; ?>
        <?php if ($payments || $order['payment_status'] === 'paid'): ?>
            <tr class="<?= $balance > 0 ? 'owing' : '' ?>"><td><?= $balance > 0 ? 'Balance due' : 'Balance' ?></td><td class="num"><?= e(money(max(0, $balance))) ?></td></tr>
        <?php endif; ?>
    </table>

    <p class="note">
        <?= $gst ? 'The total price includes GST. ' : '' ?>Thank you for choosing <?= e(setting('store_short_name')) ?>.
        Questions about this <?= e(strtolower($docTitle)) ?>? Call <?= e(setting('store_phone')) ?> or email <?= e(setting('store_email')) ?>.
    </p>
</div>
<div class="actions">
    <a class="btn btn--plain" href="<?= e($isStaff ? url('admin/orders/view?id=' . $order['id']) : order_url($order)) ?>">Back to the order</a>
    <button class="btn" type="button" onclick="window.print()">Print or save as PDF</button>
</div>
</body>
</html>
