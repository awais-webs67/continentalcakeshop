<?php
$admin = require_admin();

const SETTINGS_TABS = [
    'store'    => 'Business',
    'hours'    => 'Opening hours',
    'ordering' => 'Ordering & pickup',
    'payments' => 'Payments',
    'emails'   => 'Emails',
    'seo'      => 'Social & SEO',
];

// Plain text settings each tab may change. Anything not listed here is ignored.
const SETTINGS_FIELDS = [
    // Each shop's address, phone, email and map are in Admin > Branches > shop > Shop details.
    'store'    => ['store_name' => 120, 'store_short_name' => 80, 'store_founded' => 4, 'store_abn' => 20, 'whatsapp_number' => 20, 'whatsapp_message' => 160, 'maintenance_message' => 300],
    'ordering' => ['min_lead_hours' => 4, 'custom_lead_days' => 3, 'max_advance_days' => 4, 'slot_interval_minutes' => 3, 'last_slot_buffer_minutes' => 4, 'max_orders_per_slot' => 4, 'max_deliveries_per_day' => 4, 'delivery_fee' => 10, 'delivery_free_over' => 10, 'delivery_min_order' => 10, 'delivery_note' => 255, 'delivery_postcodes' => 255, 'ordering_paused_until' => 10, 'ordering_paused_message' => 255],
    'payments' => ['payment_stripe_label' => 60, 'payment_stripe_note' => 255, 'payment_payid_label' => 60, 'payment_payid_note' => 255, 'payment_bank_label' => 60, 'payment_bank_note' => 255, 'payid_identifier' => 120, 'payid_account_name' => 120, 'bank_account_name' => 120, 'bank_bsb' => 7, 'bank_account_number' => 20, 'stripe_publishable_key' => 255],
    // Each shop's email account, order alerts, inbox and automatic emails are in Admin > Branches > shop > Email.
    'emails'   => ['email_accent_note' => 255, 'daily_email_to' => 190, 'daily_email_minutes_before' => 4, 'reminder_send_hour' => 2],
    'homepage' => ['hero_eyebrow' => 120, 'hero_title' => 120, 'hero_subtitle' => 400, 'announcement_text' => 200, 'google_rating' => 3, 'google_review_count' => 10, 'google_reviews_url' => 500],
    'seo'      => ['social_facebook' => 255, 'social_instagram' => 255, 'social_pinterest' => 255, 'seo_home_title' => 160, 'seo_home_description' => 300, 'seo_ga4_id' => 20, 'seo_google_verification' => 120, 'seo_bing_verification' => 120],
];

const SETTINGS_SWITCHES = [
    'ordering' => ['pickup_enabled', 'delivery_enabled', 'ordering_paused'],
    'payments' => ['payment_stripe_enabled', 'payment_payid_enabled', 'payment_bank_enabled'],
    'emails'   => ['send_customer_emails', 'daily_email_enabled'],
    'store'    => ['gst_registered', 'maintenance_mode'],
];

