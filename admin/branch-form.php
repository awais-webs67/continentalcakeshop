<?php
// Saving a branch, one tab at a time (Admin > Branches > a branch). Owners only.
// Loaded by admin/bootstrap.php.

const BRANCH_TABS = [
    'overview' => 'Overview',
    'details'  => 'Shop details',
    'email'    => 'Email',
    'payments' => 'Payments',
    'page'     => 'Website page',
    'logins'   => 'Logins',
];

/** Keeps what was typed when a tab has errors, so nothing has to be typed again. */
function branch_remember(array $data): void
{
    remember_input(array_map(static fn ($v) => (string) $v, $data));
}

/** Shop details: name, address, phone, map. $branch null = a new branch. Returns errors. */
function branch_save_details(?array $branch): array
{
    $fields = ['name' => 80, 'street' => 120, 'suburb' => 80, 'state' => 10, 'postcode' => 10, 'phone' => 40, 'latitude' => 12, 'longitude' => 12, 'maps_url' => 500];
    $data = [];
    foreach ($fields as $field => $max) {
        $data[$field] = str_limit(trim(input($field)), $max);
    }
    $data['slug'] = slugify(input('slug') !== '' ? input('slug') : $data['name']);
    $data['is_active'] = $branch && (int) $branch['is_main'] ? 1 : (input('is_active') === '1' ? 1 : 0);

    $errors = [];
    if ($data['name'] === '' || $data['suburb'] === '') {
        $errors[] = 'Please enter the shop name and suburb.';
    }
    if ($data['slug'] === '' || db_value('SELECT 1 FROM branches WHERE slug = ? AND id <> ?', [$data['slug'], (int) ($branch['id'] ?? 0)])) {
        $errors[] = 'That page address is already used by another shop. Try another one.';
    }
    if ($data['maps_url'] !== '' && !safe_web_url($data['maps_url'])) {
        $errors[] = 'The Google Maps link must start with https://';
    }
    foreach (['latitude' => [-90, 90], 'longitude' => [-180, 180]] as $field => [$min, $max]) {
        if ($data[$field] !== '' && (!is_numeric($data[$field]) || (float) $data[$field] < $min || (float) $data[$field] > $max)) {
            $errors[] = 'The map position must be numbers like -35.4189 and 149.0658.';
            break;
        }
    }
    if ($data['is_active'] && ($data['street'] === '' || $data['phone'] === '')) {
        $errors[] = 'Add the street address and phone before showing this shop to customers.';
    }
    if ($errors) {
        branch_remember($data);
        return $errors;
    }
    if ($branch) {
        $sets = implode(', ', array_map(static fn ($f) => "`{$f}` = ?", array_keys($data)));
        db_run("UPDATE branches SET {$sets} WHERE id = ?", [...array_values($data), $branch['id']]);
        // The main shop's address and phone are also the ones in Settings > Shop details.
        if ((int) $branch['is_main']) {
            foreach (['street', 'suburb', 'state', 'postcode', 'phone', 'latitude', 'longitude'] as $field) {
                setting_save('store_' . $field, $data[$field]);
            }
            setting_save('store_phone_e164', phone_e164($data['phone']));
        }
    } else {
        $data['sort_order'] = count(branches()) + 1;
        $columns = array_keys($data);
        db_run('INSERT INTO branches (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
        $GLOBALS['new_branch_id'] = (int) db()->lastInsertId();
    }
    forget_input();
    branches(true);
    return [];
}

/** Website page: the "about" text and the photo. */
function branch_save_page(array $branch): array
{
    $data = ['intro' => clean_html(str_limit(input('intro'), 8000))];
    [$photo, $photoError] = save_public_image('photo', 'branches', 1200, 900, $branch['name'] . ' cake shop');
    if ($photoError) {
        return [$photoError];
    }
    if ($photo) {
        $data['photo'] = $photo;
    } elseif (input('remove_photo') === '1') {
        $data['photo'] = null;
    }
    $sets = implode(', ', array_map(static fn ($f) => "`{$f}` = ?", array_keys($data)));
    db_run("UPDATE branches SET {$sets} WHERE id = ?", [...array_values($data), $branch['id']]);
    if (array_key_exists('photo', $data) && $branch['photo'] && $branch['photo'] !== $data['photo']) {
        @unlink(BASE_PATH . '/public/uploads/' . $branch['photo']);
    }
    branches(true);
    return [];
}

/**
 * Email tab: which account the shop sends from and reads its inbox from, where order alerts go,
 * and its automatic emails.
 */
function branch_save_email(array $branch): array
{
    $id = (int) $branch['id'];
    $own = input('email_mode') === 'own';
    $errors = [];

    $alerts = strtolower(str_limit(trim(input('alerts_email')), 190));
    if ($alerts !== '' && !valid_email($alerts)) {
        $errors[] = 'The order alerts address does not look right.';
    }
    $reviews = str_limit(trim(input('reviews_url')), 500);
    if ($reviews !== '' && !safe_web_url($reviews)) {
        $errors[] = 'The Google review link must start with https://';
    }

    $account = [];
    if ($own) {
        $provider = isset(EMAIL_PROVIDERS[input('mail_provider')]) ? input('mail_provider') : 'other';
        [, $smtpHost, $smtpPort, $smtpSecurity, $imapHost, $imapPort] = EMAIL_PROVIDERS[$provider];
        $address = strtolower(str_limit(trim(input('mail_from_address')), 190));
        $account = [
            'mail_provider'     => $provider,
            'mail_driver'       => 'smtp',
            'mail_from_address' => $address,
            'mail_from_name'    => str_limit(trim(input('mail_from_name')), 120),
            'smtp_username'     => $address,
            'smtp_host'         => $provider === 'other' ? str_limit(trim(input('smtp_host')), 190) : $smtpHost,
            'smtp_port'         => (string) ($provider === 'other' ? max(1, (int) input('smtp_port', '465')) : $smtpPort),
            'smtp_encryption'   => $provider === 'other' ? (in_array(input('smtp_encryption'), ['ssl', 'tls', 'none'], true) ? input('smtp_encryption') : 'ssl') : $smtpSecurity,
            'imap_host'         => $provider === 'other' ? str_limit(trim(input('imap_host')), 190) : $imapHost,
            'imap_port'         => (string) ($provider === 'other' ? max(1, (int) input('imap_port', '993')) : $imapPort),
        ];
        if (!valid_email($address)) {
            $errors[] = 'Enter the shop\'s email address.';
        }
        if ($account['smtp_host'] === '') {
            $errors[] = 'Enter the sending (SMTP) server.';
        }
        $password = (string) ($_POST['mail_password'] ?? '');
        if ($password !== '') {
            try {
                $account['smtp_password_encrypted'] = secret_encrypt($password);
            } catch (Throwable $e) {
                $errors[] = 'The password could not be stored safely: ' . $e->getMessage();
            }
        } elseif ((string) branch_setting($id, 'smtp_password_encrypted') === '') {
            $errors[] = 'Enter the password of the email account.';
        }
    }

    $inbox = input('inbox_enabled') === '1';
    if ($inbox && $own && ($account['imap_host'] ?? '') === '') {
        $errors[] = 'Enter the inbox (IMAP) server to show this inbox in Messages.';
    }
    $sharedImap = str_limit(trim(input('shared_imap_host')), 190);
    if ($inbox && !$own && !(int) $branch['is_main']) {
        $inbox = false; // the shared inbox belongs to the main shop
    }
    if ($inbox && !$own && $sharedImap === '' && setting_raw('imap_host') === '') {
        $errors[] = 'Enter the inbox (IMAP) server of the shared account to show it in Messages.';
    }

    if ($errors) {
        branch_remember(['email_mode' => $own ? 'own' : 'shared', 'alerts_email' => $alerts, 'reviews_url' => $reviews] + $account);
        return $errors;
    }

    // Which inbox is read now, to know below if it changed.
    $inboxBefore = (branch_uses_own($id, 'email') ? 'own' : 'shared') . '|' . branch_setting($id, 'mail_from_address') . '|' . branch_setting($id, 'imap_host');
    branch_setting_save($id, 'email_own', $own ? '1' : '0');
    foreach ($account as $key => $value) {
        branch_setting_save($id, $key, $value);
    }
    $sharedChanged = !$own && $sharedImap !== '' && $sharedImap !== setting_raw('imap_host');
    if (!$own && (int) $branch['is_main'] && $sharedImap !== '') {
        setting_save('imap_host', $sharedImap);
        setting_save('imap_port', (string) max(1, (int) input('shared_imap_port', '993')));
    }
    // Switching the inbox on (or to another account) starts from the newest email.
    $inboxNow = ($own ? 'own' : 'shared') . '|' . branch_setting($id, 'mail_from_address') . '|' . branch_setting($id, 'imap_host');
    if ($inbox && (branch_setting($id, 'inbox_enabled') !== '1' || $inboxNow !== $inboxBefore || $sharedChanged)) {
        branch_setting_save($id, 'inbox_last_uid', null);
        branch_setting_save($id, 'inbox_uidvalidity', null);
    }
    branch_setting_save($id, 'inbox_enabled', $inbox ? '1' : '0');

    db_run('UPDATE branches SET email = ?, reviews_url = ? WHERE id = ?', [$alerts, $reviews, $id]);
    branch_setting_save($id, 'reminder_email_enabled', input('reminder_email_enabled') === '1' ? '1' : '0');
    branch_setting_save($id, 'review_email_enabled', input('review_email_enabled') === '1' ? '1' : '0');
    branch_setting_save($id, 'review_delay_hours', (string) min(240, max(1, (int) input('review_delay_hours', '20'))));
    branch_setting_save($id, 'daily_branch_enabled', input('daily_branch_enabled') === '1' ? '1' : '0');
    $dailyTo = strtolower(trim(input('daily_branch_to')));
    branch_setting_save($id, 'daily_branch_to', valid_email($dailyTo) ? $dailyTo : '');
    forget_input();
    branches(true);
    return [];
}

/** Payments tab: the shared payment details, or the shop's own. */
function branch_save_payments(array $branch): array
{
    $id = (int) $branch['id'];
    $own = input('payments_mode') === 'own';
    $errors = [];
    $values = [];
    if ($own) {
        foreach (['payid_identifier' => 120, 'payid_account_name' => 120, 'bank_account_name' => 120, 'bank_bsb' => 7, 'bank_account_number' => 20, 'stripe_publishable_key' => 255] as $key => $max) {
            $values[$key] = str_limit(trim(input($key)), $max);
        }
        foreach (['payment_payid_enabled', 'payment_bank_enabled', 'payment_stripe_enabled'] as $key) {
            $values[$key] = input($key) === '1' ? '1' : '0';
        }
        foreach (['stripe_secret_key' => '/^(sk|rk)_(test|live)_[A-Za-z0-9]+$/', 'stripe_webhook_secret' => '/^whsec_[A-Za-z0-9]+$/'] as $key => $pattern) {
            $typed = trim((string) ($_POST[$key] ?? ''));
            if ($typed !== '') {
                if (!preg_match($pattern, $typed)) {
                    $errors[] = $key === 'stripe_secret_key' ? 'The Stripe secret key starts with sk_live_ or sk_test_.' : 'The Stripe signing secret starts with whsec_.';
                }
                $values[$key] = $typed;
            }
        }
        if ($values['payment_payid_enabled'] === '1' && $values['payid_identifier'] === '') {
            $errors[] = 'Enter the PayID, or switch PayID off.';
        }
        if ($values['payment_bank_enabled'] === '1' && ($values['bank_bsb'] === '' || $values['bank_account_number'] === '')) {
            $errors[] = 'Enter the BSB and account number, or switch bank transfer off.';
        }
        if ($values['payment_stripe_enabled'] === '1' && ($values['stripe_secret_key'] ?? (string) branch_setting($id, 'stripe_secret_key')) === '') {
            $errors[] = 'Enter the Stripe secret key, or switch card payments off.';
        }
        if (!array_intersect([$values['payment_payid_enabled'], $values['payment_bank_enabled'], $values['payment_stripe_enabled']], ['1'])) {
            $errors[] = 'Switch on at least one way to pay, or use the shared payment details.';
        }
    }
    if ($errors) {
        branch_remember(['payments_mode' => $own ? 'own' : 'shared'] + array_diff_key($values, ['stripe_secret_key' => 1, 'stripe_webhook_secret' => 1]));
        return $errors;
    }
    branch_setting_save($id, 'payments_own', $own ? '1' : '0');
    foreach ($values as $key => $value) {
        branch_setting_save($id, $key, $value);
    }
    forget_input();
    return [];
}

/** Sends a test email from the shop's account to the inbox its order alerts go to. */
function branch_send_test_email(array $branch, array $admin): void
{
    $to = branch_notify_email((int) $branch['id']);
    if (!valid_email($to)) {
        flash('error', 'There is no order alerts address for ' . $branch['name'] . ' yet.');
        return;
    }
    $GLOBALS['mail_last_error'] = null;
    $ok = with_branch((int) $branch['id'], static fn () => send_mail($to, 'Test email for the ' . $branch['name'] . ' shop - ' . setting('store_short_name'),
        render_email('test', ['title' => 'Emails for the ' . $branch['name'] . ' shop work', 'preheader' => 'New orders for ' . $branch['name'] . ' will arrive in this inbox.', 'admin' => $admin])));
    $reason = $GLOBALS['mail_last_error'] ?? '';
    $from = with_branch((int) $branch['id'], static fn () => mail_from()[0]);
    flash($ok ? 'success' : 'error', $ok
        ? 'Test email sent from ' . $from . ' to ' . $to . '. Check that inbox (and its spam folder).'
        : 'The test email could not be sent from ' . $from . '.' . ($reason !== '' ? ' The server said: ' . $reason : ' Check the email account details.'));
}

/** Numbers for the branch cards and Overview: due today, new orders, requests, unread, this month, logins. */
function branch_stats(): array
{
    $today = date('Y-m-d');
    $month = date('Y-m-01');
    $stats = [];
    foreach (branches() as $b) {
        $stats[(int) $b['id']] = ['due_today' => 0, 'new_orders' => 0, 'month_sales' => 0.0, 'month_orders' => 0, 'requests' => 0, 'unread' => 0, 'logins' => []];
    }
    foreach (db_all(
        "SELECT branch_id,
                SUM(pickup_date = ? AND order_status <> 'cancelled') AS due_today,
                SUM(order_status = 'new') AS new_orders,
                SUM(CASE WHEN order_status <> 'cancelled' AND created_at >= ? THEN total ELSE 0 END) AS month_sales,
                SUM(order_status <> 'cancelled' AND created_at >= ?) AS month_orders
         FROM orders GROUP BY branch_id",
        [$today, $month, $month]
    ) as $row) {
        if (isset($stats[(int) $row['branch_id']])) {
            $stats[(int) $row['branch_id']] = array_merge($stats[(int) $row['branch_id']], array_map('floatval', array_diff_key($row, ['branch_id' => 1])));
        }
    }
    foreach (db_all("SELECT branch_id, COUNT(*) AS n FROM custom_requests WHERE status IN ('new', 'quoted') GROUP BY branch_id") as $row) {
        if (isset($stats[(int) $row['branch_id']])) {
            $stats[(int) $row['branch_id']]['requests'] = (int) $row['n'];
        }
    }
    foreach (db_all('SELECT branch_id, COUNT(*) AS n FROM threads WHERE unread_admin = 1 GROUP BY branch_id') as $row) {
        if (isset($stats[(int) $row['branch_id']])) {
            $stats[(int) $row['branch_id']]['unread'] = (int) $row['n'];
        }
    }
    foreach (db_all("SELECT id, name, email, role, branch_id, permissions, is_active, last_login_at FROM admins WHERE role <> 'owner' AND branch_id IS NOT NULL ORDER BY name") as $row) {
        if (isset($stats[(int) $row['branch_id']])) {
            $stats[(int) $row['branch_id']]['logins'][] = $row;
        }
    }
    return $stats;
}

/** What the Overview tab lists as done or still to do for a shop. */
function branch_checklist(array $b): array
{
    $id = (int) $b['id'];
    $ownEmail = branch_uses_own($id, 'email');
    return [
        ['Address and phone', trim($b['street']) !== '' && trim(branch_phone($b)) !== '', 'details'],
        ['Shown to customers', (bool) $b['is_active'], 'details'],
        ['Email account', $ownEmail ? branch_account_email($id) !== '' : true, 'email', $ownEmail ? 'Own: ' . (branch_account_email($id) ?: 'not finished') : 'Shared account (Settings > Emails)'],
        ['Inbox in Messages', inbox_enabled($b) && (string) branch_setting($id, 'inbox_error') === '', 'email', inbox_enabled($b) ? ((string) branch_setting($id, 'inbox_error') !== '' ? 'Problem: ' . branch_setting($id, 'inbox_error') : 'On') : 'Off'],
        ['Payments', true, 'payments', branch_uses_own($id, 'payments') ? 'Own payment details' : 'Shared payment details'],
        ['Google review link', trim((string) $b['reviews_url']) !== '', 'email'],
    ];
}
