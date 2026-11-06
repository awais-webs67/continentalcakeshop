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
    <div><h1>Settings</h1><p>What the whole business shares. Each shop's own address, phone, email account and payments are in Branches.</p></div>
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
    <div class="card__body shared-note">
        <p><strong>Each shop's address, phone, email and map are in Branches.</strong> They show in the footer, on the contact page, on each shop's page, on invoices and in emails.</p>
        <ul class="shop-summary">
            <?php foreach (branches() as $navShop): $shown = branch_email_shown($navShop); ?>
                <li>
                    <span class="shop-summary__text"><strong><?= e($navShop['name']) ?></strong><?= $navShop['is_active'] ? '' : ' <small class="muted">(hidden)</small>' ?><small><?= e(branch_address_line($navShop)) ?> &middot; <?= e($navShop['phone'] ?: 'no phone yet') ?> &middot; <?= e($shown ?: (branch_setting((int) $navShop['id'], 'show_email') === '0' ? 'email hidden' : 'no email yet')) ?></small></span>
                    <a class="link-btn" href="<?= e(admin_url('branches', ['id' => $navShop['id'], 'tab' => 'details'])) ?>">Change</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="card__body form-grid" style="border-top:1px solid var(--line)">
        <label class="field"><span>Business name <small>full name, used in titles and emails</small></span><input class="input" name="store_name" value="<?= $v('store_name') ?>" required></label>
        <label class="field"><span>Short name <small>used in the header and footer</small></span><input class="input" name="store_short_name" value="<?= $v('store_short_name') ?>"></label>
        <?= field_error($errors, 'store_name') ?>
        <div class="form-grid" style="gap:12px">
            <label class="field"><span>ABN <small>footer and invoices</small></span><input class="input" name="store_abn" value="<?= $v('store_abn') ?>" placeholder="12 345 678 901"></label>
            <label class="field"><span>Since</span><input class="input" name="store_founded" value="<?= $v('store_founded') ?>"></label>
        </div>
        <label class="switch" style="align-self:end"><input type="checkbox" name="gst_registered" value="1" <?= $on('gst_registered') ?>><span class="switch__track"></span><span>Registered for GST (orders show the GST included)</span></label>
        <?= field_error($errors, 'store_abn') ?>
        <div class="form-grid span-2">
            <label class="field"><span>WhatsApp number <small>optional, shows a chat button on the website</small></span><input class="input" name="whatsapp_number" value="<?= $v('whatsapp_number') ?>" placeholder="0412 345 678"></label>
            <label class="field"><span>First message <small>typed for the customer</small></span><input class="input" name="whatsapp_message" value="<?= $v('whatsapp_message') ?>"></label>
        </div>
        <?= field_error($errors, 'whatsapp_number') ?>
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
        <label class="field"><span>Notice needed for orders <small>hours</small></span><input class="input" type="number" min="1" max="720" name="min_lead_hours" value="<?= $v('min_lead_hours') ?>"><small class="hint">How long you need to get a cake ready. 1 or 2 lets customers order for today; 24 means from tomorrow. A cake can have its own notice in its product page.</small></label>
        <label class="field"><span>Notice for custom cakes <small>days</small></span><input class="input" type="number" min="0" name="custom_lead_days" value="<?= $v('custom_lead_days') ?>"></label>
        <label class="field"><span>Take orders up to <small>days ahead</small></span><input class="input" type="number" min="1" name="max_advance_days" value="<?= $v('max_advance_days') ?>"></label>
        <label class="field"><span>Pickup time steps <small>minutes</small></span>
            <select class="input" name="slot_interval_minutes"><?php foreach ([15, 30, 45, 60] as $m): ?><option value="<?= $m ?>" <?= selected((int) ($s['slot_interval_minutes'] ?? 30) === $m) ?>><?= $m ?> minutes</option><?php endforeach; ?></select></label>
        <label class="field"><span>Last pickup before closing <small>minutes</small></span><input class="input" type="number" min="0" name="last_slot_buffer_minutes" value="<?= $v('last_slot_buffer_minutes') ?>"></label>
        <label class="field"><span>Most pickups per time slot <small>0 = no limit</small></span><input class="input" type="number" min="0" name="max_orders_per_slot" value="<?= $v('max_orders_per_slot') ?>"></label>
        <label class="field"><span>Most deliveries per day <small>0 = no limit</small></span><input class="input" type="number" min="0" name="max_deliveries_per_day" value="<?= $v('max_deliveries_per_day') ?>"></label>
    </div>
    <div class="card__body form-stack" style="border-top:1px solid var(--line)">
        <h3 class="label">Pause online orders</h3>
        <label class="switch"><input type="checkbox" name="ordering_paused" value="1" <?= $on('ordering_paused') ?>><span class="switch__track"></span><span>Stop taking online orders for now<small>For holidays or very busy days. Customers can still browse, send custom cake requests and call. A notice shows at the top of every page.</small></span></label>
        <div class="form-grid">
            <label class="field"><span>Last paused day <small>optional, orders open again the day after</small></span><input class="input" type="date" name="ordering_paused_until" value="<?= $v('ordering_paused_until') ?>"></label>
            <label class="field"><span>Notice for customers</span><input class="input" name="ordering_paused_message" maxlength="255" value="<?= $v('ordering_paused_message') ?>"></label>
        </div>
        <?php if (ordering_paused()): ?><p class="alert alert--error" style="margin:0">Online orders are paused right now.</p><?php endif; ?>
    </div>
    <div class="card__body form-stack" style="border-top:1px solid var(--line)">
        <h3 class="label">How customers get their cake</h3>
        <label class="switch"><input type="checkbox" name="pickup_enabled" value="1" <?= $on('pickup_enabled') ?>><span class="switch__track"></span><span>Pickup from the shop</span></label>
        <label class="switch"><input type="checkbox" name="delivery_enabled" value="1" <?= $on('delivery_enabled') ?>><span class="switch__track"></span><span>Delivery</span></label>
        <?= field_error($errors, 'fulfilment') ?>
        <div class="form-grid">
            <label class="field"><span>Delivery fee</span><div class="input-prefix"><span>$</span><input class="input" name="delivery_fee" inputmode="decimal" value="<?= $v('delivery_fee') ?>"></div></label>
            <label class="field"><span>Free delivery for orders over <small>0 = never free</small></span><div class="input-prefix"><span>$</span><input class="input" name="delivery_free_over" inputmode="decimal" value="<?= $v('delivery_free_over') ?>"></div></label>
            <label class="field"><span>Minimum order for delivery <small>0 = none</small></span><div class="input-prefix"><span>$</span><input class="input" name="delivery_min_order" inputmode="decimal" value="<?= $v('delivery_min_order') ?>"></div></label>
            <label class="field"><span>Delivery area <small>shown at checkout</small></span><input class="input" name="delivery_note" value="<?= $v('delivery_note') ?>" placeholder="All over Canberra and Queanbeyan"></label>
            <label class="field"><span>Delivery postcodes <small>empty = anywhere</small></span><input class="input" name="delivery_postcodes" value="<?= $v('delivery_postcodes') ?>" placeholder="2600-2620, 2900-2914"></label>
            <?= field_error($errors, 'delivery_postcodes') ?>
        </div>
        <p class="hint">Set the delivery fee to 0 to make delivery free for everyone.</p>
        <label class="field"><span>Delivery time windows <small>one per line, customers pick one as their preferred time</small></span>
            <textarea class="input" name="delivery_windows" rows="4" placeholder="9am to 12pm&#10;12pm to 3pm&#10;3pm to 6pm"><?= e(implode("\n", delivery_windows())) ?></textarea></label>
        <p class="hint">Customers choose a delivery date and one of these windows (or "Any time"). On the order you then confirm the exact time or a range, such as 1pm to 3pm, and the customer is emailed.</p>
    </div>

