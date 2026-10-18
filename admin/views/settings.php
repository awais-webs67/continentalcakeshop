<?php
/** @var string $tab @var array $s @var array $errors @var array $hours @var array $blocked @var array $methods */
$v = static fn (string $key): string => e((string) ($s[$key] ?? ''));
$on = static fn (string $key): string => checked(($s[$key] ?? '0') === '1');
$mask = static function (string $key): string {
    $value = setting($key);
    return $value === '' ? '' : substr($value, 0, strpos($value, '_', 4) + 1 ?: 8) . str_repeat('&bull;', 8) . e(substr($value, -4));
};
?>
<div class="page-head">
    <div><h1>Settings</h1><p>Everything the website shows about your shop. Changes go live as soon as you save.</p></div>
</div>
<?php if ($errors): ?><div class="alert alert--error"><?= e(count($errors) === 1 ? reset($errors) : 'Please fix the highlighted fields.') ?></div><?php endif; ?>

<section class="card">
    <nav class="tabs">
        <?php foreach (SETTINGS_TABS as $key => $label): ?>
            <a href="<?= e(admin_url('settings', ['tab' => $key])) ?>" class="<?= $tab === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>

<form method="post" enctype="multipart/form-data" action="<?= e(admin_url('settings', ['tab' => $tab])) ?>" id="settings-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">

<?php if ($tab === 'store'): ?>
    <div class="card__body form-grid">
        <label class="field"><span>Shop name <small>full name, used in titles and emails</small></span><input class="input" name="store_name" value="<?= $v('store_name') ?>" required></label>
        <label class="field"><span>Short name <small>used in the header and footer</small></span><input class="input" name="store_short_name" value="<?= $v('store_short_name') ?>"></label>
        <?= field_error($errors, 'store_name') ?>
        <label class="field"><span>Phone</span><input class="input" name="store_phone" value="<?= $v('store_phone') ?>" placeholder="02 6293 9578"></label>
        <label class="field"><span>Email <small>shown on the website, and where replies go</small></span><input class="input" type="email" name="store_email" value="<?= $v('store_email') ?>"></label>
        <?= field_error($errors, 'store_email') ?>
        <label class="field span-2"><span>Street address</span><input class="input" name="store_street" value="<?= $v('store_street') ?>"></label>
        <label class="field"><span>Suburb</span><input class="input" name="store_suburb" value="<?= $v('store_suburb') ?>"></label>
        <div class="form-grid form-grid--3" style="gap:12px">
            <label class="field"><span>State</span><input class="input" name="store_state" value="<?= $v('store_state') ?>"></label>
            <label class="field"><span>Postcode</span><input class="input" name="store_postcode" value="<?= $v('store_postcode') ?>"></label>
            <label class="field"><span>Since</span><input class="input" name="store_founded" value="<?= $v('store_founded') ?>"></label>
        </div>
    </div>

