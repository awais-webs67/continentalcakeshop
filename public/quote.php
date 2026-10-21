<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// A custom cake quote the customer opens from the "Accept and pay" button in the quote email.
// Paying a deposit (or the full price) turns the request into an order.

$request = get_request_by_quote_token(input('token'));
if (!$request || (float) $request['quoted_price'] <= 0) {
    not_found();
}
$self = quote_url($request);
$order = $request['order_id'] ? get_order((int) $request['order_id']) : null;
// Once something is paid (or a screenshot is waiting to be checked) the order page takes over.
if ($order && ((float) $order['amount_paid'] > 0 || $order['payment_status'] === 'awaiting_verification')) {
    redirect(order_url($order));
}
$methods = available_payment_methods();
$price = round((float) $request['quoted_price'], 2);
$deposit = quote_deposit($request);
$hasDeposit = $deposit < $price - 0.004;
$delivery = ($request['fulfilment'] ?? 'pickup') === 'delivery';
$closed = $request['status'] === 'closed';

if (is_post() && !$closed) {
    verify_csrf();
    $method = $methods[input('payment_method')] ?? null;
    $amount = $hasDeposit && input('pay') === 'deposit' ? $deposit : $price;
    $errors = [];
    $address = [
        'delivery_address'  => str_limit(trim(input('delivery_address')), 255),
        'delivery_suburb'   => str_limit(trim(input('delivery_suburb')), 80),
        'delivery_postcode' => preg_replace('/\D/', '', input('delivery_postcode')),
    ];
    if (!$method) {
        $errors['payment_method'] = 'Please choose how you would like to pay.';
    } elseif ($method['needs_proof'] && !has_upload('payment_proof') && !async_upload(input('payment_proof_token'), 'payment')) {
        $errors['payment_proof'] = 'Please upload a screenshot of your payment.';
    }
    if ($delivery && !$order) {
        if ($address['delivery_address'] === '' || $address['delivery_suburb'] === '') {
            $errors['delivery_address'] = 'Please enter the delivery address and suburb.';
        }
        if (!preg_match('/^\d{4}$/', $address['delivery_postcode'])) {
            $errors['delivery_postcode'] = 'Please enter the 4 digit postcode.';
        }
    }
    if (input('agree') !== '1') {
        $errors['agree'] = 'Please confirm that the quote is correct.';
    }
    $proof = null;
    if (!$errors && $method['needs_proof']) {
        if ($uploaded = async_upload(input('payment_proof_token'), 'payment')) {
            $proof = $uploaded['path'];
        } else {
            [$proof, $uploadError] = store_private_upload('payment_proof', 'payments', UPLOAD_RECEIPT_TYPES);
            if ($uploadError) {
                $errors['payment_proof'] = $uploadError;
            }
        }
    }
    if ($errors) {
        fail_form($errors, $address + ['payment_method' => input('payment_method'), 'pay' => input('pay'), 'payment_proof_token' => input('payment_proof_token')], 'quote/' . $request['quote_token']);
    }

    $order = order_for_quote($request, $address + ['payment_method' => input('payment_method'), 'payment_proof' => $proof]);
    forget_async_upload(input('payment_proof_token'));
    forget_input();
    if ($proof !== null) {
        // PayID or bank: the shop checks the screenshot and records the payment.
        db_run("UPDATE orders SET payment_method = ?, payment_proof = ?, payment_status = 'awaiting_verification', payment_reference = ?, admin_notes = CONCAT_WS('\n', admin_notes, ?) WHERE id = ?", [
            input('payment_method'), $proof, str_limit(input('payment_reference'), 190) ?: null,
            'Customer says they paid ' . money($amount) . ($amount < $price ? ' (deposit)' : ' (full price)') . ' by ' . payment_method_label(input('payment_method')) . '.', $order['id'],
        ]);
        log_order_event((int) $order['id'], 'proof', 'Payment screenshot uploaded for ' . money($amount) . '.');
        send_order_emails(get_order((int) $order['id']));
        flash('success', 'Thank you! We have your payment screenshot and will confirm your cake as soon as we have checked it.');
        redirect(order_url(get_order((int) $order['id'])));
    }
    db_run('UPDATE orders SET payment_method = ? WHERE id = ?', [PAYMENT_STRIPE, $order['id']]);
    try {
        redirect(stripe_create_checkout(get_order((int) $order['id']), [], $amount, ($amount < $price ? 'Deposit for ' : '') . $request['occasion'] . ' cake on ' . date('j M Y', strtotime($request['event_date']))));
    } catch (Throwable $e) {
        error_log($e->getMessage());
        flash('error', 'We could not open the card payment page. Please try again or call us.');
        redirect('quote/' . $request['quote_token']);
    }
}

