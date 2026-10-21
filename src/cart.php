<?php
declare(strict_types=1);

// The session only stores IDs, quantities, options and messages. Names and prices are always
// re-read from the database so a stale session can never change what the customer pays.

const CART_MAX_QTY = 20;
const CAKE_MESSAGE_MAX = 40;

function cart_raw(): array
{
    return $_SESSION['cart'] ?? [];
}

/** Same cake, size, options and message share one line; anything different is a new line. */
function cart_line_key(int $sizeId, string $message, array $optionIds = []): string
{
    sort($optionIds);
    return $sizeId . ':' . md5($message . '|' . implode(',', $optionIds));
}

/** Active option ids of a product, keeping only the ones in $wanted. */
function valid_option_ids(int $productId, array $wanted): array
{
    $wanted = array_values(array_unique(array_filter(array_map('intval', $wanted))));
    if (!$wanted) {
        return [];
    }
    $in = implode(',', array_fill(0, count($wanted), '?'));
    $ids = array_map('intval', array_column(db_all(
        "SELECT id FROM product_options WHERE product_id = ? AND is_active = 1 AND id IN ($in)",
        [$productId, ...$wanted]
    ), 'id'));
    sort($ids);
    return $ids;
}

/** Why a cake cannot be added right now (ordering paused or sold out), or null. */
function cart_add_error(int $sizeId): ?string
{
    $size = db_one(
        'SELECT p.* FROM product_sizes s JOIN products p ON p.id = s.product_id AND p.is_active = 1 AND p.is_enquiry = 0
         WHERE s.id = ? AND s.is_active = 1',
        [$sizeId]
    );
    if (!$size) {
        return 'Sorry, that cake is no longer available.';
    }
    if (ordering_paused()) {
        return ordering_paused_message();
    }
    if (product_sold_out($size)) {
        return 'Sorry, ' . $size['name'] . ' is sold out' . (product_back_label($size) !== '' ? ' until ' . product_back_label($size) : '') . '.';
    }
    return null;
}

