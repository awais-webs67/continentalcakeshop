<?php
declare(strict_types=1);

// Pickup and delivery scheduling: works out which dates and times a customer can choose,
// based on opening hours, minimum notice, blocked dates, slot length and daily limits.

function earliest_pickup(int $leadHours): DateTimeImmutable
{
    return (new DateTimeImmutable())->modify('+' . $leadHours . ' hours');
}

function latest_pickup_date(): DateTimeImmutable
{
    return (new DateTimeImmutable('today'))->modify('+' . (int) setting('max_advance_days', '60') . ' days');
}

function is_blocked_date(string $date): bool
{
    return (bool) db_value('SELECT 1 FROM blocked_dates WHERE blocked_date = ?', [$date]);
}

/**
 * Pickup times available on a date, as "H:i" strings.
 * Returns an empty array when the shop is closed or the date is outside the booking window.
 */
function pickup_slots(string $date, int $leadHours): array
{
    $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$day || $day->format('Y-m-d') !== $date) {
        return [];
    }
    $earliest = earliest_pickup($leadHours);
    if ($day > latest_pickup_date() || $day->format('Y-m-d') < $earliest->format('Y-m-d') || is_blocked_date($date)) {
        return [];
    }
    $hours = opening_hours()[day_key($day)] ?? null;
    if (!$hours) {
        return [];
    }

    $interval = max(10, (int) setting('slot_interval_minutes', '30'));
    $buffer = max(0, (int) setting('last_slot_buffer_minutes', '30'));
    $cursor = new DateTimeImmutable($date . ' ' . $hours[0]);
    $last = (new DateTimeImmutable($date . ' ' . $hours[1]))->modify("-{$buffer} minutes");

    // Optional limit on pickups per time slot, so the kitchen is not overbooked.
    $limit = (int) setting('max_orders_per_slot', '0');
    $booked = $limit > 0 ? array_column(db_all(
        "SELECT TIME_FORMAT(pickup_time, '%H:%i') AS slot, COUNT(*) AS n FROM orders
         WHERE pickup_date = ? AND fulfilment = 'pickup' AND order_status <> 'cancelled' GROUP BY pickup_time",
        [$date]
    ), 'n', 'slot') : [];

    $slots = [];
    while ($cursor <= $last) {
        $slot = $cursor->format('H:i');
        if ($cursor >= $earliest && (!$limit || (int) ($booked[$slot] ?? 0) < $limit)) {
            $slots[] = $slot;
        }
        $cursor = $cursor->modify("+{$interval} minutes");
    }
    return $slots;
}

/**
 * Checks a delivery date. Returns null when the date can be booked, otherwise the reason.
 * Deliveries run on the days the shop is open; the shop confirms the exact time later.
 */
function delivery_date_error(string $date, int $leadHours): ?string
{
    $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$day || $day->format('Y-m-d') !== $date) {
        return 'Please choose a delivery date.';
    }
    $earliest = earliest_pickup($leadHours);
    $hours = opening_hours()[day_key($day)] ?? null;
    if ($day->format('Y-m-d') < date('Y-m-d')) {
        return 'That date has passed. Please choose today or a later day.';
    }
    if ($day->format('Y-m-d') < $earliest->format('Y-m-d')) {
        return 'That date is too soon. Please allow at least ' . notice_label($leadHours) . ' notice.';
    }
    if ($day > latest_pickup_date()) {
        return 'Please choose a date within the next ' . (int) setting('max_advance_days', '60') . ' days.';
    }
    if (!$hours || is_blocked_date($date)) {
        return 'We do not deliver on ' . $day->format('l j F') . '. Please choose another day.';
    }
    // On the first possible day the notice period must end before closing time.
    if ($day->format('Y-m-d') === $earliest->format('Y-m-d') && $earliest > new DateTimeImmutable($date . ' ' . $hours[1])) {
        return 'That date is too soon. Please choose a later day.';
    }
    $limit = (int) setting('max_deliveries_per_day', '0');
    if ($limit > 0 && (int) db_value("SELECT COUNT(*) FROM orders WHERE pickup_date = ? AND fulfilment = 'delivery' AND order_status <> 'cancelled'", [$date]) >= $limit) {
        return 'Deliveries are fully booked on ' . $day->format('l j F') . '. Please choose another day or pick up from the shop.';
    }
    return null;
}

function first_delivery_date(int $leadHours): ?string
{
    $day = new DateTimeImmutable(earliest_pickup($leadHours)->format('Y-m-d'));
    $limit = latest_pickup_date();
    while ($day <= $limit) {
        if (delivery_date_error($day->format('Y-m-d'), $leadHours) === null) {
            return $day->format('Y-m-d');
        }
        $day = $day->modify('+1 day');
    }
    return null;
}

/** True when the postcode is inside the delivery area set in Settings (e.g. "2600-2620, 2900-2914"). */
function delivery_postcode_allowed(string $postcode): bool
{
    if (!preg_match('/^\d{4}$/', $postcode)) {
        return false;
    }
    $areas = trim(setting('delivery_postcodes'));
    if ($areas === '') {
        return true;
    }
    foreach (preg_split('/\s*,\s*/', $areas) as $range) {
        [$from, $to] = array_pad(array_map('trim', explode('-', $range, 2)), 2, null);
        $to ??= $from;
        if ($from !== '' && (int) $postcode >= (int) $from && (int) $postcode <= (int) $to) {
            return true;
        }
    }
    return false;
}

/** "2 days" or "6 hours", for notice help text. */
function notice_label(int $leadHours): string
{
    if ($leadHours >= 24) {
        $days = (int) ceil($leadHours / 24);
        return $days . ' day' . ($days === 1 ? '' : 's');
    }
    return $leadHours === 1 ? '1 hour' : $leadHours . ' hours';
}

/** First date that still has at least one pickup slot, used to pre-fill the date picker. */
function first_available_date(int $leadHours): ?string
{
    $day = new DateTimeImmutable(earliest_pickup($leadHours)->format('Y-m-d'));
    $limit = latest_pickup_date();
    while ($day <= $limit) {
        if (pickup_slots($day->format('Y-m-d'), $leadHours)) {
            return $day->format('Y-m-d');
        }
        $day = $day->modify('+1 day');
    }
    return null;
}

function closed_week_days(): array
{
    return array_keys(array_filter(opening_hours(), static fn ($slot) => $slot === null));
}

function format_pickup(string $date, ?string $time): string
{
    if ($time === null || $time === '') {
        return (new DateTime($date))->format('l j F Y');
    }
    return (new DateTime($date . ' ' . $time))->format('l j F Y, g:ia');
}