<?php elseif ($tab === 'payments'): ?>
    <?php if (count(branches()) > 1): ?>
    <div class="card__body shared-note">
        <p><strong>These are the shared payment details.</strong> A shop can have its own in Branches &gt; shop &gt; Payments:</p>
        <ul>
            <?php foreach (branches() as $navShop): $sid = (int) $navShop['id']; ?>
                <li><a href="<?= e(admin_url('branches', ['id' => $sid, 'tab' => 'payments'])) ?>"><?= e($navShop['name']) ?></a>: <?= branch_uses_own($sid, 'payments') ? 'its own payment details' : 'these shared details' ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
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
                <label class="field"><span>Name at checkout</span><input class="input" name="payment_stripe_label" maxlength="60" value="<?= $v('payment_stripe_label') ?>"></label>
                <label class="field"><span>Text under the name</span><input class="input" name="payment_stripe_note" maxlength="255" value="<?= $v('payment_stripe_note') ?>"></label>
            </div>
            <?= field_error($errors, 'payment_stripe_label') ?>
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
                <label class="field"><span>Name at checkout</span><input class="input" name="payment_payid_label" maxlength="60" value="<?= $v('payment_payid_label') ?>"></label>
                <label class="field"><span>Text under the name</span><input class="input" name="payment_payid_note" maxlength="255" value="<?= $v('payment_payid_note') ?>"></label>
            </div>
            <?= field_error($errors, 'payment_payid_label') ?>
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
            <div class="form-grid">
                <label class="field"><span>Name at checkout</span><input class="input" name="payment_bank_label" maxlength="60" value="<?= $v('payment_bank_label') ?>"></label>
                <label class="field"><span>Text under the name</span><input class="input" name="payment_bank_note" maxlength="255" value="<?= $v('payment_bank_note') ?>"></label>
            </div>
            <?= field_error($errors, 'payment_bank_label') ?>
            <div class="form-grid form-grid--3">
                <label class="field"><span>Account name</span><input class="input" name="bank_account_name" value="<?= $v('bank_account_name') ?>"></label>
                <label class="field"><span>BSB</span><input class="input" name="bank_bsb" value="<?= $v('bank_bsb') ?>" inputmode="numeric" placeholder="000-000"></label>
                <label class="field"><span>Account number</span><input class="input" name="bank_account_number" value="<?= $v('bank_account_number') ?>" inputmode="numeric"></label>
            </div>
            <?= field_error($errors, 'bank_bsb') ?>
        </div>
    </div>