function cart_add(int $sizeId, int $qty, string $message = '', array $optionIds = []): bool
{
    if (cart_add_error($sizeId) !== null) {
        return false;
    }
    $size = db_one('SELECT s.id, s.product_id, p.allow_message FROM product_sizes s JOIN products p ON p.id = s.product_id WHERE s.id = ?', [$sizeId]);
    $message = (int) $size['allow_message'] === 1 ? str_limit(trim($message), CAKE_MESSAGE_MAX) : '';
    $options = valid_option_ids((int) $size['product_id'], $optionIds);
    $key = cart_line_key($sizeId, $message, $options);
    $current = $_SESSION['cart'][$key]['qty'] ?? 0;
    $_SESSION['cart'][$key] = [
        'size_id' => $sizeId,
        'qty'     => min(CART_MAX_QTY, $current + max(1, $qty)),
        'message' => $message,
        'options' => $options,
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
    $options = $line['options'] ?? [];
    unset($_SESSION['cart'][$key]);
    $newKey = cart_line_key($sizeId, $line['message'], $options);
    $qty = ($_SESSION['cart'][$newKey]['qty'] ?? 0) + $line['qty'];
    $_SESSION['cart'][$newKey] = ['size_id' => $sizeId, 'qty' => min(CART_MAX_QTY, $qty), 'message' => $line['message'], 'options' => $options];
}

function cart_remove(string $key): void
{
    unset($_SESSION['cart'][$key]);
}

function cart_clear(): void
{
    unset($_SESSION['cart'], $_SESSION['cart_addons']);
}

function cart_count(): int
{
    return array_sum(array_column(cart_raw(), 'qty')) + array_sum($_SESSION['cart_addons'] ?? []);
}

/** Cake lines joined with current product data. Lines for removed products are dropped. */
function cart_items(): array
{
    $raw = cart_raw();
    if (!$raw) {
        return [];
    }
    $ids = array_values(array_unique(array_map('intval', array_column($raw, 'size_id'))));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $rows = db_all(
        "SELECT s.id AS size_id, s.label AS size_label, s.servings, s.price AS size_price,
                p.id AS product_id, p.name, p.slug, p.image, p.lead_time_hours, c.name AS category_name
         FROM product_sizes s
         JOIN products p ON p.id = s.product_id AND p.is_active = 1
         JOIN categories c ON c.id = p.category_id AND c.is_active = 1
         WHERE s.is_active = 1 AND s.id IN ($placeholders)",
        $ids
    );
    $bySize = array_column($rows, null, 'size_id');

    $sizesByProduct = [];
    $optionsById = [];
    $productIds = array_values(array_unique(array_column($rows, 'product_id')));
    if ($productIds) {
        $in = implode(',', array_fill(0, count($productIds), '?'));
        foreach (db_all("SELECT id, product_id, label, price FROM product_sizes WHERE is_active = 1 AND product_id IN ($in) ORDER BY sort_order, price", $productIds) as $size) {
            $sizesByProduct[$size['product_id']][] = $size;
        }
        foreach (db_all("SELECT id, product_id, name, price FROM product_options WHERE is_active = 1 AND product_id IN ($in) ORDER BY sort_order, id", $productIds) as $option) {
            $optionsById[(int) $option['id']] = $option;
        }
    }

    $items = [];
    foreach ($raw as $key => $line) {
        $row = $bySize[$line['size_id']] ?? null;
        if (!$row) {
            unset($_SESSION['cart'][$key]);
            continue;
        }
        // Options that were removed or switched off since are dropped quietly.
        $chosen = [];
        foreach ($line['options'] ?? [] as $optionId) {
            $option = $optionsById[(int) $optionId] ?? null;
            if ($option && (int) $option['product_id'] === (int) $row['product_id']) {
                $chosen[] = $option;
            }
        }
        $price = round((float) $row['size_price'] + array_sum(array_map(static fn ($o) => (float) $o['price'], $chosen)), 2);
        $items[] = $row + [
            'key'        => $key,
            'qty'        => (int) $line['qty'],
            'message'    => (string) $line['message'],
            'option_ids' => array_map(static fn ($o) => (int) $o['id'], $chosen),
            'options'    => implode(', ', array_map(static fn ($o) => $o['name'], $chosen)),
            'price'      => $price,
            'line_total' => round($price * (int) $line['qty'], 2),
            'sizes'      => $sizesByProduct[$row['product_id']] ?? [],
            'addon_id'   => null,
        ];
    }
    return $items;
}

// ---------------------------------------------------------------------------
// Add-ons: extras for the whole order (candles, knife, topper, gift card)
// ---------------------------------------------------------------------------

function addons_enabled(): bool
{
    return setting_bool('addons_enabled');
}

/** Add-ons customers can choose right now. */
function available_addons(): array
{
    if (!addons_enabled()) {
        return [];
    }
    try {
        return db_all('SELECT * FROM addons WHERE is_active = 1 ORDER BY sort_order, name');
    } catch (PDOException) {
        return [];
    }
}

function cart_set_addon(int $addonId, int $qty): void
{
    $addon = db_one('SELECT id, max_qty FROM addons WHERE id = ? AND is_active = 1', [$addonId]);
    if (!$addon || $qty <= 0) {
        unset($_SESSION['cart_addons'][$addonId]);
        return;
    }
    $_SESSION['cart_addons'][$addonId] = min($qty, max(1, (int) $addon['max_qty']));
}

/** Chosen add-ons as order lines (same shape as cake lines). */
function cart_addon_lines(): array
{
    $chosen = $_SESSION['cart_addons'] ?? [];
    if (!$chosen || !addons_enabled()) {
        return [];
    }
    $lines = [];
    foreach (available_addons() as $addon) {
        $qty = (int) ($chosen[(int) $addon['id']] ?? 0);
        if ($qty <= 0) {
            continue;
        }
        $lines[] = [
            'key' => 'addon-' . $addon['id'], 'addon_id' => (int) $addon['id'], 'product_id' => null, 'size_id' => null,
            'name' => $addon['name'], 'size_label' => 'Extra', 'options' => '', 'message' => '',
            'price' => round((float) $addon['price'], 2), 'qty' => $qty, 'line_total' => round((float) $addon['price'] * $qty, 2),
            'lead_time_hours' => null, 'image' => null, 'slug' => null,
        ];
    }
    return $lines;
}

/** Everything that goes on the order: cakes, then add-ons. */
function cart_order_lines(): array
{
    $cakes = cart_items();
    return $cakes ? array_merge($cakes, cart_addon_lines()) : [];
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
        if (($item['lead_time_hours'] ?? null) !== null) {
            $hours = max($hours, (int) $item['lead_time_hours']);
        }
    }
    return $hours;
}

/**
 * Puts the cakes and extras of a past order back in the cart, at today's prices.
 * Returns the names of anything that can no longer be ordered.
 */
function cart_reorder(array $order): array
{
    $skipped = [];
    foreach (get_order_items((int) $order['id']) as $item) {
        if ($item['addon_id']) {
            if (db_value('SELECT 1 FROM addons WHERE id = ? AND is_active = 1', [$item['addon_id']]) && addons_enabled()) {
                cart_set_addon((int) $item['addon_id'], (int) ($_SESSION['cart_addons'][(int) $item['addon_id']] ?? 0) + (int) $item['quantity']);
            } else {
                $skipped[] = $item['product_name'];
            }
            continue;
        }
        if (!$item['size_id'] || cart_add_error((int) $item['size_id']) !== null) {
            $skipped[] = $item['product_name'];
            continue;
        }
        // Options were saved by name; pick the ones the cake still offers.
        $optionIds = [];
        if ($item['options'] && $item['product_id']) {
            $names = array_map('trim', explode(',', (string) $item['options']));
            foreach (db_all('SELECT id, name FROM product_options WHERE product_id = ? AND is_active = 1', [$item['product_id']]) as $option) {
                if (in_array($option['name'], $names, true)) {
                    $optionIds[] = (int) $option['id'];
                }
            }
        }
        cart_add((int) $item['size_id'], (int) $item['quantity'], (string) $item['cake_message'], $optionIds);
    }
    return $skipped;
}
