<?php
declare(strict_types=1);

const WEEK_DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

const DAY_NAMES = [
    'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday',
    'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday',
];

function settings_all(bool $refresh = false): array
{
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = [];
        foreach (db_all('SELECT setting_key, setting_value FROM settings') as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $value = settings_all()[$key] ?? null;
    return ($value === null || $value === '') ? $default : (string) $value;
}

function setting_bool(string $key): bool
{
    return setting($key, '0') === '1';
}

function setting_save(string $key, ?string $value): void
{
    db_run(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
        [$key, $value]
    );
    settings_all(true);
}

/** @return array<string, array{0:string,1:string}|null> keyed mon..sun, null when closed */
function opening_hours(): array
{
    $decoded = json_decode(setting('opening_hours', '{}'), true) ?: [];
    $hours = [];
    foreach (WEEK_DAYS as $day) {
        $slot = $decoded[$day] ?? null;
        $hours[$day] = (is_array($slot) && count($slot) === 2) ? [$slot[0], $slot[1]] : null;
    }
    return $hours;
}

function day_key(DateTimeInterface $date): string
{
    return strtolower($date->format('D'));
}

function format_time(string $time): string
{
    return strtolower((new DateTime($time))->format('g:ia'));
}

/** Rows like "Monday to Friday" => "8:00am to 7:00pm", grouping days with the same hours. */
function opening_hours_grouped(bool $short = false): array
{
    $names = $short ? array_map(static fn ($n) => substr($n, 0, 3), DAY_NAMES) : DAY_NAMES;
    $groups = [];
    foreach (opening_hours() as $day => $slot) {
        $label = $slot ? format_time($slot[0]) . ' to ' . format_time($slot[1]) : 'Closed';
        $last = array_key_last($groups);
        if ($last !== null && $groups[$last]['hours'] === $label) {
            $groups[$last]['to'] = $day;
        } else {
            $groups[] = ['from' => $day, 'to' => $day, 'hours' => $label];
        }
    }
    return array_map(static fn ($g) => [
        'days'  => $g['from'] === $g['to'] ? $names[$g['from']] : $names[$g['from']] . ' to ' . $names[$g['to']],
        'hours' => $g['hours'],
    ], $groups);
}

/** Short status line for today, e.g. "Open today 8:00am to 7:00pm". */
function today_hours_label(): string
{
    $slot = opening_hours()[day_key(new DateTime())] ?? null;
    return $slot ? 'Open today ' . format_time($slot[0]) . ' to ' . format_time($slot[1]) : 'Closed today';
}

function store_address_line(): string
{
    return sprintf('%s, %s %s %s', setting('store_street'), setting('store_suburb'), setting('store_state'), setting('store_postcode'));
}

function store_maps_url(): string
{
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(setting('store_name') . ', ' . store_address_line());
}
