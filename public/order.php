<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$order = get_order_by_number(input('number'));
if (!$order || !hash_equals($order['access_token'], input('t'))) {
    not_found();
}

// Customer chose to retry a card payment that was cancelled or failed.
if (is_post() && input('action') === 'pay' && $order['payment_method'] === PAYMENT_STRIPE && $order['payment_status'] !== 'paid') {
    verify_csrf();
    try {
        redirect(stripe_create_checkout($order, get_order_items((int) $order['id'])));
    } catch (Throwable $e) {
        error_log($e->getMessage());
        flash('error', 'We could not open the payment page. Please try again or call us.');
        redirect(order_url($order));
    }
}

if ($order['payment_method'] === PAYMENT_STRIPE && input('session_id') !== '') {
    try {
        stripe_confirm_session($order, input('session_id'));
    } catch (Throwable $e) {
        error_log($e->getMessage());
    }
    $order = get_order((int) $order['id']);
}
if (input('cancelled') === '1' && $order['payment_status'] !== 'paid') {
    flash('info', 'Card payment was cancelled. Your order is saved, so you can pay below whenever you are ready.');
    redirect(order_url($order));
}

$items = get_order_items((int) $order['id']);
$paid = $order['payment_status'] === 'paid';
$awaitingCard = $order['payment_method'] === PAYMENT_STRIPE && !$paid;

$page = [
    'title'   => 'Order ' . $order['order_number'] . ' | ' . setting('store_short_name'),
    'noindex' => true,
];
render('header', compact('page'));
?>

<section class="section">
    <div class="container container--narrow">
        <div class="order-hero <?= $awaitingCard ? 'order-hero--pending' : '' ?>">
            <span class="order-hero__icon"><?= icon($awaitingCard ? 'card' : 'check', '', 32) ?></span>
            <?php if ($awaitingCard): ?>
                <h1>Your order is saved</h1>
                <p>Complete the card payment to confirm order <strong><?= e($order['order_number']) ?></strong>.</p>
                <form method="post" action="<?= e(order_url($order)) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="pay">
                    <button class="btn btn--primary btn--lg" type="submit"><?= icon('card', '', 20) ?> Pay <?= e(money($order['total'])) ?> now</button>
                </form>
            <?php else: ?>
                <h1>Thank you, <?= e(strtok($order['customer_name'], ' ')) ?>!</h1>
                <p>Your order number is <strong><?= e($order['order_number']) ?></strong>. We have sent the details to <?= e($order['customer_email']) ?>.</p>
                <?php if ($order['payment_status'] === 'awaiting_verification'): ?>
                    <p class="order-hero__note"><?= icon('info', '', 18) ?> We have received your payment screenshot and will confirm your order once we have checked it.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="order-grid">
            <div class="order-box">
                <h2><?= $order['fulfilment'] === 'delivery' ? 'Delivery' : 'Pickup' ?></h2>
                <p class="order-box__big"><?= icon('calendar', '', 20) ?> <?= e(order_when($order)) ?></p>
                <?php if (is_delivery($order)): ?>
                    <p><?= icon('pin', '', 18) ?> <?= e(order_address($order)) ?></p>
                    <?php if (!$order['pickup_time']): ?>
                        <p class="order-box__note"><?= icon('clock', '', 18) ?> We will email you the delivery time before the day<?= ($order['delivery_window'] ?? 'any') !== 'any' && $order['delivery_window'] ? ', keeping to your preferred time (' . e(delivery_window_label($order['delivery_window'])) . ') where we can' : '' ?>.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p><?= icon('store', '', 18) ?> <?= e(store_address_line()) ?></p>
                    <a class="link-arrow" href="<?= e(store_maps_url()) ?>" target="_blank" rel="noopener">Get directions <?= icon('arrow', '', 16) ?></a>
                <?php endif; ?>
            </div>
            <div class="order-box">
                <h2>Status</h2>
                <dl class="order-status">
                    <div><dt>Order</dt><dd><span class="status status--<?= e($order['order_status']) ?>"><?= e(order_status_label($order)) ?></span></dd></div>
                    <div><dt>Payment</dt><dd><span class="status status--<?= e($order['payment_status']) ?>"><?= e(PAYMENT_STATUSES[$order['payment_status']]) ?></span></dd></div>
                    <div><dt>Method</dt><dd><?= e(payment_method_label($order['payment_method'])) ?></dd></div>
                </dl>
            </div>
        </div>

        <div class="order-box">
            <h2>Items</h2>
            <ul class="order-items">
                <?php foreach ($items as $item): ?>
                    <li>
                        <div>
                            <strong><?= (int) $item['quantity'] ?> &times; <?= e($item['product_name']) ?></strong>
                            <span><?= e($item['size_label']) ?><?= $item['cake_message'] ? ' &middot; Message: &ldquo;' . e($item['cake_message']) . '&rdquo;' : '' ?></span>
                        </div>
                        <span><?= e(money($item['line_total'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <dl class="summary-lines">
                <div><dt>Subtotal</dt><dd><?= e(money($order['subtotal'])) ?></dd></div>
                <?php if ((float) $order['discount'] > 0): ?>
                    <div><dt>Discount (<?= e((string) $order['coupon_code']) ?>)</dt><dd>-<?= e(money($order['discount'])) ?></dd></div>
                <?php endif; ?>
                <?php if (is_delivery($order)): ?>
                    <div><dt>Delivery</dt><dd><?= (float) $order['delivery_fee'] > 0 ? e(money($order['delivery_fee'])) : 'Free' ?></dd></div>
                <?php endif; ?>
            </dl>
            <div class="summary-total"><span>Total</span><strong><?= e(money($order['total'])) ?></strong></div>
            <?php if (setting_bool('gst_registered')): ?><p class="field-help">Includes GST of <?= e(money(round((float) $order['total'] / 11, 2))) ?><?= setting('store_abn') !== '' ? ' &middot; ABN ' . e(setting('store_abn')) : '' ?></p><?php endif; ?>
        </div>

        <div class="order-help">
            <p>Need to change something or have a question?</p>
            <div class="order-help__actions">
                <a class="btn btn--primary" href="<?= e(thread_url(order_thread($order))) ?>"><?= icon('mail', '', 18) ?> Message us about this order</a>
                <a class="btn btn--outline" href="tel:<?= e(setting('store_phone_e164')) ?>"><?= icon('phone', '', 18) ?> <?= e(setting('store_phone')) ?></a>
            </div>
        </div>
    </div>
</section>

<?php render('footer'); ?>
