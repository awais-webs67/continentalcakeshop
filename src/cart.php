<?php
declare(strict_types=1);

// The session only stores IDs, quantities and messages. Names and prices are always
// re-read from the database so a stale session can never change what the customer pays.

const CART_MAX_QTY = 20;
const CAKE_MESSAGE_MAX = 40;

function cart_raw(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_line_key(int $sizeId, string $message): string
{
    return $sizeId . ':' . md5($message);
}

function cart_add(int $sizeId, int $qty, string $message = ''): bool
{
    $size = db_one(
        'SELECT s.id, p.allow_message FROM product_sizes s
         JOIN products p ON p.id = s.product_id AND p.is_active = 1 AND p.is_enquiry = 0
         WHERE s.id = ? AND s.is_active = 1',
        [$sizeId]
    );
    if (!$size) {
        return false;
    }
    $message = (int) $size['allow_message'] === 1 ? str_limit(trim($message), CAKE_MESSAGE_MAX) : '';
    $key = cart_line_key($sizeId, $message);
    $current = $_SESSION['cart'][$key]['qty'] ?? 0;
    $_SESSION['cart'][$key] = [
        'size_id' => $sizeId,
        'qty'     => min(CART_MAX_QTY, $current + max(1, $qty)),
        'message' => $message,
    ];
    return true;
}

function cart_update(string $key, int $qty): void
{
    if (!isset($_SESSION['cart'][$key])) {
        return;
    }
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
        return;
    }
    $_SESSION['cart'][$key]['qty'] = min(CART_MAX_QTY, $qty);
}

/** Swaps a cart line to another size of the same product, merging with an identical line if one exists. */
function cart_change_size(string $key, int $sizeId): void
{
    $line = $_SESSION['cart'][$key] ?? null;
    if (!$line || (int) $line['size_id'] === $sizeId) {
        return;
    }
    $sameProduct = db_value(
        'SELECT 1 FROM product_sizes a JOIN product_sizes b ON b.product_id = a.product_id
         WHERE a.id = ? AND b.id = ? AND b.is_active = 1',
        [$line['size_id'], $sizeId]
    );
    if (!$sameProduct) {
        return;
    }
    unset($_SESSION['cart'][$key]);
    $newKey = cart_line_key($sizeId, $line['message']);
    $qty = ($_SESSION['cart'][$newKey]['qty'] ?? 0) + $line['qty'];
    $_SESSION['cart'][$newKey] = ['size_id' => $sizeId, 'qty' => min(CART_MAX_QTY, $qty), 'message' => $line['message']];
}

function cart_remove(string $key): void
{
    unset($_SESSION['cart'][$key]);
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

function cart_count(): int
{
    return array_sum(array_column(cart_raw(), 'qty'));
}

/** Cart lines joined with current product data. Lines for removed products are dropped. */
function cart_items(): array
{
    $raw = cart_raw();
    if (!$raw) {
        return [];
    }
    $ids = array_values(array_unique(array_map('intval', array_column($raw, 'size_id'))));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $rows = db_all(
        "SELECT s.id AS size_id, s.label AS size_label, s.servings, s.price,
                p.id AS product_id, p.name, p.slug, p.image, p.lead_time_hours, c.name AS category_name
         FROM product_sizes s
         JOIN products p ON p.id = s.product_id AND p.is_active = 1
         JOIN categories c ON c.id = p.category_id AND c.is_active = 1
         WHERE s.is_active = 1 AND s.id IN ($placeholders)",
        $ids
    );
    $bySize = array_column($rows, null, 'size_id');

    $sizesByProduct = [];
    $productIds = array_values(array_unique(array_column($rows, 'product_id')));
    if ($productIds) {
        $in = implode(',', array_fill(0, count($productIds), '?'));
        foreach (db_all("SELECT id, product_id, label, price FROM product_sizes WHERE is_active = 1 AND product_id IN ($in) ORDER BY sort_order, price", $productIds) as $size) {
            $sizesByProduct[$size['product_id']][] = $size;
        }
    }

    $items = [];
    foreach ($raw as $key => $line) {
        $row = $bySize[$line['size_id']] ?? null;
        if (!$row) {
            unset($_SESSION['cart'][$key]);
            continue;
        }
        $items[] = $row + [
            'key'        => $key,
            'qty'        => (int) $line['qty'],
            'message'    => (string) $line['message'],
            'line_total' => round((float) $row['price'] * (int) $line['qty'], 2),
            'sizes'      => $sizesByProduct[$row['product_id']] ?? [],
        ];
    }
    return $items;
}

function cart_subtotal(array $items): float
{
    return round(array_sum(array_column($items, 'line_total')), 2);
}

/** Longest notice period needed by anything in the cart. */
function cart_lead_hours(array $items): int
{
    $hours = (int) setting('min_lead_hours', '24');
    foreach ($items as $item) {
        if ($item['lead_time_hours'] !== null) {
            $hours = max($hours, (int) $item['lead_time_hours']);
        }
    }
    return $hours;
}