<?php elseif ($tab === 'hours'): ?>
    <div class="card__body">
        <p class="hint" style="margin-bottom:14px">Customers can only choose pickup times inside these hours. To close for a holiday, add the date under Ordering &amp; pickup.</p>
        <div class="hours-grid">
            <?php foreach (WEEK_DAYS as $day): $slot = $hours[$day] ?? null; ?>
                <div class="hours-grid__row">
                    <strong><?= e(DAY_NAMES[$day]) ?></strong>
                    <input class="input" type="time" name="hours[<?= $day ?>][open]" value="<?= e($slot[0] ?? '09:00') ?>" aria-label="<?= e(DAY_NAMES[$day]) ?> opens">
                    <input class="input" type="time" name="hours[<?= $day ?>][close]" value="<?= e($slot[1] ?? '17:00') ?>" aria-label="<?= e(DAY_NAMES[$day]) ?> closes">
                    <label class="switch"><input type="checkbox" name="hours[<?= $day ?>][closed]" value="1" data-closed-toggle <?= checked($slot === null) ?>><span class="switch__track"></span><span>Closed</span></label>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php elseif ($tab === 'ordering'): ?>
    <div class="card__body form-grid">
        <label class="field"><span>Notice needed for orders <small>hours</small></span><input class="input" type="number" min="0" name="min_lead_hours" value="<?= $v('min_lead_hours') ?>"></label>
        <label class="field"><span>Notice for custom cakes <small>days</small></span><input class="input" type="number" min="0" name="custom_lead_days" value="<?= $v('custom_lead_days') ?>"></label>
        <label class="field"><span>Take orders up to <small>days ahead</small></span><input class="input" type="number" min="1" name="max_advance_days" value="<?= $v('max_advance_days') ?>"></label>
        <label class="field"><span>Pickup time steps <small>minutes</small></span>
            <select class="input" name="slot_interval_minutes"><?php foreach ([15, 30, 45, 60] as $m): ?><option value="<?= $m ?>" <?= selected((int) ($s['slot_interval_minutes'] ?? 30) === $m) ?>><?= $m ?> minutes</option><?php endforeach; ?></select></label>
        <label class="field"><span>Last pickup before closing <small>minutes</small></span><input class="input" type="number" min="0" name="last_slot_buffer_minutes" value="<?= $v('last_slot_buffer_minutes') ?>"></label>
        <label class="field"><span>Most pickups per time slot <small>0 = no limit</small></span><input class="input" type="number" min="0" name="max_orders_per_slot" value="<?= $v('max_orders_per_slot') ?>"></label>
        <label class="field"><span>Most deliveries per day <small>0 = no limit</small></span><input class="input" type="number" min="0" name="max_deliveries_per_day" value="<?= $v('max_deliveries_per_day') ?>"></label>
    </div>
    <div class="card__body form-stack" style="border-top:1px solid var(--line)">
        <h3 class="label">How customers get their cake</h3>
        <label class="switch"><input type="checkbox" name="pickup_enabled" value="1" <?= $on('pickup_enabled') ?>><span class="switch__track"></span><span>Pickup from the shop</span></label>
        <label class="switch"><input type="checkbox" name="delivery_enabled" value="1" <?= $on('delivery_enabled') ?>><span class="switch__track"></span><span>Delivery</span></label>
        <?= field_error($errors, 'fulfilment') ?>
        <div class="form-grid">
            <label class="field"><span>Delivery fee</span><div class="input-prefix"><span>$</span><input class="input" name="delivery_fee" inputmode="decimal" value="<?= $v('delivery_fee') ?>"></div></label>
            <label class="field"><span>Minimum order for delivery <small>0 = none</small></span><div class="input-prefix"><span>$</span><input class="input" name="delivery_min_order" inputmode="decimal" value="<?= $v('delivery_min_order') ?>"></div></label>
            <label class="field"><span>Delivery area <small>shown at checkout</small></span><input class="input" name="delivery_note" value="<?= $v('delivery_note') ?>" placeholder="All over Canberra and Queanbeyan"></label>
            <label class="field"><span>Delivery postcodes <small>empty = anywhere</small></span><input class="input" name="delivery_postcodes" value="<?= $v('delivery_postcodes') ?>" placeholder="2600-2620, 2900-2914"></label>
            <?= field_error($errors, 'delivery_postcodes') ?>
        </div>
        <p class="hint">Customers choose a delivery date and a preferred time of day. You set the exact time on the order and the customer is emailed.</p>
    </div>