<?php elseif ($tab === 'emails'): ?>
    <div class="card__body shared-note">
        <p><strong>Each shop sends and receives its emails with its own account.</strong> The account, where order alerts go, the inbox in Messages, reminders and review requests are in each shop's Email tab.</p>
        <ul class="shop-summary">
            <?php foreach (branches() as $navShop): $sid = (int) $navShop['id']; $from = branch_account_email($sid); ?>
                <li>
                    <span class="shop-summary__text"><strong><?= e($navShop['name']) ?></strong><small><?= ($navShop['is_main'] || branch_uses_own($sid, 'email')) ? 'Sends from ' . e($from ?: '(not set up yet)') : 'Uses ' . e(main_branch()['name'] ?? 'the main shop') . '\'s account for now' ?> &middot; alerts to <?= e(branch_notify_email($sid) ?: 'nobody') ?><?= inbox_enabled($navShop) ? ' &middot; inbox in Messages' : '' ?></small></span>
                    <a class="link-btn" href="<?= e(admin_url('branches', ['id' => $sid, 'tab' => 'email'])) ?>">Email settings</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="card__body form-stack" style="border-top:1px solid var(--line)">
        <h3 class="label">Customer emails <small class="muted">(every shop)</small></h3>
        <label class="switch"><input type="checkbox" name="send_customer_emails" value="1" <?= $on('send_customer_emails') ?>><span class="switch__track"></span><span>Email customers their order confirmation and updates</span></label>
        <label class="field"><span>Thank-you line <small>shown at the bottom of customer emails</small></span><input class="input" name="email_accent_note" value="<?= $v('email_accent_note') ?>"></label>
    </div>
    <div class="card__body form-stack" style="border-top:1px solid var(--line)">
        <h3 class="label">Owner's morning email</h3>
        <label class="switch"><input type="checkbox" name="daily_email_enabled" value="1" <?= $on('daily_email_enabled') ?>><span class="switch__track"></span><span>Send the owner a plan for the day before opening<small><?= count(branches()) > 1 ? 'Covers every shop. Each shop\'s own plan is switched on in its Email tab.' : 'Today\'s pickups and deliveries, payments to check, deliveries without a time, custom cakes this week and unread messages.' ?></small></span></label>
        <div class="form-grid">
            <label class="field"><span>Send to <small>empty = <?= e(branch_notify_email(main_branch_id()) ?: 'the main shop\'s alerts address') ?></small></span><input class="input" type="email" name="daily_email_to" value="<?= $v('daily_email_to') ?>" placeholder="<?= e(branch_notify_email(main_branch_id())) ?>"></label>
            <label class="field"><span>Minutes before opening</span><input class="input" type="number" min="0" max="600" name="daily_email_minutes_before" value="<?= $v('daily_email_minutes_before') ?>"></label>
        </div>
        <?= field_error($errors, 'daily_email_to') ?>
        <p class="hint" style="margin:0">It is sent from <?= e(main_branch()['name'] ?? 'the main shop') ?>'s email account and arrives every open day, so you also know straight away if email stops working.</p>
    </div>
    <div class="card__body form-stack" style="border-top:1px solid var(--line)">
        <h3 class="label">Timing <small class="muted">(every shop)</small></h3>
        <label class="field" style="max-width:360px"><span>Day-before reminders go out from <small>hour, 24 hour clock</small></span><input class="input" type="number" min="6" max="21" name="reminder_send_hour" value="<?= $v('reminder_send_hour') ?>"></label>
        <?php $cronRun = setting('cron_last_run'); ?>
        <p class="hint" style="margin:0">Scheduled job (morning emails, reminders, reviews, inboxes): <?= $cronRun !== '' ? 'last ran ' . e(time_ago($cronRun)) . (strtotime($cronRun) < time() - 3600 ? ' ' . status_badge('pending', 'Not running') : ' ' . status_badge('active', 'Running')) : status_badge('pending', 'Not set up yet') . ' Add the cron job from DEPLOY.md in Hostinger &gt; Advanced &gt; Cron Jobs.' ?><?= setting('daily_email_last_sent') !== '' ? ' &middot; last morning email ' . e(date('j M Y', strtotime(setting('daily_email_last_sent')))) : '' ?></p>
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
        <p class="hint">Products, categories and pages have their own search settings on their edit screens. The sitemap is at <a href="<?= e(url('sitemap.xml')) ?>" target="_blank"><?= e(url('sitemap.xml')) ?></a> and robots.txt at <a href="<?= e(url('robots.txt')) ?>" target="_blank"><?= e(url('robots.txt')) ?></a>.</p>
    </div>
    <div class="card__body form-stack" style="border-top:1px solid var(--line)">
        <h3 class="label">Google and Bing</h3>
        <div class="form-grid form-grid--3">
            <label class="field"><span>Google Analytics ID <small>GA4</small></span><input class="input" name="seo_ga4_id" value="<?= $v('seo_ga4_id') ?>" placeholder="G-ABC123XYZ"></label>
            <label class="field"><span>Google Search Console code</span><input class="input" name="seo_google_verification" value="<?= $v('seo_google_verification') ?>" placeholder="Paste the code or the whole meta tag"></label>
            <label class="field"><span>Bing Webmaster code</span><input class="input" name="seo_bing_verification" value="<?= $v('seo_bing_verification') ?>"></label>
        </div>
        <?= field_error($errors, 'seo_ga4_id') ?>
        <p class="hint">After verifying in Search Console, submit the sitemap address above so Google finds every cake quickly.</p>
    </div>
    <div class="card__body form-stack" style="border-top:1px solid var(--line)">
        <h3 class="label">Redirects from old addresses</h3>
        <p class="hint" style="margin-top:-6px">The old website's pages (/allcakes, /mud-cakes, /about-us, /celebration-cakes and others) already send visitors to the new pages. Add more here, one per line: old address, a space, new address.</p>
        <textarea class="input" name="seo_redirects" rows="4" placeholder="/old-page /shop/tortes"><?= $v('seo_redirects') ?></textarea>
        <?= field_error($errors, 'seo_redirects') ?>
    </div>
<?php endif; ?>

    <div class="form-actions form-actions--sticky">
        <button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> Save <?= e(strtolower(SETTINGS_TABS[$tab])) ?></button>
        <?php if ($tab === 'payments' && setting('stripe_secret_key') !== ''): ?>
            <button class="btn btn--secondary" type="submit" name="action" value="test_stripe" formnovalidate>Test Stripe connection</button>
        <?php endif; ?>
        <?php if ($tab === 'emails'): ?>
            <button class="btn btn--secondary" type="submit" name="action" value="daily_now" formnovalidate>Send today's morning email now</button>
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
