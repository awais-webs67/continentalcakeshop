<?php
declare(strict_types=1);

// Custom cake quotes the customer can accept and pay online. Staff send a price (and an
// optional deposit) from Admin > Custom requests; the email has an "Accept and pay" button
// that opens /quote/<token>. Paying turns the request into a normal order.

function quote_url(array $request): string
{
    return url('quote/' . $request['quote_token']);
}

function get_request_by_quote_token(string $token): ?array
{
    return preg_match('/^[a-f0-9]{32}$/', $token) ? db_one('SELECT * FROM custom_requests WHERE quote_token = ?', [$token]) : null;
}

/** True when the customer can accept and pay this quote on the website. */
function quote_payable(array $request): bool
{
    return (float) $request['quoted_price'] > 0 && ($request['quote_token'] ?? '') !== '' && $request['status'] !== 'closed';
}

/** What the customer pays to accept: the deposit, or the full price when no deposit is set. */
function quote_deposit(array $request): float
{
    $price = (float) $request['quoted_price'];
    $deposit = $request['deposit_amount'] !== null ? (float) $request['deposit_amount'] : $price;
    return round(min($price, max(0.01, $deposit)), 2);
}

/** Line for the order made from a quote, e.g. "Wedding cake (custom)". */
function quote_line(array $request): array
{
    $price = round((float) $request['quoted_price'], 2);
    return [
        'product_id' => null, 'size_id' => null, 'addon_id' => null,
        'name' => $request['occasion'] . ' cake (custom)', 'size_label' => (string) ($request['servings'] ?? ''),
        'options' => str_limit((string) ($request['flavour'] ?? ''), 255), 'message' => '',
        'price' => $price, 'qty' => 1, 'line_total' => $price,
    ];
}

/**
 * The order for an accepted quote, made the first time the customer accepts it.
 * $data: payment_method, payment_proof (path or null), delivery address fields.
 */
function order_for_quote(array $request, array $data): array
{
    if ($request['order_id'] && ($existing = get_order((int) $request['order_id']))) {
        return $existing;
    }
    $delivery = ($request['fulfilment'] ?? 'pickup') === 'delivery';
    $deposit = quote_deposit($request);
    $order = create_order([
        'channel'           => 'quote',
        'custom_request_id' => (int) $request['id'],
        'user_id'           => find_user_by_email($request['email'])['id'] ?? null,
        'name'              => $request['name'],
        'email'             => $request['email'],
        'phone'             => $request['phone'],
        'fulfilment'        => $delivery ? 'delivery' : 'pickup',
        'delivery_address'  => $delivery ? ($data['delivery_address'] ?? '') : '',
        'delivery_suburb'   => $delivery ? ($data['delivery_suburb'] ?? '') : '',
        'delivery_postcode' => $delivery ? ($data['delivery_postcode'] ?? '') : '',
        'delivery_window'   => '',
        'pickup_date'       => $request['event_date'],
        'pickup_time'       => '',
        'payment_method'    => $data['payment_method'],
        'payment_reference' => '',
        'payment_proof'     => $data['payment_proof'] ?? null,
        'notes'             => $deposit < (float) $request['quoted_price'] - 0.004
            ? 'Custom cake quote accepted. Deposit to pay now: ' . money($deposit) . ' of ' . money($request['quoted_price']) . '.'
            : 'Custom cake quote accepted.',
    ], [quote_line($request)], null, [
        'subtotal' => round((float) $request['quoted_price'], 2), 'discount' => 0.0, 'delivery_fee' => 0.0, 'total' => round((float) $request['quoted_price'], 2),
    ]);
    db_run("UPDATE custom_requests SET order_id = ?, status = 'confirmed' WHERE id = ?", [$order['id'], $request['id']]);
    // The conversation about the request now also belongs to the order.
    try {
        db_run('UPDATE threads SET order_id = ? WHERE custom_request_id = ? AND order_id IS NULL', [$order['id'], $request['id']]);
    } catch (PDOException) {
        // The order already has its own conversation; keep both.
    }
    return $order;
}
