<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

// Returns the pickup times for a date, using the notice period required by the current cart.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$date = input('date');
$slots = pickup_slots($date, cart_lead_hours(cart_items()));

echo json_encode([
    'date'  => $date,
    'slots' => array_map(static fn ($s) => ['value' => $s, 'label' => format_time($s)], $slots),
]);
