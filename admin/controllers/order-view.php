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
    'completed'   => 'Thank you for collecting your order. We hope you enjoy it!',
    'cancelled'   => 'Your order has been cancelled. If you have any questions, please give us a call.',
];

if (is_post()) {
    $action = input('action');

    if ($action === 'status') {
        $new = input('order_status');
        if (isset(ORDER_STATUSES[$new]) && $new !== $order['order_status']) {
            db_run('UPDATE orders SET order_status = ? WHERE id = ?', [$new, $id]);
            log_order_event($id, 'status', 'Status changed to ' . ORDER_STATUSES[$new] . '.', $adminId);
            if (input('notify') === '1') {
                $message = trim(input('message')) ?: (STATUS_MESSAGES[$new] ?? 'There is an update on your order.');
                send_order_update_email(get_order($id), ORDER_STATUSES[$new], $message);
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

    if ($action === 'pickup') {
        $date = input('pickup_date');
        $time = input('pickup_time');
        if (DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time)) {
            db_run('UPDATE orders SET pickup_date = ?, pickup_time = ? WHERE id = ?', [$date, $time . ':00', $id]);
            log_order_event($id, 'pickup', 'Pickup moved to ' . format_pickup($date, $time) . '.', $adminId);
            if (input('notify') === '1') {
                send_order_update_email(get_order($id), 'Pickup time changed', 'Your pickup time is now ' . format_pickup($date, $time) . '.');
            }
            flash('success', 'Pickup time updated.');
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
