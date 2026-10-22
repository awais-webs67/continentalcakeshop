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
 * Called by cron every few minutes. Runs each job on its own, so one failing job never
 * stops the others. Returns a short line per job for the cron log.
 */
function run_scheduled_tasks(?DateTimeImmutable $now = null): string
{
    $now ??= new DateTimeImmutable();
    setting_save('cron_last_run', $now->format('Y-m-d H:i:s'));
    $report = [];
    foreach (['morning_email_task', 'reminder_email_task', 'review_request_task', 'backup_task', 'cleanup_task'] as $job) {
        if (!function_exists($job)) {
            continue;
        }
        try {
            $report[] = $job($now);
        } catch (Throwable $e) {
            error_log('Scheduled job ' . $job . ' failed: ' . $e->getMessage());
            $report[] = $job . ' failed: ' . $e->getMessage();
        }
    }
    return implode(' | ', array_filter($report));
}

/** The morning email, once a day, the chosen number of minutes before opening, on open days. */
function morning_email_task(DateTimeImmutable $now): string
{
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

// ---------------------------------------------------------------------------
// Reminder the day before pickup or delivery
// ---------------------------------------------------------------------------

/** Orders that get tomorrow's reminder (not cancelled or finished, with an email, not reminded yet). */
function orders_needing_reminder(DateTimeImmutable $now): array
{
    return db_all(
        "SELECT * FROM orders
         WHERE pickup_date = ? AND reminder_sent_at IS NULL AND customer_email <> ''
           AND order_status NOT IN ('cancelled', 'completed') AND created_at < ?
         ORDER BY id LIMIT 50",
        [$now->modify('+1 day')->format('Y-m-d'), $now->modify('-3 hours')->format('Y-m-d H:i:s')]
    );
}

function send_order_reminder(array $order): bool
{
    $what = is_delivery($order) ? 'delivery' : 'pickup';
    return send_mail(
        $order['customer_email'],
        'Reminder: your cake ' . $what . ' is tomorrow - ' . setting('store_short_name'),
        render_email('order-reminder', [
            'order' => $order, 'items' => get_order_items((int) $order['id']),
            'title' => 'See you tomorrow!', 'preheader' => ucfirst($what) . ' ' . order_when($order),
            'reply_url' => thread_url(order_thread($order)),
        ]),
        null,
        setting('store_email')
    );
}

/** Sends tomorrow's reminders once the chosen hour has passed. */
function reminder_email_task(DateTimeImmutable $now): string
{
    if (setting('reminder_email_enabled', '1') !== '1') {
        return 'Reminders are switched off.';
    }
    if ((int) $now->format('G') < min(23, max(0, (int) setting('reminder_send_hour', '10')))) {
        return 'Reminders go out at ' . format_time(sprintf('%02d:00', (int) setting('reminder_send_hour', '10'))) . '.';
    }
    $sent = 0;
    foreach (orders_needing_reminder($now) as $order) {
        // Marked first, so a mail server problem can never send the same reminder over and over.
        db_run('UPDATE orders SET reminder_sent_at = NOW() WHERE id = ? AND reminder_sent_at IS NULL', [$order['id']]);
        if (valid_email($order['customer_email']) && send_order_reminder($order)) {
            log_order_event((int) $order['id'], 'email', 'Reminder emailed the day before ' . (is_delivery($order) ? 'delivery' : 'pickup') . '.');
            $sent++;
        }
    }
    return $sent ? "Sent {$sent} reminder" . ($sent === 1 ? '' : 's') . '.' : 'No reminders to send.';
}

// ---------------------------------------------------------------------------
// Google review request after an order is completed
// ---------------------------------------------------------------------------

function review_requests_enabled(): bool
{
    return setting('review_email_enabled', '1') === '1' && filter_var(setting('google_reviews_url'), FILTER_VALIDATE_URL) !== false;
}

/** Asks happy customers for a Google review, once, a while after their order was completed. */
function review_request_task(DateTimeImmutable $now): string
{
    if (!review_requests_enabled()) {
        return 'Review requests are off (or no Google reviews link in Settings > Homepage).';
    }
    $delay = min(240, max(1, (int) setting('review_delay_hours', '20')));
    $orders = db_all(
        "SELECT * FROM orders
         WHERE order_status = 'completed' AND review_requested_at IS NULL AND customer_email <> ''
           AND completed_at <= ? AND completed_at >= ? AND payment_status <> 'refunded'
         ORDER BY id LIMIT 30",
        [$now->modify("-{$delay} hours")->format('Y-m-d H:i:s'), $now->modify('-14 days')->format('Y-m-d H:i:s')]
    );
    $sent = 0;
    foreach ($orders as $order) {
        db_run('UPDATE orders SET review_requested_at = NOW() WHERE id = ? AND review_requested_at IS NULL', [$order['id']]);
        // Regular customers are asked at most once every 120 days.
        $askedLately = db_value(
            'SELECT 1 FROM orders WHERE customer_email = ? AND id <> ? AND review_requested_at >= ?',
            [$order['customer_email'], $order['id'], $now->modify('-120 days')->format('Y-m-d H:i:s')]
        );
        if ($askedLately || !valid_email($order['customer_email'])) {
            continue;
        }
        $ok = send_mail(
            $order['customer_email'],
            'How was your cake? - ' . setting('store_short_name'),
            render_email('review-request', ['order' => $order, 'title' => 'Thank you for choosing us', 'preheader' => 'Would you share a quick Google review?']),
            null,
            setting('store_email')
        );
        if ($ok) {
            log_order_event((int) $order['id'], 'email', 'Google review request emailed.');
            $sent++;
        }
    }
    return $sent ? "Sent {$sent} review request" . ($sent === 1 ? '' : 's') . '.' : 'No review requests to send.';
}

/** Small tidy-ups: old "not found" entries and expired password links. */
function cleanup_task(DateTimeImmutable $now): string
{
    if (setting('cleanup_last_run') === $now->format('Y-m-d')) {
        return '';
    }
    setting_save('cleanup_last_run', $now->format('Y-m-d'));
    $removed = db_run('DELETE FROM not_found_log WHERE last_seen < ?', [$now->modify('-90 days')->format('Y-m-d H:i:s')])->rowCount();
    db_run('DELETE FROM password_resets WHERE expires_at < ?', [$now->modify('-7 days')->format('Y-m-d H:i:s')]);
    db_run('DELETE FROM admin_password_resets WHERE expires_at < ?', [$now->modify('-7 days')->format('Y-m-d H:i:s')]);
    // The missing-pages list keeps the 5,000 most visited addresses, so bots cannot grow it forever.
    $keepFrom = db_value('SELECT hits FROM not_found_log ORDER BY hits DESC, last_seen DESC LIMIT 1 OFFSET 4999');
    if ($keepFrom !== null) {
        $removed += db_run('DELETE FROM not_found_log WHERE hits < ?', [(int) $keepFrom])->rowCount();
    }
    try {
        db_run('DELETE FROM rate_limits WHERE window_start < ?', [$now->modify('-2 days')->format('Y-m-d H:i:s')]);
    } catch (PDOException) {
    }
    $files = remove_unused_uploads($now);
    return 'Tidied up' . ($removed ? ", {$removed} old missing page entries" : '') . ($files ? ", {$files} unused uploads" : '') . '.';
}

/**
 * Payment screenshots and cake photos are saved as soon as they are chosen, before the form is
 * sent. Files that never became part of an order or a request are deleted after two days.
 */
function remove_unused_uploads(DateTimeImmutable $now, int $minAgeSeconds = 2 * 86400): int
{
    $used = array_flip(array_merge(
        array_column(db_all("SELECT payment_proof AS p FROM orders WHERE payment_proof IS NOT NULL AND payment_proof <> ''"), 'p'),
        array_column(db_all("SELECT reference_image AS p FROM custom_requests WHERE reference_image IS NOT NULL AND reference_image <> ''"), 'p')
    ));
    $cutoff = $now->getTimestamp() - $minAgeSeconds;
    $deleted = 0;
    foreach (array_keys(ASYNC_UPLOADS) as $purpose) {
        $base = STORAGE_PATH . '/uploads/' . ASYNC_UPLOADS[$purpose]['folder'];
        if (!is_dir($base)) {
            continue;
        }
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (!$file->isFile() || $file->getFilename() === '.htaccess' || $file->getMTime() > $cutoff) {
                continue;
            }
            $relative = ASYNC_UPLOADS[$purpose]['folder'] . substr($file->getPathname(), strlen($base));
            if (!isset($used[$relative]) && @unlink($file->getPathname())) {
                $deleted++;
            }
        }
    }
    return $deleted;
}