if (($_GET['tab'] ?? '') === 'homepage') {
    redirect(admin_url('homepage'));
}
$tab = isset(SETTINGS_TABS[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'store';
$self = admin_url('settings', ['tab' => $tab]);
$errors = [];

if (is_post()) {
    $action = input('action', 'save');

    if ($action === 'daily_now') {
        $ok = send_daily_summary(date('Y-m-d'));
        flash($ok ? 'success' : 'error', $ok ? "Today's morning email was sent to " . daily_email_recipient() . '.' : 'The morning email could not be sent. ' . ($GLOBALS['mail_last_error'] ?? 'Add an email address first.'));
        redirect($self);
    }
    if ($action === 'test_stripe') {
        try {
            $account = stripe_request('GET', 'account');
            $mode = str_starts_with(setting('stripe_secret_key'), 'sk_live') ? 'live' : 'test';
            flash('success', 'Connected to Stripe' . (!empty($account['settings']['dashboard']['display_name']) ? ' as ' . $account['settings']['dashboard']['display_name'] : '') . ' in ' . $mode . ' mode.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect($self);
    }
    if ($action === 'block_date') {
        $date = input('blocked_date');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < date('Y-m-d')) {
            flash('error', 'Please choose a date from today onwards.');
        } else {
            db_run('INSERT INTO blocked_dates (blocked_date, reason) VALUES (?, ?) ON DUPLICATE KEY UPDATE reason = VALUES(reason)', [$date, str_limit(trim(input('reason')), 160) ?: null]);
            flash('success', 'Pickups are now closed on ' . date('l j F', strtotime($date)) . '.');
        }
        redirect($self);
    }
    if ($action === 'unblock_date') {
        db_run('DELETE FROM blocked_dates WHERE id = ?', [post_int('id')]);
        flash('success', 'The date is open for pickups again.');
        redirect($self);
    }

    // Regular save of the current tab.
    $values = [];
    foreach (SETTINGS_FIELDS[$tab] ?? [] as $key => $max) {
        $values[$key] = str_limit(trim(input($key)), $max);
    }
    foreach (SETTINGS_SWITCHES[$tab] ?? [] as $key) {
        $values[$key] = input($key) === '1' ? '1' : '0';
    }

    if ($tab === 'store') {
        if ($values['store_name'] === '') {
            $errors['store_name'] = 'Please enter the shop name.';
        }
        $values['store_abn'] = trim(preg_replace('/(\d{2})(\d{3})(\d{3})(\d{3})/', '$1 $2 $3 $4', preg_replace('/\D/', '', $values['store_abn'])));
        if ($values['store_abn'] !== '' && strlen(str_replace(' ', '', $values['store_abn'])) !== 11) {
            $errors['store_abn'] = 'An ABN has 11 digits.';
        }
        $wa = preg_replace('/\D/', '', $values['whatsapp_number']);
        if ($wa !== '' && !preg_match('/^(0[45]\d{8}|61[45]\d{8}|\d{9,15})$/', $wa)) {
            $errors['whatsapp_number'] = 'Enter the mobile number used for WhatsApp, e.g. 0412 345 678.';
        }
    }

    if ($tab === 'hours') {
        $hours = [];
        foreach (WEEK_DAYS as $day) {
            $open = (string) ($_POST['hours'][$day]['open'] ?? '');
            $close = (string) ($_POST['hours'][$day]['close'] ?? '');
            if (!empty($_POST['hours'][$day]['closed'])) {
                $hours[$day] = null;
                continue;
            }
            if (!preg_match('/^\d{2}:\d{2}$/', $open) || !preg_match('/^\d{2}:\d{2}$/', $close) || $open >= $close) {
                $errors['hours'] = 'Check the times for ' . DAY_NAMES[$day] . '. Closing time must be after opening time.';
                break;
            }
            $hours[$day] = [$open, $close];
        }
        $values['opening_hours'] = json_encode($hours);
    }

    if ($tab === 'ordering') {
        foreach (['min_lead_hours' => [1, 720], 'custom_lead_days' => [0, 60], 'max_advance_days' => [1, 365], 'slot_interval_minutes' => [10, 120], 'last_slot_buffer_minutes' => [0, 240], 'max_orders_per_slot' => [0, 99], 'max_deliveries_per_day' => [0, 999]] as $key => [$min, $max]) {
            $values[$key] = (string) min($max, max($min, (int) $values[$key]));
        }
        $values['delivery_fee'] = number_format(max(0, (float) str_replace(['$', ','], '', $values['delivery_fee'])), 2, '.', '');
        $values['delivery_free_over'] = number_format(max(0, (float) str_replace(['$', ','], '', $values['delivery_free_over'])), 2, '.', '');
        $windows = array_values(array_unique(array_filter(array_map(static fn ($w) => str_limit(trim($w), 40), preg_split('/\R/', input('delivery_windows'))))));
        $values['delivery_windows'] = json_encode(array_slice($windows, 0, 12));
        $values['delivery_min_order'] = number_format(max(0, (float) str_replace(['$', ','], '', $values['delivery_min_order'])), 2, '.', '');
        $values['delivery_postcodes'] = implode(', ', array_filter(array_map('trim', explode(',', $values['delivery_postcodes']))));
        if ($values['delivery_postcodes'] !== '' && !preg_match('/^\d{4}(-\d{4})?(, \d{4}(-\d{4})?)*$/', $values['delivery_postcodes'])) {
            $errors['delivery_postcodes'] = 'Use postcodes or ranges separated by commas, e.g. 2600-2620, 2900-2914.';
        }
        if ($values['ordering_paused_until'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $values['ordering_paused_until'])) {
            $values['ordering_paused_until'] = '';
        }
        if ($values['ordering_paused_message'] === '') {
            $values['ordering_paused_message'] = 'We are not taking online orders right now. Please call us and we will do our best to help.';
        }
        if ($values['pickup_enabled'] === '0' && $values['delivery_enabled'] === '0') {
            $errors['fulfilment'] = 'Keep pickup or delivery switched on, or customers cannot order.';
        }
    }

    if ($tab === 'payments') {
        foreach (['stripe_secret_key' => '/^(sk|rk)_(test|live)_[A-Za-z0-9]+$/', 'stripe_webhook_secret' => '/^whsec_[A-Za-z0-9]+$/'] as $key => $pattern) {
            $new = trim(input($key));
            if (input('clear_' . $key) === '1') {
                $values[$key] = '';
            } elseif ($new !== '') {
                if (!preg_match($pattern, $new)) {
                    $errors[$key] = $key === 'stripe_secret_key' ? 'The secret key starts with sk_live_ or sk_test_.' : 'The signing secret starts with whsec_.';
                }
                $values[$key] = $new;
            }
        }
        if ($values['stripe_publishable_key'] !== '' && !preg_match('/^pk_(test|live)_[A-Za-z0-9]+$/', $values['stripe_publishable_key'])) {
            $errors['stripe_publishable_key'] = 'The publishable key starts with pk_live_ or pk_test_.';
        }
        foreach (['stripe', 'payid', 'bank'] as $method) {
            if ($values["payment_{$method}_label"] === '') {
                $errors["payment_{$method}_label"] = 'Please give each payment method a name.';
            }
        }
        $values['bank_bsb'] = preg_replace('/\D/', '', $values['bank_bsb']);
        if ($values['bank_bsb'] !== '') {
            if (strlen($values['bank_bsb']) !== 6) {
                $errors['bank_bsb'] = 'A BSB has 6 digits.';
            }
            $values['bank_bsb'] = substr($values['bank_bsb'], 0, 3) . '-' . substr($values['bank_bsb'], 3);
        }
        $values['bank_account_number'] = preg_replace('/[^\d]/', '', $values['bank_account_number']);
        $secret = $values['stripe_secret_key'] ?? setting('stripe_secret_key');
        if ($values['payment_stripe_enabled'] === '1' && $secret === '') {
            $errors['payment_stripe_enabled'] = 'Add your Stripe secret key before switching card payments on.';
        }
        if ($values['payment_payid_enabled'] === '1' && $values['payid_identifier'] === '') {
            $errors['payment_payid_enabled'] = 'Add your PayID before switching PayID on.';
        }
        if ($values['payment_bank_enabled'] === '1' && ($values['bank_bsb'] === '' || $values['bank_account_number'] === '')) {
            $errors['payment_bank_enabled'] = 'Add the BSB and account number before switching bank transfer on.';
        }
        if (!$errors && $values['payment_stripe_enabled'] === '0' && $values['payment_payid_enabled'] === '0' && $values['payment_bank_enabled'] === '0') {
            $errors['payments'] = 'Keep at least one payment method switched on, or customers cannot check out.';
        }
    }

    if ($tab === 'emails') {
        if ($values['daily_email_to'] !== '' && !valid_email($values['daily_email_to'])) {
            $errors['daily_email_to'] = 'Please enter a valid email address.';
        }
        $values['daily_email_minutes_before'] = (string) min(600, max(0, (int) $values['daily_email_minutes_before']));
        $values['reminder_send_hour'] = (string) min(21, max(6, (int) $values['reminder_send_hour']));
    }

    if ($tab === 'homepage') {
        $rating = (float) $values['google_rating'];
        $values['google_rating'] = $rating > 0 ? number_format(min(5, $rating), 1) : '';
        if ($values['google_reviews_url'] !== '' && !safe_web_url($values['google_reviews_url'])) {
            $errors['google_reviews_url'] = 'Please paste the full link, starting with https://';
        }
        if (!$errors) {
            [$hero, $heroError] = save_public_image('hero_upload', 'gallery', 1080, 1440, 'hero');
            if ($heroError) {
                $errors['hero_upload'] = $heroError;
            } elseif ($hero) {
                $values['hero_image'] = $hero;
            }
        }
    }

    if ($tab === 'seo') {
        $values['seo_ga4_id'] = strtoupper($values['seo_ga4_id']);
        if ($values['seo_ga4_id'] !== '' && !preg_match('/^G-[A-Z0-9]{4,}$/', $values['seo_ga4_id'])) {
            $errors['seo_ga4_id'] = 'The Google Analytics ID looks like G-ABC123XYZ.';
        }
        // Accept either the code or the whole <meta> tag Google gives you.
        foreach (['seo_google_verification', 'seo_bing_verification'] as $key) {
            if (preg_match('/content="([^"]+)"/', $values[$key], $m)) {
                $values[$key] = $m[1];
            }
            $values[$key] = preg_replace('/[^A-Za-z0-9_-]/', '', $values[$key]);
        }
        $lines = [];
        foreach (preg_split('/\R/', input('seo_redirects')) as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) === 2 && $parts[0] !== '') {
                $lines[] = '/' . trim(parse_url($parts[0], PHP_URL_PATH) ?: $parts[0], '/') . ' ' . (preg_match('#^https?://#', $parts[1]) ? $parts[1] : '/' . trim($parts[1], '/'));
            } elseif (trim($line) !== '') {
                $errors['seo_redirects'] = 'Each line needs an old address and a new address, separated by a space.';
            }
        }
        $values['seo_redirects'] = implode("\n", $lines);
        foreach (['social_facebook', 'social_instagram', 'social_pinterest'] as $key) {
            if ($values[$key] !== '' && !safe_web_url($values[$key])) {
                $errors[$key] = 'Please paste the full link, starting with https://';
            }
        }
    }

    if (!$errors) {
        foreach ($values as $key => $value) {
            setting_save($key, $value);
        }
        flash('success', SETTINGS_TABS[$tab] . ' saved.');
        redirect($self);
    }
    // Show the submitted values again, without the secret keys.
    unset($values['stripe_secret_key'], $values['stripe_webhook_secret'], $values['smtp_password_encrypted']);
    $posted = $values;
}

$s = array_merge(settings_all(), $posted ?? []);
$hours = $tab === 'hours' && isset($posted['opening_hours']) ? json_decode($posted['opening_hours'], true) : opening_hours();
$blocked = $tab === 'ordering' ? db_all('SELECT * FROM blocked_dates WHERE blocked_date >= CURDATE() ORDER BY blocked_date') : [];
$methods = payment_method_definitions();
admin_view('settings', compact('tab', 's', 'errors', 'hours', 'blocked', 'methods') + ['title' => 'Settings']);
