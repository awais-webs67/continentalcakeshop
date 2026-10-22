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
    // The event is signed by Stripe and the order number was set by us when the session was made,
    // so a payment on an older session of the same order (two "Pay now" tabs) still counts.
    if ($order) {
        if (($object['payment_status'] ?? '') === 'paid') {
            mark_order_paid($order, (string) ($object['payment_intent'] ?? $object['id']), isset($object['amount_total']) ? $object['amount_total'] / 100 : null);
        } elseif ($type === 'checkout.session.async_payment_failed' && in_array($order['payment_status'], ['pending', 'awaiting_verification'], true)) {
            db_run("UPDATE orders SET payment_status = 'failed' WHERE id = ?", [$order['id']]);
            log_order_event((int) $order['id'], 'payment_failed', 'Stripe reported that the card payment failed.');
        }
    }
}

// A refund made in the Stripe dashboard is reflected on the order too.
// Full and partial refunds both arrive as charge.refunded.
if ($type === 'charge.refunded' && !empty($object['id'])) {
    $intent = (string) ($object['payment_intent'] ?? '');
    $orderId = $intent !== '' ? db_value("SELECT order_id FROM payments WHERE reference = ? AND kind = 'payment'", [$intent]) : null;
    $order = $orderId ? get_order((int) $orderId)
        : (get_order_by_number((string) ($object['metadata']['order_number'] ?? ''))
            ?? ($intent !== '' ? db_one('SELECT * FROM orders WHERE payment_reference = ?', [$intent]) : null));
    if ($order) {
        try {
            stripe_sync_refunds($order, (string) $object['id']);
        } catch (Throwable $e) {
            error_log('Stripe refund sync failed: ' . $e->getMessage());
            http_response_code(500);
            exit('retry');
        }
    }
}

http_response_code(200);
echo 'ok';
