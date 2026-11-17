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
        'branch_id'         => (int) ($request['branch_id'] ?? 0) ?: null,
        'custom_request_id' => (int) $request['id'],
        'user_id'           => find_verified_user_by_email($request['email'])['id'] ?? null,
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

/**
 * Staff confirm a custom cake the customer agreed to by phone, email or in the shop: it becomes a
 * confirmed order (Orders > Upcoming, sales reports, receipts), with any deposit already taken.
 *
 * $opts: price, date (Y-m-d), time (H:i or ''), fulfilment, delivery_address, delivery_suburb,
 * delivery_postcode, payment_method (how the rest will be paid), deposit, deposit_method, notify.
 */
function confirm_request_as_order(array $request, array $opts, ?int $adminId): array
{
    $price = round((float) $opts['price'], 2);
    $delivery = ($opts['fulfilment'] ?? $request['fulfilment'] ?? 'pickup') === 'delivery';
    db_run('UPDATE custom_requests SET quoted_price = ?, event_date = ?, fulfilment = ? WHERE id = ?',
        [$price, $opts['date'], $delivery ? 'delivery' : 'pickup', $request['id']]);
    $request = db_one('SELECT * FROM custom_requests WHERE id = ?', [$request['id']]);
    $order = order_for_quote($request, [
        'payment_method'    => $opts['payment_method'],
        'payment_proof'     => null,
        'delivery_address'  => $opts['delivery_address'] ?? '',
        'delivery_suburb'   => $opts['delivery_suburb'] ?? '',
        'delivery_postcode' => $opts['delivery_postcode'] ?? '',
    ]);
    $id = (int) $order['id'];
    db_run("UPDATE orders SET order_status = 'confirmed', pickup_time = ?, notes = ? WHERE id = ?", [
        ($opts['time'] ?? '') !== '' ? $opts['time'] . ':00' : null,
        'Custom cake quote agreed with the shop.',
        $id,
    ]);
    log_order_event($id, 'status', 'Confirmed by the shop from the custom cake request.', $adminId);
    $deposit = round(min($price, max(0, (float) ($opts['deposit'] ?? 0))), 2);
    if ($deposit > 0) {
        record_payment(get_order($id), $deposit, (string) $opts['deposit_method'], null, $deposit < $price ? 'Deposit' : 'Paid in full', $adminId);
    }
    $order = get_order($id);
    if (!empty($opts['notify']) && valid_email((string) $order['customer_email'])) {
        $balance = order_balance($order);
        send_order_update_email($order, 'Your custom cake is confirmed',
            'Thank you, your ' . strtolower($request['occasion']) . ' cake is booked for ' . order_when($order) . '. The price is ' . money($order['total']) . '.'
            . ($deposit > 0 ? ' We have received ' . money($deposit) . ($balance > 0.004 ? ', and ' . money($balance) . ' is due before ' . ($delivery ? 'delivery' : 'pickup') . '.' : ', so it is fully paid.') : '')
            . "\n\nYour receipt: " . invoice_url($order));
        log_order_event($id, 'email', 'Customer emailed the confirmation.', $adminId);
    }
    return $order;
}

/**
 * Keeps a copy of the gallery photo a customer chose ("Order one like this") with their request,
 * in storage/uploads/custom, so it stays even if the photo later leaves the gallery.
 * Returns the path inside storage/uploads, or null.
 */
function copy_gallery_photo(array $photo): ?string
{
    $image = (string) $photo['image'];
    $source = BASE_PATH . '/public/uploads/' . ltrim($image, '/');
    $ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));
    if (str_contains($image, '..') || !in_array($ext, UPLOAD_IMAGE_TYPES, true) || !is_file($source)) {
        return null;
    }
    $folder = ASYNC_UPLOADS['custom']['folder'] . '/' . date('Y/m');
    $dir = STORAGE_PATH . '/uploads/' . $folder;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return null;
    }
    $path = $folder . '/gallery-' . (int) $photo['id'] . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    return @copy($source, STORAGE_PATH . '/uploads/' . $path) ? $path : null;
}

/** The photos of a custom request: the gallery cake the customer chose and the photo they sent. */
function custom_request_photos(array $request): array
{
    $photos = [];
    foreach (['gallery_image' => 'Chosen from your gallery', 'reference_image' => 'Photo they sent'] as $key => $label) {
        $path = (string) ($request[$key] ?? '');
        if ($path !== '' && !str_contains($path, '..')) {
            $photos[] = ['path' => $path, 'label' => $label, 'is_image' => (bool) preg_match('/\.(jpe?g|png|webp|gif)$/i', $path)];
        }
    }
    return $photos;
}