<?php elseif ($tab === 'payments'): ?>
    <div class="card__body">
        <p class="hint">A payment method shows at checkout only when it is switched on <strong>and</strong> its details are filled in.</p>
        <?= field_error($errors, 'payments') ?>
    </div>
    <div class="pay-method">
        <div class="pay-method__head">
            <div><h3>Card payments (Stripe)</h3><p class="hint">Visa, Mastercard, Amex, Apple Pay and Google Pay. Orders are marked paid automatically.</p></div>
            <?= $methods['stripe']['enabled'] && $methods['stripe']['configured'] ? status_badge('active', 'Live at checkout') : status_badge('inactive', 'Off') ?>
        </div>
        <div class="form-stack">
            <label class="switch"><input type="checkbox" name="payment_stripe_enabled" value="1" <?= $on('payment_stripe_enabled') ?>><span class="switch__track"></span><span>Accept card payments</span></label>
            <?= field_error($errors, 'payment_stripe_enabled') ?>
            <div class="form-grid">
                <label class="field"><span>Secret key <small>Stripe dashboard, Developers, API keys</small></span>
                    <input class="input" type="password" name="stripe_secret_key" autocomplete="off" placeholder="<?= setting('stripe_secret_key') !== '' ? 'Saved. Paste a new key to replace it' : 'sk_live_...' ?>"></label>
                <label class="field"><span>Publishable key <small>optional</small></span><input class="input" name="stripe_publishable_key" value="<?= $v('stripe_publishable_key') ?>" placeholder="pk_live_..."></label>
                <?= field_error($errors, 'stripe_secret_key') ?><?= field_error($errors, 'stripe_publishable_key') ?>
                <label class="field"><span>Webhook signing secret</span>
                    <input class="input" type="password" name="stripe_webhook_secret" autocomplete="off" placeholder="<?= setting('stripe_webhook_secret') !== '' ? 'Saved. Paste a new secret to replace it' : 'whsec_...' ?>"></label>
                <div class="field"><span>Webhook address <small>add this in Stripe, Developers, Webhooks</small></span>
                    <input class="input" readonly value="<?= e(url('stripe-webhook')) ?>" onclick="this.select()"></div>
                <?= field_error($errors, 'stripe_webhook_secret') ?>
            </div>
            <?php if (setting('stripe_secret_key') !== ''): ?>
                <p class="hint">Saved key: <code><?= $mask('stripe_secret_key') ?></code> <?= str_starts_with(setting('stripe_secret_key'), 'sk_test') || str_starts_with(setting('stripe_secret_key'), 'rk_test') ? status_badge('pending', 'Test mode') : status_badge('active', 'Live mode') ?>
                    &nbsp;<label class="inline-check"><input type="checkbox" name="clear_stripe_secret_key" value="1"> Remove key</label></p>
            <?php endif; ?>
            <?php if (setting('stripe_webhook_secret') === '' && setting('stripe_secret_key') !== ''): ?>
                <div class="alert alert--warning">Add the webhook so orders are marked paid even if a customer closes the page before returning to the website. Choose the events <code>checkout.session.completed</code> and <code>checkout.session.async_payment_succeeded</code>.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="pay-method">
        <div class="pay-method__head">
            <div><h3>PayID</h3><p class="hint">The customer pays from their banking app and uploads a screenshot. You check it and mark the order paid.</p></div>
            <?= $methods['payid']['enabled'] && $methods['payid']['configured'] ? status_badge('active', 'Live at checkout') : status_badge('inactive', 'Off') ?>
        </div>
        <div class="form-stack">
            <label class="switch"><input type="checkbox" name="payment_payid_enabled" value="1" <?= $on('payment_payid_enabled') ?>><span class="switch__track"></span><span>Accept PayID</span></label>
            <?= field_error($errors, 'payment_payid_enabled') ?>
            <div class="form-grid">
                <label class="field"><span>PayID <small>phone number, email or ABN</small></span><input class="input" name="payid_identifier" value="<?= $v('payid_identifier') ?>" placeholder="e.g. 0412 345 678"></label>
                <label class="field"><span>Name shown in the banking app</span><input class="input" name="payid_account_name" value="<?= $v('payid_account_name') ?>"></label>
            </div>
        </div>
    </div>
    <div class="pay-method">
        <div class="pay-method__head">
            <div><h3>Bank transfer</h3><p class="hint">The customer transfers to your account and uploads the receipt. You check it and mark the order paid.</p></div>
            <?= $methods['bank']['enabled'] && $methods['bank']['configured'] ? status_badge('active', 'Live at checkout') : status_badge('inactive', 'Off') ?>
        </div>
        <div class="form-stack">
            <label class="switch"><input type="checkbox" name="payment_bank_enabled" value="1" <?= $on('payment_bank_enabled') ?>><span class="switch__track"></span><span>Accept bank transfer</span></label>
            <?= field_error($errors, 'payment_bank_enabled') ?>
            <div class="form-grid form-grid--3">
                <label class="field"><span>Account name</span><input class="input" name="bank_account_name" value="<?= $v('bank_account_name') ?>"></label>
                <label class="field"><span>BSB</span><input class="input" name="bank_bsb" value="<?= $v('bank_bsb') ?>" inputmode="numeric" placeholder="000-000"></label>
                <label class="field"><span>Account number</span><input class="input" name="bank_account_number" value="<?= $v('bank_account_number') ?>" inputmode="numeric"></label>
            </div>
            <?= field_error($errors, 'bank_bsb') ?>
        </div>
    </div>

