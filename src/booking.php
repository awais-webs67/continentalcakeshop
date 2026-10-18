<?php
declare(strict_types=1);

// Pickup scheduling: works out which dates and times a customer can choose,
// based on opening hours, minimum notice, blocked dates and slot length.

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

    $slots = [];
    while ($cursor <= $last) {
        if ($cursor >= $earliest) {
            $slots[] = $cursor->format('H:i');
        }
        $cursor = $cursor->modify("+{$interval} minutes");
    }
    return $slots;
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

function format_pickup(string $date, string $time): string
{
    return (new DateTime($date . ' ' . $time))->format('l j F Y, g:ia');
}
