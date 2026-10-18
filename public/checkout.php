<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$items = cart_items();
if (!$items) {
    flash('info', 'Your cart is empty. Add a cake to get started.');
    redirect('shop');
}

$leadHours = cart_lead_hours($items);
$methods = available_payment_methods();
$pickupEnabled = setting_bool('pickup_enabled');
$deliveryEnabled = setting_bool('delivery_enabled');
$fulfilmentOptions = array_keys(array_filter(['pickup' => $pickupEnabled, 'delivery' => $deliveryEnabled]));
$subtotal = cart_subtotal($items);
$deliveryFee = (float) setting('delivery_fee', '0');
$deliveryMin = (float) setting('delivery_min_order', '0');
$user = current_user();

if (is_post()) {
    verify_csrf();

    $data = [
        'name'              => str_limit(input('name'), 120),
        'email'             => str_limit(input('email'), 190),
        'phone'             => str_limit(input('phone'), 40),
        'fulfilment'        => input('fulfilment', $fulfilmentOptions[0] ?? 'pickup'),
        'delivery_address'  => str_limit(input('delivery_address'), 255),
        'delivery_suburb'   => str_limit(input('delivery_suburb'), 80),
        'delivery_postcode' => preg_replace('/\D/', '', input('delivery_postcode')),
        'delivery_window'   => isset(DELIVERY_WINDOWS[input('delivery_window')]) ? input('delivery_window') : 'any',
        'pickup_date'       => input('fulfilment') === 'delivery' ? input('delivery_date') : input('pickup_date'),
        'pickup_time'       => input('pickup_time'),
        'payment_method'    => input('payment_method'),
        'payment_reference' => str_limit(input('payment_reference'), 190),
        'notes'             => str_limit(input('notes'), 1000),
        'payment_proof'     => null,
        'user_id'           => $user['id'] ?? null,
    ];
    $createAccount = !$user && input('create_account') === '1';
    $accountPassword = (string) ($_POST['account_password'] ?? '');

    $errors = [];
    if ($data['name'] === '') {
        $errors['name'] = 'Please enter your name.';
    }
    if (!valid_email($data['email'])) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (!valid_au_phone($data['phone'])) {
        $errors['phone'] = 'Please enter an Australian phone number, e.g. 0412 345 678.';
    }
    if (!in_array($data['fulfilment'], $fulfilmentOptions, true)) {
        $errors['fulfilment'] = 'Please choose pickup or delivery.';
    }
    if ($data['fulfilment'] === 'delivery') {
        if ($data['delivery_address'] === '') {
            $errors['delivery_address'] = 'Please enter the street address.';
        }
        if ($data['delivery_suburb'] === '') {
            $errors['delivery_suburb'] = 'Please enter the suburb.';
        }
        if (!preg_match('/^\d{4}$/', $data['delivery_postcode'])) {
            $errors['delivery_postcode'] = 'Please enter the 4 digit postcode.';
        } elseif (!delivery_postcode_allowed($data['delivery_postcode'])) {
            $errors['delivery_postcode'] = 'Sorry, we do not deliver to this postcode yet. Please choose pickup or call us.';
        }
        if ($dateError = delivery_date_error($data['pickup_date'], $leadHours)) {
            $errors['delivery_date'] = $dateError;
        }
        if ($deliveryMin > 0 && $subtotal < $deliveryMin) {
            $errors['fulfilment'] = 'Delivery is available for orders of ' . money($deliveryMin) . ' or more.';
        }
        $data['pickup_time'] = '';
    } elseif (!in_array($data['pickup_time'], pickup_slots($data['pickup_date'], $leadHours), true)) {
        $errors['pickup_time'] = 'Please choose an available date and time.';
    }
    $method = $methods[$data['payment_method']] ?? null;
    if (!$method) {
        $errors['payment_method'] = 'Please choose how you would like to pay.';
    } elseif ($method['needs_proof'] && !has_upload('payment_proof') && !async_upload(input('payment_proof_token'), 'payment')) {
        $errors['payment_proof'] = 'Please upload a screenshot of your payment.';
    }
    if ($createAccount) {
        if (mb_strlen($accountPassword) < PASSWORD_MIN_LENGTH) {
            $errors['account_password'] = 'Please use at least ' . PASSWORD_MIN_LENGTH . ' characters for your password.';
        } elseif (!isset($errors['email']) && find_user_by_email($data['email'])) {
            $errors['email'] = 'This email already has an account. Please log in to save this order to it.';
        }
    }
    if (input('agree') !== '1') {
        $errors['agree'] = 'Please confirm your order details.';
    }

    if (!$errors && $method['needs_proof']) {
        if ($uploaded = async_upload(input('payment_proof_token'), 'payment')) {
            $data['payment_proof'] = $uploaded['path'];
        } else {
            [$path, $uploadError] = store_private_upload('payment_proof', 'payments', UPLOAD_RECEIPT_TYPES);
            if ($uploadError) {
                $errors['payment_proof'] = $uploadError;
            }
            $data['payment_proof'] = $path;
        }
    }

    if ($errors) {
        remember_input(array_diff_key($data, ['payment_proof' => true, 'user_id' => true]) + ['create_account' => $createAccount ? '1' : '', 'agree' => input('agree'), 'delivery_date' => input('delivery_date'), 'pickup_date' => input('pickup_date'), 'payment_proof_token' => input('payment_proof_token')]);
        $_SESSION['form_errors'] = $errors;
        flash('error', 'Please check the highlighted fields.');
        redirect('checkout');
    }

    if ($createAccount) {
        [$newUserId] = register_user($data['name'], $data['email'], $data['phone'], $accountPassword);
        if ($newUserId) {
            login_user($newUserId);
            $data['user_id'] = $newUserId;
        }
    }

    $order = create_order($data, $items);
    forget_async_upload(input('payment_proof_token'));
    cart_clear();
    forget_input();

    if ($data['payment_method'] === PAYMENT_STRIPE) {
        try {
            redirect(stripe_create_checkout($order, get_order_items((int) $order['id'])));
        } catch (Throwable $e) {
            error_log($e->getMessage());
            flash('error', 'We saved your order but could not open the card payment page. Please try again below or call us.');
            redirect(order_url($order));
        }
    }

    send_order_emails($order);
    redirect(order_url($order));
}

