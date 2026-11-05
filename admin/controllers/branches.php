<?php
// Super admin: the shops, their details for customers and Google, and a quick look at each one.
require_admin('owner');

const BRANCH_TEXT_FIELDS = [
    'name' => 80, 'street' => 120, 'suburb' => 80, 'state' => 10, 'postcode' => 10, 'phone' => 40,
    'email' => 190, 'latitude' => 12, 'longitude' => 12, 'maps_url' => 500, 'reviews_url' => 500,
];

if (is_post()) {
    $id = post_int('id');
    $branch = $id ? get_branch($id) : null;
    $action = input('action');

    if ($action === 'save') {
        $data = [];
        foreach (BRANCH_TEXT_FIELDS as $field => $max) {
            $data[$field] = str_limit(trim(input($field)), $max);
        }
        $data['intro'] = clean_html(str_limit(input('intro'), 8000));
        $data['slug'] = slugify(input('slug') !== '' ? input('slug') : $data['name']);
        $data['is_active'] = input('is_active') === '1' ? 1 : 0;
        $data['sort_order'] = (int) input('sort_order', '0');

        $errors = [];
        if ($data['name'] === '' || $data['suburb'] === '') {
            $errors[] = 'Please enter the branch name and suburb.';
        }
        if ($data['slug'] === '' || db_value('SELECT 1 FROM branches WHERE slug = ? AND id <> ?', [$data['slug'], $id])) {
            $errors[] = 'That web address is already used by another branch. Try another one.';
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
            if ($data[$field] !== '' && (!is_numeric($data[$field]) || (float) $data[$field] < $min || (float) $data[$field] > $max)) {
                $errors[] = 'The map position (' . $field . ') must be a number, e.g. -35.4189.';
            }
        }
        // A branch customers can choose needs an address and a phone for Google and the emails.
        if ($data['is_active'] && ($data['street'] === '' || ($data['phone'] === '' && !($branch && (int) $branch['is_main'])))) {
            $errors[] = 'Add the street address and phone before showing this branch to customers.';
        }
        if ($branch && (int) $branch['is_main'] && !$data['is_active']) {
            $errors[] = 'The main shop is always shown. Hide the other branch instead.';
        }
        if (!$errors) {
            [$photo, $photoError] = save_public_image('photo', 'branches', 1200, 900, $data['name'] . ' cake shop');
            if ($photoError) {
                $errors[] = $photoError;
            }
        }
        if ($errors) {
            remember_input($data);
            flash('error', implode(' ', $errors));
            redirect(admin_url('branches', ['edit' => $id ?: 'new']));
        }
        if (!empty($photo)) {
            $data['photo'] = $photo;
        } elseif ($branch && input('remove_photo') === '1') {
            $data['photo'] = null;
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
                    setting_save('store_' . $field, $data[$field]);
                }
                setting_save('store_phone_e164', phone_e164($data['phone']));
            }
            flash('success', $data['name'] . ' saved.');
        } else {
            $columns = array_keys($data);
            db_run('INSERT INTO branches (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
            flash('success', $data['name'] . ' added. ' . ($data['is_active'] ? 'Customers can choose it now.' : 'Switch on "Show to customers" when it is ready.'));
        }
        forget_input();
        branches(true);
        redirect(admin_url('branches'));
    }

    if ($branch && $action === 'toggle' && !(int) $branch['is_main']) {
        if (!$branch['is_active'] && (trim($branch['street']) === '' || trim($branch['phone']) === '')) {
            flash('error', 'Add the street address and phone of ' . $branch['name'] . ' first (Edit).');
        } else {
            db_run('UPDATE branches SET is_active = 1 - is_active WHERE id = ?', [$branch['id']]);
            flash('success', $branch['name'] . ($branch['is_active'] ? ' is hidden from customers. Its orders stay in the panel.' : ' is now shown to customers.'));
        }
        redirect(admin_url('branches'));
    }
    redirect(admin_url('branches'));
}

// Today and this month for every branch, for the overview cards.
$today = date('Y-m-d');
$month = date('Y-m-01');
$stats = [];
foreach (db_all(
    "SELECT branch_id,
            SUM(pickup_date = ? AND order_status <> 'cancelled') AS due_today,
            SUM(order_status = 'new') AS new_orders,
            SUM(CASE WHEN order_status <> 'cancelled' AND created_at >= ? THEN total ELSE 0 END) AS month_sales,
            SUM(order_status <> 'cancelled' AND created_at >= ?) AS month_orders
     FROM orders GROUP BY branch_id",
    [$today, $month, $month]
) as $row) {
    $stats[(int) $row['branch_id']] = $row;
}
foreach (db_all("SELECT branch_id, COUNT(*) AS n FROM custom_requests WHERE status IN ('new', 'quoted') GROUP BY branch_id") as $row) {
    $stats[(int) $row['branch_id']]['requests'] = $row['n'];
}
foreach (db_all('SELECT branch_id, COUNT(*) AS n FROM threads WHERE unread_admin = 1 GROUP BY branch_id') as $row) {
    $stats[(int) $row['branch_id']]['unread'] = $row['n'];
}
$staff = [];
foreach (db_all('SELECT id, name, email, role, branch_id, is_active FROM admins ORDER BY name') as $row) {
    $staff[$row['role'] === 'owner' || !$row['branch_id'] ? 0 : (int) $row['branch_id']][] = $row;
}

$editing = null;
if (isset($_GET['edit'])) {
    $editing = $_GET['edit'] === 'new'
        ? ['id' => 0, 'slug' => '', 'name' => '', 'street' => '', 'suburb' => '', 'state' => 'ACT', 'postcode' => '', 'phone' => '', 'email' => '', 'latitude' => '', 'longitude' => '', 'maps_url' => '', 'reviews_url' => '', 'intro' => '', 'photo' => null, 'is_main' => 0, 'is_active' => 0, 'sort_order' => count(branches()) + 1]
        : get_branch(query_int('edit'));
}
admin_view('branches', compact('stats', 'staff', 'editing') + ['title' => 'Branches']);
