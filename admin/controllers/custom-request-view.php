<?php
$admin = require_admin();

$id = query_int('id') ?: post_int('id');
$request = $id ? db_one('SELECT * FROM custom_requests WHERE id = ?', [$id]) : null;
if (!$request) {
    flash('error', 'That request was not found.');
    redirect(admin_url('custom-requests'));
}
require_branch_access($request, 'custom-requests');
$self = admin_url('custom-requests/view', ['id' => $request['id']]);

if (is_post()) {
    $action = input('action');
    if ($action === 'quote') {
        $price = (float) str_replace(['$', ','], '', input('quoted_price'));
        $message = trim(input('message'));
        if ($message === '') {
            flash('error', 'Please write a short message for the customer.');
            redirect($self);
        }
        $depositText = trim(str_replace(['$', ','], '', input('deposit_amount')));
        $deposit = $depositText !== '' && is_numeric($depositText) && (float) $depositText > 0 && (float) $depositText < $price ? round((float) $depositText, 2) : null;
        $online = input('online_pay') === '1' && $price > 0 && !$request['order_id'];
        db_run("UPDATE custom_requests SET quoted_price = ?, deposit_amount = ?, quote_token = IF(? = 1, COALESCE(quote_token, ?), quote_token), quote_sent_at = NOW(),
                       status = IF(status = 'new', 'quoted', status) WHERE id = ?",
            [$price > 0 ? round($price, 2) : null, $deposit, $online ? 1 : 0, bin2hex(random_bytes(16)), $request['id']]);
        if (!$online && !$request['order_id']) {
            db_run('UPDATE custom_requests SET quote_token = NULL WHERE id = ?', [$request['id']]);
        }
        $request = db_one('SELECT * FROM custom_requests WHERE id = ?', [$request['id']]);
        // Keep the quote in the conversation, so the customer's answer lands next to it.
        add_thread_message(custom_request_thread($request), 'shop', ($request['quoted_price'] ? 'Quote: ' . money($request['quoted_price']) . "\n\n" : '') . $message, (int) $admin['id']);
        if (send_custom_quote_email($request, $message)) {
            flash('success', 'Your reply was emailed to ' . $request['email'] . '.' . ($online ? ' It has an "Accept and pay" button.' : ''));
        } else {
            flash('error', 'The quote was saved but the email could not be sent. Check the mail settings in config.php.');
        }
    } elseif ($action === 'make_order') {
        // The customer agreed the price with the shop: make it a confirmed order.
        if ($request['order_id'] && get_order((int) $request['order_id'])) {
            redirect(admin_url('orders/view', ['id' => $request['order_id']]));
        }
        $price = (float) str_replace(['$', ','], '', input('price'));
        $deposit = (float) str_replace(['$', ','], '', input('deposit'));
        $delivery = input('fulfilment') === 'delivery';
        $methods = payment_method_definitions();
        $errors = [];
        if ($price <= 0) {
            $errors[] = 'Enter the price of the cake.';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', input('date'))) {
            $errors[] = 'Choose the date.';
        }
        if (input('time') !== '' && !preg_match('/^\d{2}:\d{2}$/', input('time'))) {
            $errors[] = 'Check the time.';
        }
        if ($delivery && (trim(input('delivery_address')) === '' || trim(input('delivery_suburb')) === '' || !preg_match('/^\d{4}$/', preg_replace('/\D/', '', input('delivery_postcode'))))) {
            $errors[] = 'Enter the delivery address, suburb and 4 digit postcode.';
        }
        if (!isset($methods[input('payment_method')])) {
            $errors[] = 'Choose how the customer pays the rest.';
        }
        if ($deposit < 0 || ($price > 0 && $deposit > $price + 0.004)) {
            $errors[] = 'The amount paid now cannot be more than the price.';
        }
        if ($deposit > 0 && !isset(staff_payment_methods()[input('deposit_method')])) {
            $errors[] = 'Choose how the deposit was paid.';
        }
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect($self . '#make-order');
        }
        $order = confirm_request_as_order($request, [
            'price' => $price, 'date' => input('date'), 'time' => input('time'), 'fulfilment' => $delivery ? 'delivery' : 'pickup',
            'delivery_address' => str_limit(trim(input('delivery_address')), 255), 'delivery_suburb' => str_limit(trim(input('delivery_suburb')), 80),
            'delivery_postcode' => preg_replace('/\D/', '', input('delivery_postcode')),
            'payment_method' => input('payment_method'), 'deposit' => $deposit, 'deposit_method' => input('deposit_method'),
            'notify' => input('notify') === '1',
        ], (int) $admin['id']);
        flash('success', 'Order ' . $order['order_number'] . ' is confirmed and in Orders.' . ($deposit > 0 ? ' ' . money($deposit) . ' recorded.' : ''));
        redirect(admin_url('orders/view', ['id' => $order['id']]));
    } elseif ($action === 'status' && isset(CUSTOM_STATUSES[input('status')])) {
        // "Confirmed" without an order yet: make the order, so it is in Orders and the sales figures.
        if (input('status') === 'confirmed' && !($request['order_id'] && get_order((int) $request['order_id']))) {
            if ((float) $request['quoted_price'] <= 0) {
                flash('error', 'Add the price in "Confirm as an order" below, so the cake goes into your orders.');
                redirect($self . '#make-order');
            }
            $order = confirm_request_as_order($request, [
                'price' => (float) $request['quoted_price'], 'date' => $request['event_date'], 'time' => '',
                'fulfilment' => $request['fulfilment'] ?? 'pickup', 'payment_method' => ($request['fulfilment'] ?? 'pickup') === 'delivery' ? PAYMENT_CALL : PAYMENT_SHOP,
                'deposit' => 0, 'deposit_method' => '', 'notify' => false,
            ], (int) $admin['id']);
            flash('success', 'Confirmed. Order ' . $order['order_number'] . ' is now in Orders. Set the time and record any payment there.');
            redirect(admin_url('orders/view', ['id' => $order['id']]));
        }
        db_run('UPDATE custom_requests SET status = ? WHERE id = ?', [input('status'), $request['id']]);
        flash('success', 'Status changed to ' . CUSTOM_STATUSES[input('status')] . '.');
    } elseif ($action === 'notes') {
        db_run('UPDATE custom_requests SET admin_notes = ? WHERE id = ?', [str_limit(trim(input('admin_notes')), 5000) ?: null, $request['id']]);
        flash('success', 'Notes saved.');
    } elseif ($action === 'delete') {
        db_run('DELETE FROM custom_requests WHERE id = ?', [$request['id']]);
        if ($request['reference_image'] && !str_contains($request['reference_image'], '..')) {
            @unlink(STORAGE_PATH . '/uploads/' . $request['reference_image']);
        }
        flash('success', 'The request was deleted.');
        redirect(admin_url('custom-requests'));
    }
    redirect($self);
}

$linkedOrder = $request['order_id'] ? get_order((int) $request['order_id']) : null;
$isImage = $request['reference_image'] && preg_match('/\.(jpe?g|png|webp|gif)$/i', $request['reference_image']);
$defaultMessage = "Thank you for your custom cake enquiry. We would love to make this cake for your " . strtolower($request['occasion']) . ".\n\n"
    . "Based on the details you sent, the price is shown below. To confirm, press \"Accept and pay\" in this email, or reply or call us if you would like to change anything.";
admin_view('custom-request-view', compact('request', 'isImage', 'defaultMessage', 'linkedOrder') + ['title' => 'Request from ' . $request['name']]);
