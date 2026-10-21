<?php
declare(strict_types=1);

// Quick checks for the rules the shop depends on: prices, coupons, delivery areas,
// pickup times, safe HTML, Stripe signatures, SEO helpers and payments.
//
//   php tests/run.php
//
// Uses the database in config/config.php. The payments test makes one order called
// "Test Runner" and deletes it again at the end. Best run on a copy of the live database.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/src/bootstrap.php';

$passed = 0;
$failed = [];
function check(string $name, bool $ok, string $detail = ''): void
{
    global $passed, $failed;
    if ($ok) {
        $passed++;
        echo '.';
    } else {
        $failed[] = $name . ($detail !== '' ? ' (' . $detail . ')' : '');
        echo 'F';
    }
}
function same(string $name, mixed $expected, mixed $actual): void
{
    check($name, $expected === $actual, 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
}

// --- Helpers ---------------------------------------------------------------------------------
same('money formats', '$1,234.50', money(1234.5));
check('Australian mobile accepted', valid_au_phone('0412 345 678'));
check('+61 number accepted', valid_au_phone('+61 2 6293 9578'));
check('short number refused', !valid_au_phone('12345'));
same('notice in days', '2 days', notice_label(48));
same('notice in hours', '6 hours', notice_label(6));

// --- Safe HTML -------------------------------------------------------------------------------
$dirty = '<p onclick="x()">Hi<script>alert(1)</script></p><a href="javascript:alert(1)">x</a><img src="data:x" onerror="y"><p style="color:red;text-align:center">c</p>';
$clean = clean_html($dirty);
check('scripts removed', !str_contains($clean, 'script'));
check('event attributes removed', !preg_match('/on(click|error)/i', $clean));
check('javascript links removed', !str_contains($clean, 'javascript:'));
check('data images removed', !str_contains($clean, 'data:'));
check('text alignment kept', str_contains($clean, 'style="text-align: center"') && !str_contains($clean, 'color:red'));

// --- Coupons and totals ----------------------------------------------------------------------
$percent = ['discount_type' => 'percent', 'discount_value' => '10'];
$fixed = ['discount_type' => 'fixed', 'discount_value' => '50'];
same('10% of $80', 8.0, coupon_discount($percent, 80));
same('fixed discount never above the subtotal', 30.0, coupon_discount($fixed, 30));
$totals = order_totals(100, 'pickup', $percent);
same('pickup total with coupon', 90.0, $totals['total']);
same('pickup has no delivery fee', 0.0, $totals['delivery_fee']);

// --- Delivery areas --------------------------------------------------------------------------
$saved = setting('delivery_postcodes');
setting_save('delivery_postcodes', '2600-2620, 2900-2914');
check('postcode in range', delivery_postcode_allowed('2905'));
check('postcode outside range', !delivery_postcode_allowed('2000'));
check('bad postcode refused', !delivery_postcode_allowed('29a5'));
setting_save('delivery_postcodes', $saved);

// --- Opening hours ---------------------------------------------------------------------------
$hours = opening_hours();
$openDay = null;
for ($i = 1; $i <= 14 && !$openDay; $i++) {
    $day = new DateTimeImmutable("+{$i} days");
    if ($hours[day_key($day)] && !is_blocked_date($day->format('Y-m-d'))) {
        $openDay = $day;
    }
}
if ($openDay) {
    [$open, $close] = $hours[day_key($openDay)];
    $mid = new DateTimeImmutable($openDay->format('Y-m-d') . ' ' . $open);
    check('open during opening hours', today_status($mid->modify('+1 hour'))['open']);
    check('closed before opening', !today_status($mid->modify('-1 hour'))['open']);
    $slots = pickup_slots($openDay->format('Y-m-d'), 0);
    check('pickup times inside opening hours', $slots && $slots[0] >= substr($open, 0, 5) && end($slots) < substr($close, 0, 5), implode(',', array_slice($slots, 0, 3)));
}
same('no pickup times on a bad date', [], pickup_slots('2026-02-30', 0));

// --- SQL splitting for migrations ------------------------------------------------------------
same('semicolons inside quotes', 2, count(split_sql("INSERT INTO t VALUES ('a;b');\n-- comment;\nSELECT 1;")));

// --- Stripe webhook signature ----------------------------------------------------------------
$payload = '{"id":"evt_1"}';
$time = time();
$sig = hash_hmac('sha256', $time . '.' . $payload, 'whsec_test');
check('valid Stripe signature', stripe_verify_webhook($payload, "t={$time},v1={$sig}", 'whsec_test'));
check('wrong secret refused', !stripe_verify_webhook($payload, "t={$time},v1={$sig}", 'whsec_other'));
check('old signature refused', !stripe_verify_webhook($payload, 't=' . ($time - 3600) . ",v1={$sig}", 'whsec_test'));

// --- SEO -------------------------------------------------------------------------------------
check('automatic titles fit in 60 characters', mb_strlen(product_default_title(['name' => 'Double Chocolate Strawberry Torte With Cream'])) <= 60);
$checks = product_seo_checks(['name' => 'Red Velvet Cake', 'slug' => 'red-velvet-cake', 'focus_keyword' => 'red velvet cake canberra',
    'meta_title' => 'Red Velvet Cake Canberra | Order Online', 'meta_description' => '', 'description' => '', 'short_description' => '', 'image' => 'x.webp'], 0, []);
check('keyword found in title', $checks[1][1]);
check('keyword words found in web address', $checks[3][1]);
check('old addresses redirect', legacy_redirect_target('about-us') === url('about'));

// --- Payments ledger (the test order is deleted afterwards) ----------------------------------
try {
    $order = create_order([
        'channel' => 'phone', 'name' => 'Test Runner', 'email' => '', 'phone' => '0400000000', 'fulfilment' => 'pickup',
        'pickup_date' => date('Y-m-d', strtotime('+3 days')), 'pickup_time' => '10:00', 'payment_method' => 'cash',
    ], [[
        'name' => 'Test cake', 'size_label' => 'Midi', 'options' => 'Eggless', 'price' => 50.0, 'qty' => 2, 'line_total' => 100.0,
    ]], null, ['subtotal' => 100.0, 'discount' => 0.0, 'delivery_fee' => 0.0, 'total' => 100.0]);
    record_payment($order, 40, 'cash', 'test-deposit-' . $order['id']);
    $order = get_order((int) $order['id']);
    same('deposit makes the order part paid', 'partially_paid', $order['payment_status']);
    same('balance after deposit', 60.0, order_balance($order));
    check('same payment is not counted twice', !record_payment($order, 40, 'cash', 'test-deposit-' . $order['id']));
    record_payment($order, 60, 'eftpos');
    same('fully paid', 'paid', get_order((int) $order['id'])['payment_status']);
    record_refund(get_order((int) $order['id']), 100, 'cash');
    same('full refund', 'refunded', get_order((int) $order['id'])['payment_status']);
    same('options saved on the line', 'Eggless', get_order_items((int) $order['id'])[0]['options']);
} finally {
    if (isset($order['id'])) {
        db_run('DELETE FROM orders WHERE id = ? AND customer_name = ?', [$order['id'], 'Test Runner']);
    }
}

echo "\n\n{$passed} passed";
if ($failed) {
    echo ', ' . count($failed) . " failed:\n - " . implode("\n - ", $failed) . "\n";
    exit(1);
}
echo ", all good.\n";
