<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// Stripe calls this URL after a Checkout payment, so orders are marked paid even if the
// customer closes the browser before returning to the site.
// Add https://your-domain/stripe-webhook in Stripe > Developers > Webhooks for the
// "checkout.session.completed", "checkout.session.async_payment_succeeded",
// "checkout.session.async_payment_failed" and "charge.refunded" events, then save the
// signing secret in Admin > Settings > Payments.

$payload = (string) file_get_contents('php://input');
$secret = setting('stripe_webhook_secret');

if ($secret === '' || !stripe_verify_webhook($payload, $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '', $secret)) {
    http_response_code(400);
    exit('Invalid signature');
}

$event = json_decode($payload, true) ?: [];
$type = (string) ($event['type'] ?? '');
$object = $event['data']['object'] ?? [];

if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded', 'checkout.session.async_payment_failed'], true)) {
    $order = get_order_by_number((string) ($object['client_reference_id'] ?? ''));
    if ($order && $order['stripe_session_id'] === ($object['id'] ?? null)) {
        if (($object['payment_status'] ?? '') === 'paid') {
            mark_order_paid($order, (string) ($object['payment_intent'] ?? $object['id']));
        } elseif ($type === 'checkout.session.async_payment_failed' && $order['payment_status'] !== 'paid') {
            db_run("UPDATE orders SET payment_status = 'failed' WHERE id = ?", [$order['id']]);
            log_order_event((int) $order['id'], 'payment_failed', 'Stripe reported that the card payment failed.');
        }
    }
}

// A refund made in the Stripe dashboard is reflected on the order too.
if ($type === 'charge.refunded' && !empty($object['refunded'])) {
    $order = get_order_by_number((string) ($object['metadata']['order_number'] ?? ''))
        ?? (!empty($object['payment_intent']) ? db_one('SELECT * FROM orders WHERE payment_reference = ?', [(string) $object['payment_intent']]) : null);
    if ($order && $order['payment_status'] !== 'refunded') {
        db_run("UPDATE orders SET payment_status = 'refunded' WHERE id = ?", [$order['id']]);
        log_order_event((int) $order['id'], 'refunded', 'Refunded in the Stripe dashboard.');
    }
}

http_response_code(200);
echo 'ok';
