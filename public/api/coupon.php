<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

// Applies or removes a coupon on the checkout page without reloading it.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!is_post() || !hash_equals(csrf_token(), (string) ($_POST['_token'] ?? ''))) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Your session expired. Please refresh the page.']);
    exit;
}

$subtotal = cart_subtotal(cart_items());
if (input('action') === 'remove') {
    remove_coupon();
    echo json_encode(['ok' => true, 'removed' => true]);
    exit;
}

$error = apply_coupon(input('code'), $subtotal);
$coupon = $error ? null : find_coupon(input('code'));
echo json_encode($error ? ['ok' => false, 'message' => $error] : [
    'ok'       => true,
    'code'     => $coupon['code'],
    'label'    => coupon_value_label($coupon),
    'discount' => coupon_discount($coupon, $subtotal),
]);
