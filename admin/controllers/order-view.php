<?php
$admin = require_admin();
$order = get_order(query_int('id') ?: post_int('id'));
if (!$order) {
    flash('error', 'That order was not found.');
    redirect(admin_url('orders'));
}
$id = (int) $order['id'];
$adminId = (int) $admin['id'];
$self = admin_url('orders/view', ['id' => $id]);

const STATUS_MESSAGES = [
    'confirmed'   => 'Good news, your order is confirmed. We will have it ready at the time you chose.',
    'in_progress' => 'We have started preparing your order.',
    'ready'       => 'Your order is ready to collect.',
    'completed'   => 'Thank you for your order. We hope you enjoy it!',
    'cancelled'   => 'Your order has been cancelled. If you have any questions, please give us a call.',
];

/** "$12.50" or "12.5" typed by staff, as a number (null when empty or not a number). */
$amountInput = static function (string $key): ?float {
    $text = trim(str_replace(['$', ','], '', input($key)));
    return $text !== '' && is_numeric($text) ? round((float) $text, 2) : null;
};

if (is_post()) {
    $action = input('action');

    if ($action === 'status') {
        $new = input('order_status');
        if (isset(ORDER_STATUSES[$new]) && $new !== $order['order_status']) {
            db_run('UPDATE orders SET order_status = ?, completed_at = IF(? = \'completed\', NOW(), completed_at) WHERE id = ?', [$new, $new, $id]);
            log_order_event($id, 'status', 'Status changed to ' . order_status_label($order, $new) . '.', $adminId);
            if (input('notify') === '1' && valid_email($order['customer_email'])) {
                $default = $new === 'ready' && is_delivery($order) ? 'Your order is on its way to you.' : (STATUS_MESSAGES[$new] ?? 'There is an update on your order.');
                $message = trim(input('message')) ?: $default;
                send_order_update_email(get_order($id), order_status_label($order, $new), $message);
                log_order_event($id, 'email', 'Customer emailed about the status change.', $adminId);
            }
            flash('success', 'Order status updated.');
        }
    }

    // Money received: the whole balance, a deposit or part of it, by any method.
    if ($action === 'record_payment') {
        $amount = $amountInput('amount') ?? max(0, order_balance($order));
        $method = isset(staff_payment_methods()[input('method')]) ? input('method') : $order['payment_method'];
        if ($amount <= 0) {
            flash('error', 'Enter the amount received.');
        } elseif ($amount > order_balance($order) + 0.004 && input('allow_over') !== '1') {
            flash('error', 'That is more than the ' . money(max(0, order_balance($order))) . ' still owing. Check the amount.');
        } else {
            record_payment($order, $amount, $method, input('reference') !== '' ? str_limit(input('reference'), 190) . '-' . $id : null, str_limit(trim(input('note')), 200), $adminId);
            if (input('reference') !== '') {
                db_run('UPDATE orders SET payment_reference = ? WHERE id = ?', [str_limit(input('reference'), 190), $id]);
            }
            $updated = get_order($id);
            if (input('notify') === '1' && valid_email($updated['customer_email'])) {
                $balance = order_balance($updated);
                send_order_update_email($updated, $balance > 0 ? 'Payment received' : 'Payment received, thank you',
                    'Thank you, we have received your payment of ' . money($amount) . '.' . ($balance > 0 ? ' The balance of ' . money($balance) . ' is due before ' . (is_delivery($updated) ? 'delivery' : 'pickup') . '.' : ' Your order is fully paid.'));
            }
            flash('success', money($amount) . ' recorded. ' . (order_balance($updated) > 0 ? money(order_balance($updated)) . ' still owing.' : 'The order is fully paid.'));
        }
    }

    if ($action === 'reject_payment' && in_array($order['payment_status'], ['pending', 'awaiting_verification'], true)) {
        db_run("UPDATE orders SET payment_status = 'failed' WHERE id = ?", [$id]);
        $reason = trim(input('reason')) ?: 'We could not find your payment. Please check the transfer or call us so we can sort it out.';
        log_order_event($id, 'payment_failed', 'Payment not received: ' . $reason, $adminId);
        send_order_update_email(get_order($id), 'We could not confirm your payment', $reason);
        flash('success', 'Payment marked as not received. The customer has been emailed.');
    }

    // Full or partial refund, through Stripe or recorded after sending the money back.
    if ($action === 'refund' && order_net_paid($order) > 0) {
        $amount = $amountInput('amount') ?? order_net_paid($order);
        if ($amount <= 0 || $amount > order_net_paid($order) + 0.004) {
            flash('error', 'The refund must be between $0.01 and ' . money(order_net_paid($order)) . '.');
        } else {
            try {
                // A partial refund (one cake less, goodwill) also lowers the order total, so nothing shows as owing.
                // Money paid over the total is just returned, and a full refund leaves the total as it was.
                $overpaid = max(0, -order_balance($order));
                $lowerBy = $amount < order_net_paid($order) - 0.004 ? round(max(0, $amount - $overpaid), 2) : 0.0;
                if (input('via_stripe') === '1') {
                    $ids = stripe_refund($order, $amount, $adminId);
                    log_order_event($id, 'refunded', 'Stripe refund ' . implode(', ', array_filter($ids)) . '.', $adminId);
                } else {
                    record_refund($order, $amount, isset(staff_payment_methods()[input('method')]) ? input('method') : $order['payment_method'], null, 'Refund sent outside the website.' . (trim(input('note')) !== '' ? ' ' . str_limit(trim(input('note')), 150) : ''), $adminId);
                }
                if ($lowerBy > 0) {
                    recalculate_order_total($id, null, (float) $order['discount'] + $lowerBy);
                    log_order_event($id, 'items', 'Order total lowered by ' . money($lowerBy) . ' for the partial refund.', $adminId);
                }
                $updated = get_order($id);
                if (input('notify') === '1' && valid_email($updated['customer_email'])) {
                    send_order_update_email($updated, 'Refund processed', 'We have refunded ' . money($amount) . ' for this order.' . (input('via_stripe') === '1' ? ' Card refunds usually appear within 5 to 10 business days.' : ''));
                }
                flash('success', 'Refund of ' . money($amount) . ' recorded.');
            } catch (Throwable $e) {
                flash('error', 'Refund failed: ' . $e->getMessage());
            }
        }
    }

    // Change the cakes on the order: quantities, prices, remove lines, add cakes or custom lines.
    if ($action === 'items') {
        $current = array_column(get_order_items($id), null, 'id');
        $changes = [];
        $pdo = db();
        $pdo->beginTransaction();
        try {
            foreach ((array) ($_POST['line'] ?? []) as $lineId => $row) {
                $line = $current[(int) $lineId] ?? null;
                if (!$line) {
                    continue;
                }
                $qty = max(0, (int) ($row['qty'] ?? $line['quantity']));
                $priceText = trim(str_replace(['$', ','], '', (string) ($row['price'] ?? '')));
                $price = $priceText !== '' && is_numeric($priceText) ? max(0, round((float) $priceText, 2)) : (float) $line['unit_price'];
                if ($qty === 0 || !empty($row['remove'])) {
                    db_run('DELETE FROM order_items WHERE id = ?', [$line['id']]);
                    $changes[] = 'removed ' . $line['product_name'];
                } elseif ($qty !== (int) $line['quantity'] || abs($price - (float) $line['unit_price']) > 0.004) {
                    db_run('UPDATE order_items SET quantity = ?, unit_price = ?, line_total = ? WHERE id = ?', [$qty, $price, round($qty * $price, 2), $line['id']]);
                    $changes[] = $line['product_name'] . ' now ' . $qty . ' x ' . money($price);
                }
            }
            $new = admin_order_lines_from_post($_POST['add'] ?? []);
            insert_order_lines($id, $new);
            foreach ($new as $line) {
                $changes[] = 'added ' . $line['qty'] . ' x ' . $line['name'];
            }
            $fee = $amountInput('delivery_fee');
            $discount = $amountInput('discount');
            $before = $order;
            $order = recalculate_order_total($id, $fee, $discount);
            if (abs((float) $order['delivery_fee'] - (float) $before['delivery_fee']) > 0.004) {
                $changes[] = 'delivery fee ' . money($order['delivery_fee']);
            }
            if (abs((float) $order['discount'] - (float) $before['discount']) > 0.004) {
                $changes[] = 'discount ' . money($order['discount']);
            }
            if (!db_value('SELECT 1 FROM order_items WHERE order_id = ?', [$id])) {
                throw new RuntimeException('An order needs at least one item. Cancel the order instead.');
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            refresh_order_payment($id);
            flash('error', $e->getMessage());
            redirect($self . '#items');
        }
        if ($changes) {
            log_order_event($id, 'items', 'Order changed: ' . implode('; ', $changes) . '. New total ' . money($order['total']) . '.', $adminId);
            if (input('notify') === '1' && valid_email($order['customer_email'])) {
                send_order_update_email($order, 'Your order has been updated', 'We have updated your order as we discussed. The new total is ' . money($order['total'])
                    . (order_balance($order) > 0 && (float) $order['amount_paid'] > 0 ? ', with ' . money(order_balance($order)) . ' still to pay.' : '.'));
            }
            $balance = order_balance($order);
            flash('success', 'Order updated. New total ' . money($order['total']) . ($balance < 0 ? '. You owe the customer ' . money(-$balance) . ', use Refund below.' : ($balance > 0 && (float) $order['amount_paid'] > 0 ? '. ' . money($balance) . ' still owing.' : '.')));
        } else {
            flash('info', 'Nothing was changed.');
        }
        redirect($self . '#items');
    }

    // Customer details and delivery address (useful for phone orders).
    if ($action === 'details') {
        $name = str_limit(trim(input('customer_name')), 120);
        $email = str_limit(trim(input('customer_email')), 190);
        $phone = str_limit(trim(input('customer_phone')), 40);
        if ($name === '' || ($email !== '' && !valid_email($email))) {
            flash('error', 'Enter a name, and a valid email or leave it empty.');
        } else {
            db_run('UPDATE orders SET customer_name = ?, customer_email = ?, customer_phone = ?, delivery_address = ?, delivery_suburb = ?, delivery_postcode = ? WHERE id = ?', [
                $name, $email, $phone, str_limit(trim(input('delivery_address')), 255) ?: null, str_limit(trim(input('delivery_suburb')), 80) ?: null,
                preg_replace('/\D/', '', input('delivery_postcode')) ?: null, $id,
            ]);
            log_order_event($id, 'details', 'Customer details updated.', $adminId);
            flash('success', 'Customer details saved.');
        }
    }

    // Pickup: move the date or time. Delivery: set (or change) the delivery time the shop has planned.
    if ($action === 'pickup') {
        $date = input('pickup_date');
        $time = input('pickup_time');
        $to = is_delivery($order) ? input('delivery_time_to') : '';
        $kind = is_delivery($order) ? 'Delivery' : 'Pickup';
        if ($to !== '' && (!preg_match('/^\d{2}:\d{2}$/', $to) || $to <= $time)) {
            flash('error', 'The end of the delivery time range must be after the start.');
            redirect($self . '#when');
        }
        if (DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time)) {
            $first = !$order['pickup_time'];
            db_run('UPDATE orders SET pickup_date = ?, pickup_time = ?, delivery_time_to = ?, reminder_sent_at = IF(pickup_date = ?, reminder_sent_at, NULL) WHERE id = ?', [$date, $time . ':00', $to !== '' ? $to . ':00' : null, $date, $id]);
            $updated = get_order($id);
            $when = order_when($updated);
            log_order_event($id, 'pickup', $kind . ($first ? ' time set to ' : ' moved to ') . $when . '.', $adminId);
            if (input('notify') === '1' && valid_email($updated['customer_email'])) {
                $message = $first && is_delivery($order)
                    ? 'Your delivery is booked for ' . $when . '. Please make sure someone is home to receive the cake.'
                    : 'Your ' . strtolower($kind) . ' time is now ' . $when . '.';
                send_order_update_email($updated, $first ? $kind . ' time confirmed' : $kind . ' time changed', $message);
                log_order_event($id, 'email', 'Customer emailed the ' . strtolower($kind) . ' time.', $adminId);
            }
            flash('success', $kind . ' time saved.');
        } else {
            flash('error', 'Please enter a valid date and time.');
        }
    }

    if ($action === 'send_payment_link' && $order['payment_method'] === PAYMENT_STRIPE && order_balance($order) > 0 && valid_email($order['customer_email'])) {
        send_order_update_email($order, 'Pay for your order', 'You can pay ' . money(order_balance($order)) . ' by card using the button below. Open the order and press "Pay now".');
        log_order_event($id, 'email', 'Payment link emailed to the customer.', $adminId);
        flash('success', 'Payment link emailed to ' . $order['customer_email'] . '.');
    }

    if ($action === 'notes') {
        db_run('UPDATE orders SET admin_notes = ? WHERE id = ?', [str_limit(input('admin_notes'), 5000) ?: null, $id]);
        flash('success', 'Notes saved.');
    }

    redirect($self);
}

$items = get_order_items($id);
$events = get_order_events($id);
$payments = get_order_payments($id);
$customer = $order['user_id'] ? db_one('SELECT id, name FROM users WHERE id = ?', [$order['user_id']]) : null;
$proofIsImage = $order['payment_proof'] && preg_match('/\.(jpe?g|png|webp)$/i', $order['payment_proof']);
$hasStripePayment = (bool) db_value("SELECT 1 FROM payments WHERE order_id = ? AND kind = 'payment' AND reference LIKE 'pi\\_%'", [$id])
    || str_starts_with((string) $order['payment_reference'], 'pi_');
$catalogue = admin_size_choices();

admin_view('order-view', compact('order', 'items', 'events', 'payments', 'customer', 'proofIsImage', 'hasStripePayment', 'catalogue') + ['title' => 'Order ' . $order['order_number']]);