<?php elseif ($tab === 'emails'): ?>
    <div class="card__body form-stack">
        <label class="field"><span>Send new order alerts to</span><input class="input" type="email" name="order_notification_email" value="<?= $v('order_notification_email') ?>"></label>
        <?= field_error($errors, 'order_notification_email') ?>
        <p class="hint" style="margin-top:-6px">New orders, custom cake requests and contact messages are emailed here. Leave empty to turn alerts off.</p>
        <label class="switch"><input type="checkbox" name="send_customer_emails" value="1" <?= $on('send_customer_emails') ?>><span class="switch__track"></span><span>Email customers their order confirmation and updates</span></label>
        <label class="field"><span>Thank-you line <small>shown at the bottom of customer emails</small></span><input class="input" name="email_accent_note" value="<?= $v('email_accent_note') ?>"></label>
    </div>
    <div class="card__body" style="border-top:1px solid var(--line)">
        <h3 class="label" style="margin-bottom:8px">Sending method</h3>
        <dl class="dl">
            <dt>Method</dt><dd><strong><?= e(strtoupper((string) mail_config('driver', 'mail'))) ?></strong><?= mail_config('driver') === 'log' ? ' ' . status_badge('pending', 'Emails are saved to a file, not sent') : '' ?></dd>
            <dt>Sent from</dt><dd><?= e(mail_from()[1]) ?> &lt;<?= e(mail_from()[0]) ?>&gt;</dd>
            <?php if (mail_config('driver') === 'smtp'): ?><dt>Server</dt><dd><?= e((string) mail_config('smtp.host')) ?>:<?= (int) mail_config('smtp.port') ?></dd><?php endif; ?>
        </dl>
        <p class="hint" style="margin-top:10px">The sending method and email password are set in <code>config/config.php</code> on the server, so they stay private.</p>
    </div>

<?php elseif ($tab === 'homepage'): ?>
    <div class="card__body">
        <div class="grid-main" style="gap:20px">
            <div class="form-stack">
                <label class="field"><span>Small line above the title</span><input class="input" name="hero_eyebrow" value="<?= $v('hero_eyebrow') ?>"></label>
                <label class="field"><span>Main title</span><input class="input" name="hero_title" value="<?= $v('hero_title') ?>"></label>
                <label class="field"><span>Text under the title</span><textarea class="input" name="hero_subtitle" rows="3"><?= $v('hero_subtitle') ?></textarea></label>
                <label class="field"><span>Announcement bar <small>the thin strip at the very top, next to a delivery icon. Leave empty to show only the hours and phone</small></span><input class="input" name="announcement_text" value="<?= $v('announcement_text') ?>"></label>
            </div>
            <div class="form-stack">
                <span class="label">Main photo</span>
                <img id="hero-preview" src="<?= e(product_image_url($s['hero_image'] ?? '')) ?>" alt="" style="width:100%;aspect-ratio:3/4;object-fit:cover;border-radius:12px;border:1px solid var(--line)">
                <input class="input" type="file" name="hero_upload" accept="image/jpeg,image/png,image/webp" data-image-input="hero-preview">
                <?= field_error($errors, 'hero_upload') ?>
                <p class="hint">Upload a portrait photo, or pick one from the <a href="<?= e(admin_url('gallery')) ?>">gallery</a>.</p>
            </div>
        </div>
    </div>
    <div class="card__body form-grid form-grid--3" style="border-top:1px solid var(--line)">
        <label class="field"><span>Google rating</span><input class="input" name="google_rating" value="<?= $v('google_rating') ?>" placeholder="4.3"></label>
        <label class="field"><span>Number of reviews</span><input class="input" name="google_review_count" value="<?= $v('google_review_count') ?>" placeholder="290+"></label>
        <label class="field"><span>Google reviews link</span><input class="input" type="url" name="google_reviews_url" value="<?= $v('google_reviews_url') ?>"></label>
        <?= field_error($errors, 'google_reviews_url') ?>
    </div>

