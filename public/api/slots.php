<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

// Checkout helper. Pickup: returns the free pickup times for a date.
// Delivery (?type=delivery): says whether the date can be booked, and why not.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$date = input('date');
$leadHours = cart_lead_hours(cart_items());

if (input('type') === 'delivery') {
    $error = delivery_date_error($date, $leadHours);
    echo json_encode(['date' => $date, 'available' => $error === null, 'message' => $error]);
    exit;
}

$slots = pickup_slots($date, $leadHours);
echo json_encode([
    'date'  => $date,
    'slots' => array_map(static fn ($s) => ['value' => $s, 'label' => format_time($s)], $slots),
]);
