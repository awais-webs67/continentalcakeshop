<?php
$admin = require_admin();
$order = get_order(query_int('id') ?: post_int('id'));
if (!$order) {
    flash('error', 'That order was not found.');
    redirect(admin_url('orders'));
}
$id = (int) $order['id'];
$adminId = (int) $admin['id'];

const STATUS_MESSAGES = [
    'confirmed'   => 'Good news, your order is confirmed. We will have it ready at the time you chose.',
    'in_progress' => 'We have started preparing your order.',
    'ready'       => 'Your order is ready to collect.',
    'completed'   => 'Thank you for your order. We hope you enjoy it!',
    'cancelled'   => 'Your order has been cancelled. If you have any questions, please give us a call.',
];

if (is_post()) {
    $action = input('action');

    if ($action === 'status') {
        $new = input('order_status');
        if (isset(ORDER_STATUSES[$new]) && $new !== $order['order_status']) {
            db_run('UPDATE orders SET order_status = ? WHERE id = ?', [$new, $id]);
            log_order_event($id, 'status', 'Status changed to ' . order_status_label($order, $new) . '.', $adminId);
            if (input('notify') === '1') {
                $default = $new === 'ready' && is_delivery($order) ? 'Your order is on its way to you.' : (STATUS_MESSAGES[$new] ?? 'There is an update on your order.');
                $message = trim(input('message')) ?: $default;
                send_order_update_email(get_order($id), order_status_label($order, $new), $message);
                log_order_event($id, 'email', 'Customer emailed about the status change.', $adminId);
            }
            flash('success', 'Order status updated.');
        }
    }

    if ($action === 'mark_paid' && $order['payment_status'] !== 'paid') {
        db_run("UPDATE orders SET payment_status = 'paid', order_status = IF(order_status = 'new', 'confirmed', order_status) WHERE id = ?", [$id]);
        log_order_event($id, 'paid', 'Payment checked and marked as paid.', $adminId);
        send_order_update_email(get_order($id), 'Payment received', 'Thank you, we have received your payment and your order is confirmed.');
        flash('success', 'Payment marked as received. The customer has been emailed.');
    }

    if ($action === 'reject_payment' && in_array($order['payment_status'], ['pending', 'awaiting_verification'], true)) {
        db_run("UPDATE orders SET payment_status = 'failed' WHERE id = ?", [$id]);
        $reason = trim(input('reason')) ?: 'We could not find your payment. Please check the transfer or call us so we can sort it out.';
        log_order_event($id, 'payment_failed', 'Payment not received: ' . $reason, $adminId);
        send_order_update_email(get_order($id), 'We could not confirm your payment', $reason);
        flash('success', 'Payment marked as not received. The customer has been emailed.');
    }

    if ($action === 'refund' && $order['payment_status'] === 'paid') {
        try {
            if ($order['payment_method'] === PAYMENT_STRIPE && input('via_stripe') === '1') {
                $refundId = stripe_refund($order);
                log_order_event($id, 'refunded', 'Refunded in full through Stripe (' . $refundId . ').', $adminId);
            } else {
                log_order_event($id, 'refunded', 'Marked as refunded (refund sent outside the website).', $adminId);
            }
            db_run("UPDATE orders SET payment_status = 'refunded' WHERE id = ?", [$id]);
            send_order_update_email(get_order($id), 'Refund processed', 'We have refunded ' . money($order['total']) . ' for this order. Card refunds usually appear within 5 to 10 business days.');
            flash('success', 'Refund recorded and the customer has been emailed.');
        } catch (Throwable $e) {
            flash('error', 'Refund failed: ' . $e->getMessage());
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
            redirect(admin_url('orders/view', ['id' => $id]) . '#when');
        }
        if (DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time)) {
            $first = is_delivery($order) && !$order['pickup_time'];
            db_run('UPDATE orders SET pickup_date = ?, pickup_time = ?, delivery_time_to = ? WHERE id = ?', [$date, $time . ':00', $to !== '' ? $to . ':00' : null, $id]);
            $updated = get_order($id);
            $when = order_when($updated);
            log_order_event($id, 'pickup', $kind . ($first ? ' time set to ' : ' moved to ') . $when . '.', $adminId);
            if (input('notify') === '1') {
                $message = $first
                    ? 'Your delivery is booked for ' . $when . '. Please make sure someone is home to receive the cake.'
                    : 'Your ' . strtolower($kind) . ' time is now ' . $when . '.';
                send_order_update_email($updated, $first ? 'Delivery time confirmed' : $kind . ' time changed', $message);
                log_order_event($id, 'email', 'Customer emailed the ' . strtolower($kind) . ' time.', $adminId);
            }
            flash('success', $kind . ' time saved.');
        } else {
            flash('error', 'Please enter a valid date and time.');
        }
    }

    if ($action === 'notes') {
        db_run('UPDATE orders SET admin_notes = ? WHERE id = ?', [str_limit(input('admin_notes'), 5000) ?: null, $id]);
        flash('success', 'Notes saved.');
    }

    redirect(admin_url('orders/view', ['id' => $id]));
}

$items = get_order_items($id);
$events = get_order_events($id);
$customer = $order['user_id'] ? db_one('SELECT id, name FROM users WHERE id = ?', [$order['user_id']]) : null;
$proofIsImage = $order['payment_proof'] && preg_match('/\.(jpe?g|png|webp)$/i', $order['payment_proof']);

admin_view('order-view', compact('order', 'items', 'events', 'customer', 'proofIsImage') + ['title' => 'Order ' . $order['order_number']]);