$errors = take_form_errors();
$selectedMethod = old('payment_method', (string) array_key_first($methods));
$payChoice = old('pay', $hasDeposit ? 'deposit' : 'full');
$page = ['title' => 'Your cake quote | ' . setting('store_short_name'), 'noindex' => true];
render('header', compact('page'));
?>

<section class="page-hero page-hero--slim">
    <div class="container">
        <p class="kicker">Custom cake quote</p>
        <h1 class="page-title"><?= e($request['occasion']) ?> cake for <?= e(date('l j F Y', strtotime($request['event_date']))) ?></h1>
    </div>
</section>

<section class="section section--compact">
    <div class="container">
        <?php if ($closed): ?>
            <div class="alert alert--info">This quote is no longer open. Please call us on <a href="tel:<?= e(setting('store_phone_e164')) ?>"><?= e(setting('store_phone')) ?></a> and we will help.</div>
        <?php elseif (!$methods): ?>
            <div class="alert alert--info">Online payment is not available right now. Please call us on <a href="tel:<?= e(setting('store_phone_e164')) ?>"><?= e(setting('store_phone')) ?></a> to confirm your cake.</div>
        <?php else: ?>
        <form class="checkout-layout" method="post" action="<?= e($self) ?>" enctype="multipart/form-data" data-checkout novalidate>
            <?= csrf_field() ?>
            <div class="checkout-main">
                <?php if ($hasDeposit): ?>
                <fieldset class="checkout-step">
                    <legend class="checkout-step__title"><span>1</span> How much would you like to pay now?</legend>
                    <div class="option-cards">
                        <label class="option-card">
                            <input type="radio" name="pay" value="deposit" data-quote-amount="<?= e(money($deposit)) ?>" <?= $payChoice === 'deposit' ? 'checked' : '' ?>>
                            <span class="option-card__box"><?= icon('calendar', '', 22) ?><span><strong>Deposit <?= e(money($deposit)) ?></strong><span>Pay the rest (<?= e(money($price - $deposit)) ?>) before <?= $delivery ? 'delivery' : 'pickup' ?>.</span></span></span>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="pay" value="full" data-quote-amount="<?= e(money($price)) ?>" <?= $payChoice === 'full' ? 'checked' : '' ?>>
                            <span class="option-card__box"><?= icon('check', '', 22) ?><span><strong>Full price <?= e(money($price)) ?></strong><span>Nothing more to pay later.</span></span></span>
                        </label>
                    </div>
                </fieldset>
                <?php endif; ?>

                <?php if ($delivery && !$order): ?>
                <fieldset class="checkout-step">
                    <legend class="checkout-step__title"><span><?= $hasDeposit ? 2 : 1 ?></span> Delivery address</legend>
                    <div class="field">
                        <label class="field-label" for="delivery_address">Street address</label>
                        <input class="input" id="delivery_address" name="delivery_address" autocomplete="street-address" value="<?= e(old('delivery_address')) ?>" <?= invalid_attr($errors, 'delivery_address') ?>>
                        <?= field_error($errors, 'delivery_address') ?>
                    </div>
                    <div class="field-row">
                        <div class="field"><label class="field-label" for="delivery_suburb">Suburb</label><input class="input" id="delivery_suburb" name="delivery_suburb" value="<?= e(old('delivery_suburb')) ?>"></div>
                        <div class="field"><label class="field-label" for="delivery_postcode">Postcode</label><input class="input" id="delivery_postcode" name="delivery_postcode" inputmode="numeric" maxlength="4" value="<?= e(old('delivery_postcode')) ?>" <?= invalid_attr($errors, 'delivery_postcode') ?>><?= field_error($errors, 'delivery_postcode') ?></div>
                    </div>
                    <p class="field-help"><?= icon('clock', '', 16) ?> We will confirm the delivery time with you before the day.</p>
                </fieldset>
                <?php endif; ?>

                <fieldset class="checkout-step">
                    <legend class="checkout-step__title"><span><?= 1 + ($hasDeposit ? 1 : 0) + ($delivery && !$order ? 1 : 0) ?></span> Payment</legend>
                    <?= field_error($errors, 'payment_method') ?>
                    <div class="option-cards">
                        <?php foreach ($methods as $key => $method): ?>
                            <div class="payment-option">
                                <label class="option-card">
                                    <input type="radio" name="payment_method" value="<?= e($key) ?>" <?= $selectedMethod === $key ? 'checked' : '' ?> data-payment-method data-needs-proof="<?= $method['needs_proof'] ? '1' : '0' ?>">
                                    <span class="option-card__box"><?= icon($key === PAYMENT_STRIPE ? 'card' : 'upload', '', 22) ?><span><strong><?= e($method['label']) ?></strong><span><?= e($method['description']) ?></span></span></span>
                                </label>
                                <?php if (!empty($method['details'])): ?>
                                    <div class="payment-details" data-payment-details="<?= e($key) ?>" <?= $selectedMethod === $key ? '' : 'hidden' ?>>
                                        <dl>
                                            <?php foreach ($method['details'] as $label => $value): ?>
                                                <div><dt><?= e($label) ?></dt><dd><span><?= e($value) ?></span> <button type="button" class="copy-btn" data-copy="<?= e($value) ?>">Copy</button></dd></div>
                                            <?php endforeach; ?>
                                            <div><dt>Amount</dt><dd><strong data-pay-amount><?= e(money($payChoice === 'deposit' ? $deposit : $price)) ?></strong></dd></div>
                                        </dl>
                                        <p class="field-help">Please use your name as the payment reference.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="proof-fields" data-proof-fields <?= ($methods[$selectedMethod]['needs_proof'] ?? false) ? '' : 'hidden' ?>>
                        <div class="field">
                            <label class="field-label" for="payment_proof">Payment screenshot</label>
                            <?php render('partials/upload-field', ['field' => 'payment_proof', 'purpose' => 'payment', 'accept' => 'image/jpeg,image/png,image/webp,image/heic,application/pdf', 'hint' => 'Tap to upload a screenshot or PDF (max 10 MB)', 'invalid' => invalid_attr($errors, 'payment_proof')]); ?>
                            <?= field_error($errors, 'payment_proof') ?>
                        </div>
                        <div class="field">
                            <label class="field-label" for="payment_reference">Payment reference <span class="field-hint">Optional</span></label>
                            <input class="input" id="payment_reference" name="payment_reference" type="text" placeholder="Receipt or transaction number">
                        </div>
                    </div>
                    <label class="check" style="margin-top:16px"><input type="checkbox" name="agree" value="1" <?= invalid_attr($errors, 'agree') ?>><span>The details of my cake and the date are correct.</span></label>
                    <?= field_error($errors, 'agree') ?>
                </fieldset>
            </div>

            <aside class="summary-card checkout-summary">
                <h2 class="summary-card__title">Your quote</h2>
                <dl class="summary-lines">
                    <div><dt>Occasion</dt><dd><?= e($request['occasion']) ?></dd></div>
                    <div><dt><?= $delivery ? 'Delivery' : 'Pickup' ?></dt><dd><?= e(date('D j M Y', strtotime($request['event_date']))) ?></dd></div>
                    <?php if ($request['servings']): ?><div><dt>Serves</dt><dd><?= e($request['servings']) ?></dd></div><?php endif; ?>
                    <?php if ($request['flavour']): ?><div><dt>Flavour</dt><dd><?= e($request['flavour']) ?></dd></div><?php endif; ?>
                </dl>
                <div class="summary-total"><span>Cake price</span><strong><?= e(money($price)) ?></strong></div>
                <?php if ($hasDeposit): ?><p class="summary-note"><?= icon('info', '', 16) ?> Pay now: <strong data-pay-amount><?= e(money($payChoice === 'deposit' ? $deposit : $price)) ?></strong></p><?php endif; ?>
                <button class="btn btn--primary btn--lg btn--block" type="submit"><?= icon('check', '', 20) ?> Accept and pay</button>
                <p class="summary-legal">By paying you agree to our <a href="<?= e(url('terms')) ?>">Terms</a> and <a href="<?= e(url('refund-policy')) ?>">Refund Policy</a>.</p>
                <a class="link-arrow summary-card__back" href="<?= e(thread_url(custom_request_thread($request))) ?>">Questions? Message us</a>
            </aside>
        </form>
        <?php endif; ?>
    </div>
</section>

<?php render('footer'); ?>