$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

$selectedDate = old('pickup_date', first_available_date($leadHours) ?? '');
$firstDelivery = $deliveryEnabled ? first_delivery_date($leadHours) : null;
$selectedDeliveryDate = old('delivery_date', $firstDelivery ?? '');
$slots = $selectedDate !== '' ? pickup_slots($selectedDate, $leadHours) : [];
$selectedFulfilment = old('fulfilment', $fulfilmentOptions[0] ?? 'pickup');
$selectedMethod = old('payment_method', (string) array_key_first($methods));
$closedDays = array_map(static fn ($d) => DAY_NAMES[$d], closed_week_days());

$fieldError = static function (string $field) use ($errors): string {
    return isset($errors[$field]) ? '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field]) . '</p>' : '';
};
$invalid = static fn (string $field): string => isset($errors[$field]) ? 'aria-invalid="true" aria-describedby="' . e($field) . '-error"' : '';

$page = [
    'title'   => 'Checkout | ' . setting('store_short_name'),
    'noindex' => true,
];
render('header', compact('page'));
?>

<section class="page-hero page-hero--slim">
    <div class="container">
        <h1 class="page-title">Checkout</h1>
    </div>
</section>

<section class="section section--compact">
    <div class="container">
        <?php if (!$methods): ?>
            <div class="alert alert--info">Online payment is not available right now. Please call us on <a href="tel:<?= e(setting('store_phone_e164')) ?>"><?= e(setting('store_phone')) ?></a> to place your order.</div>
        <?php endif; ?>

        <form class="checkout-layout" method="post" action="<?= e(url('checkout')) ?>" enctype="multipart/form-data" data-checkout novalidate>
            <?= csrf_field() ?>
            <div class="checkout-main">

                <fieldset class="checkout-step">
                    <legend class="checkout-step__title"><span>1</span> Your details</legend>
                    <?php if ($user): ?>
                        <p class="account-note"><?= icon('user', '', 18) ?> <span>Logged in as <strong><?= e($user['email']) ?></strong>. This order will be saved to your account.</span></p>
                    <?php else: ?>
                        <p class="account-note"><?= icon('user', '', 18) ?> <span>Have an account? <a href="<?= e(url('login') . '?next=checkout') ?>">Log in</a> to save this order to your order history.</span></p>
                    <?php endif; ?>
                    <div class="field">
                        <label class="field-label" for="name">Full name</label>
                        <input class="input" id="name" name="name" type="text" autocomplete="name" value="<?= e(old('name', $user['name'] ?? '')) ?>" required <?= $invalid('name') ?>>
                        <?= $fieldError('name') ?>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label class="field-label" for="email">Email</label>
                            <input class="input" id="email" name="email" type="email" autocomplete="email" value="<?= e(old('email', $user['email'] ?? '')) ?>" required <?= $invalid('email') ?> <?= $user ? 'readonly' : '' ?>>
                            <?= $fieldError('email') ?>
                        </div>
                        <div class="field">
                            <label class="field-label" for="phone">Mobile number</label>
                            <input class="input" id="phone" name="phone" type="tel" autocomplete="tel" placeholder="04xx xxx xxx" value="<?= e(old('phone', (string) ($user['phone'] ?? ''))) ?>" required <?= $invalid('phone') ?>>
                            <?= $fieldError('phone') ?>
                        </div>
                    </div>
                    <?php if (!$user): ?>
                        <div class="create-account">
                            <label class="check">
                                <input type="checkbox" name="create_account" value="1" <?= old('create_account') === '1' ? 'checked' : '' ?> data-create-account>
                                <span><strong>Create an account</strong> to save this order and follow its progress</span>
                            </label>
                            <div class="field" data-account-password <?= old('create_account') === '1' ? '' : 'hidden' ?>>
                                <label class="field-label" for="account_password">Choose a password</label>
                                <input class="input" id="account_password" name="account_password" type="password" autocomplete="new-password" minlength="<?= PASSWORD_MIN_LENGTH ?>" <?= $invalid('account_password') ?>>
                                <?= $fieldError('account_password') ?: '<p class="field-help">At least ' . PASSWORD_MIN_LENGTH . ' characters.</p>' ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </fieldset>

                <fieldset class="checkout-step">
                    <legend class="checkout-step__title"><span>2</span> <?= count($fulfilmentOptions) > 1 ? 'Pickup or delivery' : ($fulfilmentOptions === ['delivery'] ? 'Delivery' : 'Pickup date and time') ?></legend>
                    <?= $fieldError('fulfilment') ?>

                    <?php if (count($fulfilmentOptions) > 1): ?>
                        <div class="option-cards option-cards--2">
                            <label class="option-card">
                                <input type="radio" name="fulfilment" value="pickup" <?= $selectedFulfilment === 'pickup' ? 'checked' : '' ?> data-fulfilment>
                                <span class="option-card__box"><?= icon('store', '', 22) ?><span><strong>Pickup from the shop</strong><span>Free. You choose the time</span></span></span>
                            </label>
                            <label class="option-card">
                                <input type="radio" name="fulfilment" value="delivery" <?= $selectedFulfilment === 'delivery' ? 'checked' : '' ?> data-fulfilment>
                                <span class="option-card__box"><?= icon('truck', '', 22) ?><span><strong>Delivery</strong><span><?= $deliveryFee > 0 ? e(money($deliveryFee)) . ' &middot; ' : 'Free &middot; ' ?><?= e(setting('delivery_note', 'All over Canberra')) ?></span></span></span>
                            </label>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="fulfilment" value="<?= e($fulfilmentOptions[0] ?? 'pickup') ?>">
                    <?php endif; ?>

                    <?php if ($pickupEnabled): ?>
                    <div class="fulfilment-panel" data-pickup-only <?= $selectedFulfilment === 'pickup' ? '' : 'hidden' ?>>
                        <p class="pickup-address"><?= icon('store', '', 20) ?> <span>Pick up from <strong><?= e(store_address_line()) ?></strong></span></p>
                        <div class="field-row">
                            <div class="field">
                                <label class="field-label" for="pickup_date">Pickup date</label>
                                <input class="input" id="pickup_date" name="pickup_date" type="date" value="<?= e($selectedDate) ?>"
                                       min="<?= e(earliest_pickup($leadHours)->format('Y-m-d')) ?>" max="<?= e(latest_pickup_date()->format('Y-m-d')) ?>"
                                       data-slot-date data-slots-url="<?= e(url('api/slots')) ?>">
                            </div>
                            <div class="field">
                                <label class="field-label" for="pickup_time">Pickup time</label>
                                <select class="input" id="pickup_time" name="pickup_time" data-slot-time <?= $invalid('pickup_time') ?>>
                                    <?php if (!$slots): ?>
                                        <option value="">No times available on this day</option>
                                    <?php endif; ?>
                                    <?php foreach ($slots as $slot): ?>
                                        <option value="<?= e($slot) ?>" <?= old('pickup_time') === $slot ? 'selected' : '' ?>><?= e(format_time($slot)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?= $fieldError('pickup_time') ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($deliveryEnabled): ?>
                    <div class="fulfilment-panel" data-delivery-only <?= $selectedFulfilment === 'delivery' ? '' : 'hidden' ?>>
                        <div class="field-row">
                            <div class="field">
                                <label class="field-label" for="delivery_date">Delivery date</label>
                                <input class="input" id="delivery_date" name="delivery_date" type="date" value="<?= e($selectedDeliveryDate) ?>"
                                       min="<?= e($firstDelivery ?? earliest_pickup($leadHours)->format('Y-m-d')) ?>" max="<?= e(latest_pickup_date()->format('Y-m-d')) ?>"
                                       data-delivery-date data-check-url="<?= e(url('api/slots')) ?>" <?= $invalid('delivery_date') ?>>
                                <?= $fieldError('delivery_date') ?: '<p class="field-error" data-delivery-date-error hidden></p>' ?>
                            </div>
                            <div class="field">
                                <label class="field-label" for="delivery_window">Preferred time</label>
                                <select class="input" id="delivery_window" name="delivery_window">
                                    <?php foreach (DELIVERY_WINDOWS as $key => $label): ?>
                                        <option value="<?= e($key) ?>" <?= old('delivery_window', 'any') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <label class="field-label" for="delivery_address">Street address</label>
                            <input class="input" id="delivery_address" name="delivery_address" type="text" autocomplete="address-line1" placeholder="e.g. 12 Smith Street, Unit 4" value="<?= e(old('delivery_address')) ?>" <?= $invalid('delivery_address') ?>>
                            <?= $fieldError('delivery_address') ?>
                        </div>
                        <div class="field-row field-row--suburb">
                            <div class="field">
                                <label class="field-label" for="delivery_suburb">Suburb</label>
                                <input class="input" id="delivery_suburb" name="delivery_suburb" type="text" autocomplete="address-level2" placeholder="e.g. Tuggeranong" value="<?= e(old('delivery_suburb')) ?>" <?= $invalid('delivery_suburb') ?>>
                                <?= $fieldError('delivery_suburb') ?>
                            </div>
                            <div class="field">
                                <label class="field-label" for="delivery_postcode">Postcode</label>
                                <input class="input" id="delivery_postcode" name="delivery_postcode" type="text" inputmode="numeric" autocomplete="postal-code" maxlength="4" placeholder="2900" value="<?= e(old('delivery_postcode')) ?>" <?= $invalid('delivery_postcode') ?>>
                                <?= $fieldError('delivery_postcode') ?>
                            </div>
                        </div>
                        <p class="delivery-note"><?= icon('clock', '', 18) ?> <span>We plan our delivery runs each day and <strong>email you the delivery time</strong> before the day. Please add gate codes or other instructions in the order notes.</span></p>
                    </div>
                    <?php endif; ?>

                    <p class="field-help"><?= icon('info', '', 16) ?> Please order at least <?= e(notice_label($leadHours)) ?> ahead. You can book up to <?= (int) setting('max_advance_days', '60') ?> days in advance.<?= $closedDays ? ' We are closed on ' . e(implode(', ', $closedDays)) . '.' : '' ?></p>
                </fieldset>

                <fieldset class="checkout-step">
                    <legend class="checkout-step__title"><span>3</span> Payment</legend>
                    <?= $fieldError('payment_method') ?>
                    <div class="option-cards">
                        <?php foreach ($methods as $key => $method): ?>
                            <div class="payment-option">
                                <label class="option-card">
                                    <input type="radio" name="payment_method" value="<?= e($key) ?>" <?= $selectedMethod === $key ? 'checked' : '' ?> data-payment-method data-needs-proof="<?= $method['needs_proof'] ? '1' : '0' ?>">
                                    <span class="option-card__box">
                                        <?= icon($key === PAYMENT_STRIPE ? 'card' : 'upload', '', 22) ?>
                                        <span><strong><?= e($method['label']) ?></strong><span><?= e($method['description']) ?></span></span>
                                    </span>
                                </label>
                                <?php if (!empty($method['details'])): ?>
                                    <div class="payment-details" data-payment-details="<?= e($key) ?>" <?= $selectedMethod === $key ? '' : 'hidden' ?>>
                                        <dl>
                                            <?php foreach ($method['details'] as $label => $value): ?>
                                                <div>
                                                    <dt><?= e($label) ?></dt>
                                                    <dd><span><?= e($value) ?></span> <button type="button" class="copy-btn" data-copy="<?= e($value) ?>">Copy</button></dd>
                                                </div>
                                            <?php endforeach; ?>
                                            <div><dt>Amount</dt><dd><strong data-pay-amount><?= e(money($subtotal)) ?></strong></dd></div>
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
                            <?php render('partials/upload-field', ['field' => 'payment_proof', 'purpose' => 'payment', 'accept' => 'image/jpeg,image/png,image/webp,image/heic,application/pdf', 'hint' => 'Tap to upload a screenshot or PDF (max 10 MB)', 'invalid' => $invalid('payment_proof')]); ?>
                            <?= $fieldError('payment_proof') ?>
                        </div>
                        <div class="field">
                            <label class="field-label" for="payment_reference">Payment reference <span class="field-hint">Optional</span></label>
                            <input class="input" id="payment_reference" name="payment_reference" type="text" value="<?= e(old('payment_reference')) ?>" placeholder="Receipt or transaction number">
                        </div>
                    </div>
                </fieldset>

                <fieldset class="checkout-step">
                    <legend class="checkout-step__title"><span>4</span> Anything else?</legend>
                    <div class="field">
                        <label class="field-label" for="notes">Order notes <span class="field-hint">Optional</span></label>
                        <textarea class="input" id="notes" name="notes" rows="3" maxlength="1000" placeholder="Allergies, candles, delivery instructions or anything we should know"><?= e(old('notes')) ?></textarea>
                    </div>
                    <label class="check">
                        <input type="checkbox" name="agree" value="1" <?= old('agree') === '1' ? 'checked' : '' ?> required <?= $invalid('agree') ?>>
                        <span>I have checked my order, the date and my details are correct.</span>
                    </label>
                    <?= $fieldError('agree') ?>
                </fieldset>
            </div>

            <aside class="summary-card checkout-summary">
                <h2 class="summary-card__title">Your order</h2>
                <ul class="summary-items">
                    <?php foreach ($items as $item): ?>
                        <li>
                            <img src="<?= e(product_image_url($item['image'])) ?>" alt="" width="56" height="56" loading="lazy">
                            <div>
                                <strong><?= e($item['name']) ?></strong>
                                <span><?= e($item['size_label']) ?> &times; <?= (int) $item['qty'] ?></span>
                                <?php if ($item['message'] !== ''): ?><span class="summary-items__msg">&ldquo;<?= e($item['message']) ?>&rdquo;</span><?php endif; ?>
                            </div>
                            <span><?= e(money($item['line_total'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <dl class="summary-lines">
                    <div><dt>Subtotal</dt><dd><?= e(money($subtotal)) ?></dd></div>
                    <div data-delivery-fee-row <?= $selectedFulfilment === 'delivery' ? '' : 'hidden' ?>><dt>Delivery</dt><dd><?= e(money($deliveryFee)) ?></dd></div>
                </dl>
                <div class="summary-total">
                    <span>Total</span>
                    <strong data-order-total data-subtotal="<?= e(number_format($subtotal, 2, '.', '')) ?>" data-delivery-fee="<?= e(number_format($deliveryFee, 2, '.', '')) ?>">
                        <?= e(money($subtotal + ($selectedFulfilment === 'delivery' ? $deliveryFee : 0))) ?>
                    </strong>
                </div>
                <button class="btn btn--primary btn--lg btn--block" type="submit" <?= $methods ? '' : 'disabled' ?> data-submit-label-card="Continue to Payment" data-submit-label-default="Place Order">
                    <?= icon('check', '', 20) ?> <span data-submit-label><?= $selectedMethod === PAYMENT_STRIPE ? 'Continue to Payment' : 'Place Order' ?></span>
                </button>
                <p class="summary-legal">By placing your order you agree to our <a href="<?= e(url('terms')) ?>">Terms</a> and <a href="<?= e(url('refund-policy')) ?>">Refund Policy</a>.</p>
                <ul class="summary-trust">
                    <li><?= icon('check', '', 16) ?> Secure checkout</li>
                    <li><?= icon('halal', '', 16) ?> 100% Halal</li>
                    <li><?= icon('truck', '', 16) ?> <?= $deliveryEnabled ? 'Pickup or delivery' : 'Pickup in Greenway' ?></li>
                </ul>
                <a class="link-arrow summary-card__back" href="<?= e(url('cart')) ?>">Edit cart</a>
            </aside>
        </form>
    </div>
</section>

<?php render('footer'); ?>