<?php elseif ($tab === 'seo'): ?>
    <div class="card__body form-grid form-grid--3">
        <label class="field"><span>Facebook page</span><input class="input" type="url" name="social_facebook" value="<?= $v('social_facebook') ?>" placeholder="https://facebook.com/..."></label>
        <label class="field"><span>Instagram</span><input class="input" type="url" name="social_instagram" value="<?= $v('social_instagram') ?>" placeholder="https://instagram.com/..."></label>
        <label class="field"><span>Pinterest</span><input class="input" type="url" name="social_pinterest" value="<?= $v('social_pinterest') ?>"></label>
        <?= field_error($errors, 'social_facebook') ?><?= field_error($errors, 'social_instagram') ?><?= field_error($errors, 'social_pinterest') ?>
    </div>
    <div class="card__body form-stack" style="border-top:1px solid var(--line)">
        <label class="field"><span>Homepage title in Google <small>about 60 characters</small></span><input class="input" name="seo_home_title" maxlength="160" value="<?= $v('seo_home_title') ?>" data-count></label>
        <label class="field"><span>Homepage description in Google <small>about 155 characters</small></span><textarea class="input" name="seo_home_description" rows="3" maxlength="300" data-count><?= $v('seo_home_description') ?></textarea></label>
        <div class="serp">
            <span class="serp__url"><?= e(url()) ?></span>
            <span class="serp__title"><?= $v('seo_home_title') ?></span>
            <span class="serp__desc"><?= $v('seo_home_description') ?></span>
        </div>
        <p class="hint">Products, categories and pages have their own search settings on their edit screens. The sitemap is at <a href="<?= e(url('sitemap.xml')) ?>" target="_blank"><?= e(url('sitemap.xml')) ?></a>.</p>
    </div>
<?php endif; ?>

    <div class="form-actions form-actions--sticky">
        <button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> Save <?= e(strtolower(SETTINGS_TABS[$tab])) ?></button>
        <?php if ($tab === 'payments' && setting('stripe_secret_key') !== ''): ?>
            <button class="btn btn--secondary" type="submit" name="action" value="test_stripe" formnovalidate>Test Stripe connection</button>
        <?php endif; ?>
        <?php if ($tab === 'emails'): ?>
            <button class="btn btn--secondary" type="submit" name="action" value="test_email" formnovalidate><?= icon('mail', '', 18) ?> Send me a test email</button>
        <?php endif; ?>
    </div>
</form>
</section>

<?php if ($tab === 'ordering'): ?>
<section class="card">
    <div class="card__head"><h2>Closed dates</h2><span class="hint">Public holidays, shop holidays or fully booked days</span></div>
    <form class="card__body toolbar toolbar--plain" method="post" action="<?= e(admin_url('settings', ['tab' => 'ordering'])) ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="block_date">
        <input class="input" type="date" name="blocked_date" min="<?= e(date('Y-m-d')) ?>" required>
        <input class="input grow" name="reason" maxlength="160" placeholder="Reason (optional), e.g. Christmas Day">
        <button class="btn btn--secondary" type="submit"><?= icon('plus', '', 18) ?> Close this date</button>
    </form>
    <?php if ($blocked): ?>
    <div class="table-wrap"><table class="table">
        <tbody>
        <?php foreach ($blocked as $b): ?>
            <tr>
                <td><strong><?= e(date('l j F Y', strtotime($b['blocked_date']))) ?></strong></td>
                <td class="muted"><?= e($b['reason'] ?: '') ?></td>
                <td class="num"><form method="post" action="<?= e(admin_url('settings', ['tab' => 'ordering'])) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="unblock_date"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><button class="link-btn link-btn--danger" type="submit">Open again</button></form></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php else: ?>
        <div class="card__body" style="padding-top:0"><p class="hint">No closed dates coming up.</p></div>
    <?php endif; ?>
</section>
<?php endif; ?>
