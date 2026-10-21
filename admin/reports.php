<?php
declare(strict_types=1);

// Sales figures for Admin > Reports and the CSV exports. Cancelled orders are left out of
// sales; refunded orders are shown separately. Dates are the day the order was placed.

/** [from, to] from the query string, defaulting to this month. */
function report_range(): array
{
    $valid = static fn (string $d): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
    $preset = (string) ($_GET['range'] ?? '');
    $today = new DateTimeImmutable('today');
    [$from, $to] = match ($preset) {
        'today'      => [$today, $today],
        'yesterday'  => [$today->modify('-1 day'), $today->modify('-1 day')],
        'week'       => [$today->modify('monday this week'), $today],
        'last-month' => [$today->modify('first day of last month'), $today->modify('last day of last month')],
        'year'       => [$today->modify('first day of january this year'), $today],
        default      => [$today->modify('first day of this month'), $today],
    };
    $from = $valid((string) ($_GET['from'] ?? '')) ? (string) $_GET['from'] : $from->format('Y-m-d');
    $to = $valid((string) ($_GET['to'] ?? '')) ? (string) $_GET['to'] : $to->format('Y-m-d');
    return $from <= $to ? [$from, $to] : [$to, $from];
}

function report_summary(string $from, string $to): array
{
    $range = [$from . ' 00:00:00', $to . ' 23:59:59'];
    $row = db_one(
        "SELECT COUNT(*) AS orders,
                COALESCE(SUM(subtotal), 0) AS gross, COALESCE(SUM(discount), 0) AS discounts,
                COALESCE(SUM(delivery_fee), 0) AS delivery, COALESCE(SUM(total), 0) AS total,
                COALESCE(SUM(amount_paid - amount_refunded), 0) AS paid,
                COALESCE(SUM(CASE WHEN payment_status <> 'refunded' AND total > amount_paid - amount_refunded THEN total - (amount_paid - amount_refunded) END), 0) AS unpaid,
                COALESCE(SUM(CASE WHEN payment_status = 'refunded' THEN total END), 0) AS refunded,
                SUM(fulfilment = 'delivery') AS deliveries, SUM(coupon_code IS NOT NULL) AS coupon_orders
         FROM orders WHERE order_status <> 'cancelled' AND created_at BETWEEN ? AND ?",
        $range
    );
    $row['cancelled'] = (int) db_value("SELECT COUNT(*) FROM orders WHERE order_status = 'cancelled' AND created_at BETWEEN ? AND ?", $range);
    $row['net'] = (float) $row['total'] - (float) $row['refunded'];
    $row['average'] = (int) $row['orders'] > 0 ? (float) $row['total'] / (int) $row['orders'] : 0.0;
    $row['gst'] = setting_bool('gst_registered') ? round($row['net'] / 11, 2) : null;
    return $row;
}

function report_by_day(string $from, string $to): array
{
    $rows = array_column(db_all(
        "SELECT DATE(created_at) AS day, COUNT(*) AS orders, SUM(total) AS total,
                SUM(amount_paid - amount_refunded) AS paid
         FROM orders WHERE order_status <> 'cancelled' AND created_at BETWEEN ? AND ? GROUP BY DATE(created_at)",
        [$from . ' 00:00:00', $to . ' 23:59:59']
    ), null, 'day');
    // Every day in the range, including days without orders, for the chart.
    $days = [];
    for ($d = new DateTimeImmutable($from); $d->format('Y-m-d') <= $to && count($days) < 400; $d = $d->modify('+1 day')) {
        $key = $d->format('Y-m-d');
        $days[$key] = ['day' => $key, 'orders' => (int) ($rows[$key]['orders'] ?? 0), 'total' => (float) ($rows[$key]['total'] ?? 0), 'paid' => (float) ($rows[$key]['paid'] ?? 0)];
    }
    return array_values($days);
}

function report_by_method(string $from, string $to): array
{
    return db_all(
        "SELECT payment_method, COUNT(*) AS orders, SUM(total) AS total,
                SUM(amount_paid - amount_refunded) AS paid
         FROM orders WHERE order_status <> 'cancelled' AND created_at BETWEEN ? AND ?
         GROUP BY payment_method ORDER BY total DESC",
        [$from . ' 00:00:00', $to . ' 23:59:59']
    );
}

function report_top_products(string $from, string $to, int $limit = 10): array
{
    return db_all(
        "SELECT i.product_name, SUM(i.quantity) AS qty, SUM(i.line_total) AS revenue,
                COALESCE(c.name, IF(i.addon_id IS NOT NULL, 'Extras', 'Removed products')) AS category
         FROM order_items i JOIN orders o ON o.id = i.order_id
         LEFT JOIN products p ON p.id = i.product_id LEFT JOIN categories c ON c.id = p.category_id
         WHERE o.order_status <> 'cancelled' AND o.created_at BETWEEN ? AND ?
         GROUP BY i.product_name, category ORDER BY revenue DESC LIMIT " . max(1, $limit),
        [$from . ' 00:00:00', $to . ' 23:59:59']
    );
}

function report_by_category(string $from, string $to): array
{
    return db_all(
        "SELECT COALESCE(c.name, IF(i.addon_id IS NOT NULL, 'Extras', 'Removed products')) AS category, SUM(i.quantity) AS qty, SUM(i.line_total) AS revenue
         FROM order_items i JOIN orders o ON o.id = i.order_id
         LEFT JOIN products p ON p.id = i.product_id LEFT JOIN categories c ON c.id = p.category_id
         WHERE o.order_status <> 'cancelled' AND o.created_at BETWEEN ? AND ?
         GROUP BY category ORDER BY revenue DESC",
        [$from . ' 00:00:00', $to . ' 23:59:59']
    );
}

/** Sends a CSV download that opens correctly in Excel (UTF-8 with BOM). */
function send_csv(string $filename, array $header, iterable $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header);
    foreach ($rows as $row) {
        // Stop spreadsheet formulas sneaking in through customer text.
        fputcsv($out, array_map(static fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $row));
    }
    fclose($out);
    exit;
}
