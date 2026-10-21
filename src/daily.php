<?php
declare(strict_types=1);

// The morning email: a short plan for the day, sent before the shop opens. It also proves
// every day that the website can send email. cron/run.php sends it; see DEPLOY.md.

/** Everything the day's email needs, for one date. */
function daily_summary_data(string $date): array
{
    $orders = db_all(
        "SELECT o.*, (SELECT GROUP_CONCAT(CONCAT(i.quantity, ' x ', i.product_name, ' (', CONCAT_WS(', ', NULLIF(i.size_label, ''), i.options), ')') SEPARATOR ', ')
                      FROM order_items i WHERE i.order_id = o.id) AS summary
         FROM orders o WHERE o.pickup_date = ? AND o.order_status <> 'cancelled'
         ORDER BY o.pickup_time IS NULL, o.pickup_time",
        [$date]
    );
    $tomorrow = date('Y-m-d', strtotime($date . ' +1 day'));
    return [
        'date'        => $date,
        'pickups'     => array_values(array_filter($orders, static fn ($o) => !is_delivery($o))),
        'deliveries'  => array_values(array_filter($orders, static fn ($o) => is_delivery($o))),
        'to_verify'   => (int) db_value("SELECT COUNT(*) FROM orders WHERE payment_status = 'awaiting_verification'"),
        'new_orders'  => (int) db_value("SELECT COUNT(*) FROM orders WHERE order_status = 'new'"),
        'to_schedule' => (int) db_value("SELECT COUNT(*) FROM orders WHERE fulfilment = 'delivery' AND pickup_time IS NULL AND order_status NOT IN ('cancelled', 'completed')"),
        'requests'    => db_all("SELECT * FROM custom_requests WHERE status IN ('new', 'quoted', 'confirmed') AND event_date BETWEEN ? AND ? ORDER BY event_date", [$date, date('Y-m-d', strtotime($date . ' +7 days'))]),
        'unread'      => unread_thread_count(),
        'tomorrow'    => (int) db_value("SELECT COUNT(*) FROM orders WHERE pickup_date = ? AND order_status <> 'cancelled'", [$tomorrow]),
        'yesterday_sales' => (float) db_value("SELECT COALESCE(SUM(amount_paid - amount_refunded), 0) FROM orders WHERE order_status <> 'cancelled' AND DATE(created_at) = ?", [date('Y-m-d', strtotime($date . ' -1 day'))]),
    ];
}

function daily_email_recipient(): string
{
    $to = setting('daily_email_to');
    return valid_email($to) ? $to : setting('order_notification_email');
}

function send_daily_summary(string $date): bool
{
    $to = daily_email_recipient();
    if (!valid_email($to)) {
        return false;
    }
    $data = daily_summary_data($date);
    $count = count($data['pickups']) + count($data['deliveries']);
    $subject = 'Today at the shop: ' . count($data['pickups']) . ' pickup' . (count($data['pickups']) === 1 ? '' : 's')
        . ', ' . count($data['deliveries']) . ' deliver' . (count($data['deliveries']) === 1 ? 'y' : 'ies')
        . ' - ' . date('D j M', strtotime($date));
    return send_mail($to, $subject, render_email('daily-summary', $data + [
        'title'     => 'Good morning! Here is ' . date('l', strtotime($date)),
        'preheader' => $count ? $count . ' orders due today' : 'No orders due today',
    ]));
}

/**
 * Called by cron every few minutes. Sends the morning email once a day, at the chosen
 * number of minutes before opening time, on days the shop is open.
 * Returns a short line describing what happened, for the cron log.
 */
function run_scheduled_tasks(?DateTimeImmutable $now = null): string
{
    $now ??= new DateTimeImmutable();
    setting_save('cron_last_run', $now->format('Y-m-d H:i:s'));
    if (setting('daily_email_enabled', '1') !== '1') {
        return 'Morning email is switched off.';
    }
    $today = $now->format('Y-m-d');
    if (setting('daily_email_last_sent') === $today) {
        return 'Morning email already sent today.';
    }
    $hours = opening_hours()[day_key($now)] ?? null;
    if (!$hours || is_blocked_date($today)) {
        return 'Shop closed today, no morning email.';
    }
    $sendAt = (new DateTimeImmutable($today . ' ' . $hours[0]))->modify('-' . max(0, (int) setting('daily_email_minutes_before', '60')) . ' minutes');
    if ($now < $sendAt) {
        return 'Morning email due at ' . $sendAt->format('g:ia') . '.';
    }
    if (send_daily_summary($today)) {
        setting_save('daily_email_last_sent', $today);
        return 'Morning email sent to ' . daily_email_recipient() . '.';
    }
    return 'Morning email could not be sent: ' . ($GLOBALS['mail_last_error'] ?? 'no recipient set');
}
