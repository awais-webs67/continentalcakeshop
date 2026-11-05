<?php
// Saving a branch, shared by Admin > Branches (owner, every field) and My shop (a branch manager,
// only the contact details and the branch page). Loaded by admin/bootstrap.php.

const BRANCH_TEXT_FIELDS = [
    'name' => 80, 'street' => 120, 'suburb' => 80, 'state' => 10, 'postcode' => 10, 'phone' => 40,
    'email' => 190, 'latitude' => 12, 'longitude' => 12, 'maps_url' => 500, 'reviews_url' => 500,
];
// What a branch manager may change on their own shop. The address, name and page address stay with the owner.
const BRANCH_MANAGER_FIELDS = ['phone', 'email', 'maps_url', 'reviews_url'];

/**
 * Checks and saves the posted branch form. $branch null = a new branch. $limited = a branch
 * manager editing their own shop. Returns the error messages (empty when saved).
 */
function branch_form_save(?array $branch, bool $limited): array
{
    $fields = $limited ? array_intersect_key(BRANCH_TEXT_FIELDS, array_flip(BRANCH_MANAGER_FIELDS)) : BRANCH_TEXT_FIELDS;
    $data = [];
    foreach ($fields as $field => $max) {
        $data[$field] = str_limit(trim(input($field)), $max);
    }
    $data['intro'] = clean_html(str_limit(input('intro'), 8000));
    if (!$limited) {
        $data['slug'] = slugify(input('slug') !== '' ? input('slug') : $data['name']);
        $data['is_active'] = $branch && (int) $branch['is_main'] ? 1 : (input('is_active') === '1' ? 1 : 0);
    }
    $check = $data + ($branch ?? []);

    $errors = [];
    if (!$limited && ($data['name'] === '' || $data['suburb'] === '')) {
        $errors[] = 'Please enter the branch name and suburb.';
    }
    if (!$limited && ($data['slug'] === '' || db_value('SELECT 1 FROM branches WHERE slug = ? AND id <> ?', [$data['slug'], (int) ($branch['id'] ?? 0)]))) {
        $errors[] = 'That page address is already used by another branch. Try another one.';
    }
    if ($data['email'] !== '' && !valid_email($data['email'])) {
        $errors[] = 'The order email does not look right.';
    }
    foreach (['maps_url' => 'Google Maps link', 'reviews_url' => 'Google review link'] as $field => $label) {
        if ($data[$field] !== '' && !safe_web_url($data[$field])) {
            $errors[] = 'The ' . $label . ' must start with https://';
        }
    }
    foreach (['latitude' => [-90, 90], 'longitude' => [-180, 180]] as $field => [$min, $max]) {
        if (isset($data[$field]) && $data[$field] !== '' && (!is_numeric($data[$field]) || (float) $data[$field] < $min || (float) $data[$field] > $max)) {
            $errors[] = 'The map position must be numbers like -35.4189 and 149.0658.';
            break;
        }
    }
    // A shop customers can choose needs an address and a phone for Google and the emails.
    if (!empty($check['is_active']) && (trim((string) $check['street']) === '' || (trim((string) $check['phone']) === '' && empty($check['is_main'])))) {
        $errors[] = 'Add the street address and phone before showing this shop to customers.';
    }
    if (!$errors) {
        [$photo, $photoError] = save_public_image('photo', 'branches', 1200, 900, ($check['name'] ?? 'shop') . ' cake shop');
        if ($photoError) {
            $errors[] = $photoError;
        } elseif ($photo) {
            $data['photo'] = $photo;
        } elseif ($branch && input('remove_photo') === '1') {
            $data['photo'] = null;
        }
    }
    if ($errors) {
        remember_input(array_map(static fn ($v) => (string) $v, $data));
        return $errors;
    }

    if ($branch) {
        $sets = implode(', ', array_map(static fn ($f) => "`{$f}` = ?", array_keys($data)));
        db_run("UPDATE branches SET {$sets} WHERE id = ?", [...array_values($data), $branch['id']]);
        if (array_key_exists('photo', $data) && $branch['photo'] && $branch['photo'] !== $data['photo']) {
            @unlink(BASE_PATH . '/public/uploads/' . $branch['photo']);
        }
        // The main shop's address and phone are the ones in Settings > Shop details: keep them the same.
        if ((int) $branch['is_main']) {
            foreach (['street', 'suburb', 'state', 'postcode', 'phone', 'latitude', 'longitude'] as $field) {
                if (array_key_exists($field, $data)) {
                    setting_save('store_' . $field, $data[$field]);
                }
            }
            setting_save('store_phone_e164', phone_e164($data['phone']));
        }
    } else {
        $data['sort_order'] = count(branches()) + 1;
        $columns = array_keys($data);
        db_run('INSERT INTO branches (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
    }
    forget_input();
    branches(true);
    return [];
}

/** Sends a test email to the inbox a branch's orders go to. */
function branch_send_test_email(array $branch, array $admin): void
{
    $to = branch_notify_email((int) $branch['id']);
    if (!valid_email($to)) {
        flash('error', 'There is no order email for ' . $branch['name'] . ' yet.');
        return;
    }
    $ok = send_mail($to, 'Test email for the ' . $branch['name'] . ' shop - ' . setting('store_short_name'),
        render_email('test', ['title' => 'Emails for the ' . $branch['name'] . ' shop work', 'preheader' => 'New orders for ' . $branch['name'] . ' will arrive in this inbox.', 'admin' => $admin]));
    $reason = $GLOBALS['mail_last_error'] ?? '';
    flash($ok ? 'success' : 'error', $ok
        ? 'Test email sent to ' . $to . '. Check that inbox (and its spam folder).'
        : 'The test email could not be sent.' . ($reason !== '' ? ' The server said: ' . $reason : ' Check Settings > Emails.'));
}

/** Numbers for the branch cards: due today, new orders, open requests, unread messages, this month, and its logins. */
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
        $stats[(int) $row['branch_id']]['requests'] = (int) $row['n'];
    }
    foreach (db_all('SELECT branch_id, COUNT(*) AS n FROM threads WHERE unread_admin = 1 GROUP BY branch_id') as $row) {
        $stats[(int) $row['branch_id']]['unread'] = (int) $row['n'];
    }
    foreach (db_all("SELECT id, name, email, role, branch_id, permissions, is_active FROM admins WHERE role <> 'owner' AND branch_id IS NOT NULL ORDER BY name") as $row) {
        if (isset($stats[(int) $row['branch_id']])) {
            $stats[(int) $row['branch_id']]['logins'][] = $row;
        }
    }
    return $stats;
}
