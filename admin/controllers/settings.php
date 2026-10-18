<?php
$admin = require_admin();

const SETTINGS_TABS = [
    'store'    => 'Shop details',
    'hours'    => 'Opening hours',
    'ordering' => 'Ordering & pickup',
    'payments' => 'Payments',
    'emails'   => 'Emails',
    'homepage' => 'Homepage',
    'seo'      => 'Social & SEO',
];

// Plain text settings each tab may change. Anything not listed here is ignored.
const SETTINGS_FIELDS = [
    'store'    => ['store_name' => 120, 'store_short_name' => 80, 'store_phone' => 40, 'store_email' => 190, 'store_street' => 120, 'store_suburb' => 80, 'store_state' => 10, 'store_postcode' => 10, 'store_founded' => 4],
    'ordering' => ['min_lead_hours' => 4, 'custom_lead_days' => 3, 'max_advance_days' => 4, 'slot_interval_minutes' => 3, 'last_slot_buffer_minutes' => 4, 'delivery_fee' => 10, 'delivery_note' => 255],
    'payments' => ['payid_identifier' => 120, 'payid_account_name' => 120, 'bank_account_name' => 120, 'bank_bsb' => 7, 'bank_account_number' => 20, 'stripe_publishable_key' => 255],
    'emails'   => ['order_notification_email' => 190, 'email_accent_note' => 255],
    'homepage' => ['hero_eyebrow' => 120, 'hero_title' => 120, 'hero_subtitle' => 400, 'announcement_text' => 200, 'google_rating' => 3, 'google_review_count' => 10, 'google_reviews_url' => 500],
    'seo'      => ['social_facebook' => 255, 'social_instagram' => 255, 'social_pinterest' => 255, 'seo_home_title' => 160, 'seo_home_description' => 300],
];

const SETTINGS_SWITCHES = [
    'ordering' => ['pickup_enabled', 'delivery_enabled'],
    'payments' => ['payment_stripe_enabled', 'payment_payid_enabled', 'payment_bank_enabled'],
    'emails'   => ['send_customer_emails'],
];

$tab = isset(SETTINGS_TABS[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'store';
$self = admin_url('settings', ['tab' => $tab]);
$errors = [];

if (is_post()) {
    $action = input('action', 'save');

    if ($action === 'test_email') {
        $html = render_email('test', ['title' => 'Your shop emails are working', 'preheader' => 'This is a test email from the admin panel', 'admin' => $admin]);
        $ok = send_mail($admin['email'], 'Test email from ' . setting('store_short_name'), $html);
        flash($ok ? 'success' : 'error', $ok ? 'A test email was sent to ' . $admin['email'] . '.' : 'The test email could not be sent. Check the mail settings in config/config.php.');
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
        if (!valid_email($values['store_email'])) {
            $errors['store_email'] = 'Please enter a valid email address.';
        }
        $digits = preg_replace('/\D/', '', $values['store_phone']);
        $values['store_phone_e164'] = $digits !== '' ? '+61' . ltrim(str_starts_with($digits, '61') ? substr($digits, 2) : $digits, '0') : '';
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
        foreach (['min_lead_hours' => [0, 720], 'custom_lead_days' => [0, 60], 'max_advance_days' => [1, 365], 'slot_interval_minutes' => [10, 120], 'last_slot_buffer_minutes' => [0, 240]] as $key => [$min, $max]) {
            $values[$key] = (string) min($max, max($min, (int) $values[$key]));
        }
        $values['delivery_fee'] = number_format(max(0, (float) str_replace(['$', ','], '', $values['delivery_fee'])), 2, '.', '');
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

    if ($tab === 'emails' && $values['order_notification_email'] !== '' && !valid_email($values['order_notification_email'])) {
        $errors['order_notification_email'] = 'Please enter a valid email address.';
    }

    if ($tab === 'homepage') {
        $rating = (float) $values['google_rating'];
        $values['google_rating'] = $rating > 0 ? number_format(min(5, $rating), 1) : '';
        if ($values['google_reviews_url'] !== '' && !filter_var($values['google_reviews_url'], FILTER_VALIDATE_URL)) {
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
        foreach (['social_facebook', 'social_instagram', 'social_pinterest'] as $key) {
            if ($values[$key] !== '' && !filter_var($values[$key], FILTER_VALIDATE_URL)) {
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
    unset($values['stripe_secret_key'], $values['stripe_webhook_secret']);
    $posted = $values;
}

$s = array_merge(settings_all(), $posted ?? []);
$hours = $tab === 'hours' && isset($posted['opening_hours']) ? json_decode($posted['opening_hours'], true) : opening_hours();
$blocked = $tab === 'ordering' ? db_all('SELECT * FROM blocked_dates WHERE blocked_date >= CURDATE() ORDER BY blocked_date') : [];
$methods = payment_method_definitions();
admin_view('settings', compact('tab', 's', 'errors', 'hours', 'blocked', 'methods') + ['title' => 'Settings']);
