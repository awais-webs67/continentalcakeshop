<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// Stripe calls this URL after a Checkout payment, so orders are marked paid even if the
// customer closes the browser before returning to the site.
// Add https://your-domain/stripe-webhook in Stripe > Developers > Webhooks for the
// "checkout.session.completed" event and save the signing secret in settings.

$payload = (string) file_get_contents('php://input');
$secret = setting('stripe_webhook_secret');

if ($secret === '' || !stripe_verify_webhook($payload, $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '', $secret)) {
    http_response_code(400);
    exit('Invalid signature');
}

$event = json_decode($payload, true);
if (($event['type'] ?? '') === 'checkout.session.completed') {
    $session = $event['data']['object'] ?? [];
    $order = get_order_by_number((string) ($session['client_reference_id'] ?? ''));
    if ($order && ($session['payment_status'] ?? '') === 'paid' && $order['stripe_session_id'] === ($session['id'] ?? null)) {
        mark_order_paid($order, (string) ($session['payment_intent'] ?? $session['id']));
    }
}

http_response_code(200);
echo 'ok';
