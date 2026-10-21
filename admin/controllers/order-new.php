<?php
// A new order typed in by staff: phone orders and orders taken in the shop.
// Staff can book any date and time (the website's slot rules do not apply here).
$admin = require_admin();
$catalogue = admin_size_choices();
$errors = [];

if (is_post()) {
    $data = [
        'channel'           => input('channel') === 'in_store' ? 'in_store' : 'phone',
        'name'              => str_limit(trim(input('name')), 120),
        'email'             => strtolower(str_limit(trim(input('email')), 190)),
        'phone'             => str_limit(trim(input('phone')), 40),
        'fulfilment'        => input('fulfilment') === 'delivery' ? 'delivery' : 'pickup',
        'delivery_address'  => str_limit(trim(input('delivery_address')), 255),
        'delivery_suburb'   => str_limit(trim(input('delivery_suburb')), 80),
        'delivery_postcode' => preg_replace('/\D/', '', input('delivery_postcode')),
        'delivery_window'   => '',
        'pickup_date'       => input('pickup_date'),
        'pickup_time'       => preg_match('/^\d{2}:\d{2}$/', input('pickup_time')) ? input('pickup_time') : '',
        'payment_method'    => isset(staff_payment_methods()[input('payment_method')]) ? input('payment_method') : 'cash',
        'payment_reference' => '',
        'payment_proof'     => null,
        'notes'             => str_limit(trim(input('notes')), 1000),
        'admin_id'          => (int) $admin['id'],
    ];
    $lines = admin_order_lines_from_post($_POST['add'] ?? []);
    $timeTo = preg_match('/^\d{2}:\d{2}$/', input('delivery_time_to')) ? input('delivery_time_to') : '';

    if ($data['name'] === '') {
        $errors['name'] = 'Enter the customer name.';
    }
    if ($data['phone'] === '' && $data['email'] === '') {
        $errors['phone'] = 'Enter a phone number or an email so you can reach the customer.';
    }
    if ($data['email'] !== '' && !valid_email($data['email'])) {
        $errors['email'] = 'That email does not look right. Leave it empty if the customer has none.';
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['pickup_date']);
    if (!$date || $date->format('Y-m-d') !== $data['pickup_date']) {
        $errors['pickup_date'] = 'Choose the ' . ($data['fulfilment'] === 'delivery' ? 'delivery' : 'pickup') . ' date.';
    }
    if ($data['fulfilment'] === 'pickup' && $data['pickup_time'] === '') {
        $errors['pickup_time'] = 'Choose the pickup time.';
    }
    if ($data['fulfilment'] === 'delivery' && $data['delivery_address'] === '') {
        $errors['delivery_address'] = 'Enter the delivery address.';
    }
    if ($timeTo !== '' && ($data['pickup_time'] === '' || $timeTo <= $data['pickup_time'])) {
        $errors['pickup_time'] = 'The end of the delivery time must be after the start.';
    }
    if (!$lines) {
        $errors['items'] = 'Add at least one cake or item. For "Something else", type a name and a price.';
    }

    $subtotal = cart_subtotal($lines);
    $fee = $data['fulfilment'] === 'delivery'
        ? round(max(0, (float) str_replace(['$', ','], '', input('delivery_fee', (string) delivery_fee_for($subtotal)))), 2)
        : 0.0;
    $discount = round(min($subtotal, max(0, (float) str_replace(['$', ','], '', input('discount', '0')))), 2);
    $totals = ['subtotal' => $subtotal, 'discount' => $discount, 'delivery_fee' => $fee, 'total' => round($subtotal - $discount + $fee, 2)];
    $paidNow = round(max(0, (float) str_replace(['$', ','], '', input('paid_now', '0'))), 2);
    if ($paidNow > $totals['total'] + 0.004) {
        $errors['paid_now'] = 'The amount paid is more than the order total of ' . money($totals['total']) . '.';
    }

    if (!$errors) {
        $data['user_id'] = $data['email'] !== '' ? (find_user_by_email($data['email'])['id'] ?? null) : null;
        $order = create_order($data, $lines, null, $totals);
        if ($timeTo !== '') {
            db_run('UPDATE orders SET delivery_time_to = ? WHERE id = ?', [$timeTo . ':00', $order['id']]);
        }
        if (input('confirmed') === '1') {
            db_run("UPDATE orders SET order_status = 'confirmed' WHERE id = ? AND order_status = 'new'", [$order['id']]);
        }
        if ($paidNow > 0) {
            record_payment($order, $paidNow, $data['payment_method'], null, str_limit(trim(input('payment_note')), 200), (int) $admin['id']);
        }
        $order = get_order((int) $order['id']);
        if (input('admin_notes') !== '') {
            db_run('UPDATE orders SET admin_notes = ? WHERE id = ?', [str_limit(trim(input('admin_notes')), 5000), $order['id']]);
        }
        $emailed = input('notify') === '1' && valid_email($order['customer_email']) && send_order_confirmation($order);
        if ($emailed) {
            log_order_event((int) $order['id'], 'email', 'Order confirmation emailed to the customer.', (int) $admin['id']);
        }
        forget_input();
        flash('success', 'Order ' . $order['order_number'] . ' saved' . ($emailed ? ' and emailed to the customer' : '') . '.'
            . (order_balance($order) > 0 ? ' ' . money(order_balance($order)) . ' still to pay.' : ' Fully paid.'));
        redirect(admin_url('orders/view', ['id' => $order['id']]));
    }
    remember_input(array_map(static fn ($v) => is_string($v) ? $v : '', array_diff_key($_POST, ['_token' => 1, 'add' => 1])));
}

$defaultFee = (float) setting('delivery_fee', '0');
$values = is_post() ? (array) ($_POST['add'] ?? []) : [];
admin_view('order-new', compact('catalogue', 'errors', 'defaultFee', 'values') + ['title' => 'New order']);
