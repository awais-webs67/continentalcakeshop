<?php
declare(strict_types=1);

// Discount coupons. The admin creates codes in Admin > Coupons; customers apply one at checkout.
// The discount comes off the cake subtotal, never the delivery fee.

function coupons_enabled(): bool
{
    return setting_bool('coupons_enabled');
}

function normalise_coupon_code(string $code): string
{
    return strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $code));
}

function find_coupon(string $code): ?array
{
    $code = normalise_coupon_code($code);
    return $code === '' ? null : db_one('SELECT * FROM coupons WHERE code = ?', [$code]);
}

/** Why a coupon cannot be used on this subtotal, or null when it can. */
function coupon_error(?array $coupon, float $subtotal): ?string
{
    $today = date('Y-m-d');
    return match (true) {
        !coupons_enabled()                                   => 'Coupons are not available right now.',
        !$coupon || !(int) $coupon['is_active']              => 'That coupon code is not valid.',
        $coupon['starts_at'] && $coupon['starts_at'] > $today => 'That coupon starts on ' . date('j F Y', strtotime($coupon['starts_at'])) . '.',
        $coupon['expires_at'] && $coupon['expires_at'] < $today => 'That coupon expired on ' . date('j F Y', strtotime($coupon['expires_at'])) . '.',
        $coupon['max_uses'] !== null && (int) $coupon['used_count'] >= (int) $coupon['max_uses'] => 'That coupon has been fully used.',
        $subtotal < (float) $coupon['min_order']             => 'This coupon is for orders of ' . money($coupon['min_order']) . ' or more. Add ' . money((float) $coupon['min_order'] - $subtotal) . ' more to use it.',
        default                                              => null,
    };
}

function coupon_discount(array $coupon, float $subtotal): float
{
    $discount = $coupon['discount_type'] === 'percent'
        ? $subtotal * (float) $coupon['discount_value'] / 100
        : (float) $coupon['discount_value'];
    return round(min($subtotal, max(0, $discount)), 2);
}

/** "10% off" or "$15 off". */
function coupon_value_label(array $coupon): string
{
    $value = (float) $coupon['discount_value'];
    return $coupon['discount_type'] === 'percent'
        ? rtrim(rtrim(number_format($value, 2), '0'), '.') . '% off'
        : money($value) . ' off';
}

/** Plain sentence for the popup and admin, e.g. "10% off orders over $50, until 31 December 2026". */
function coupon_summary(array $coupon): string
{
    $text = coupon_value_label($coupon) . ((float) $coupon['min_order'] > 0 ? ' orders of ' . money($coupon['min_order']) . ' or more' : ' your order');
    if ($coupon['expires_at']) {
        $text .= ', until ' . date('j F Y', strtotime($coupon['expires_at']));
    }
    return $text;
}

// ---------------------------------------------------------------------------
// The coupon applied to the current cart (kept in the session as a code)
// ---------------------------------------------------------------------------

function applied_coupon_code(): string
{
    return (string) ($_SESSION['coupon_code'] ?? '');
}

function apply_coupon(string $code, float $subtotal): ?string
{
    $coupon = find_coupon($code);
    if ($error = coupon_error($coupon, $subtotal)) {
        return $error;
    }
    $_SESSION['coupon_code'] = $coupon['code'];
    return null;
}

function remove_coupon(): void
{
    unset($_SESSION['coupon_code']);
}

/** The applied coupon if it still works for this subtotal. */
function active_coupon(float $subtotal): ?array
{
    $code = applied_coupon_code();
    if ($code === '') {
        return null;
    }
    $coupon = find_coupon($code);
    return coupon_error($coupon, $subtotal) === null ? $coupon : null;
}

/** Counts one use. Returns false when the last use was taken in the meantime. */
function redeem_coupon(array $coupon): bool
{
    return db_run(
        'UPDATE coupons SET used_count = used_count + 1 WHERE id = ? AND is_active = 1 AND (max_uses IS NULL OR used_count < max_uses)',
        [$coupon['id']]
    )->rowCount() === 1;
}

// ---------------------------------------------------------------------------
// Delivery fee and order totals
// ---------------------------------------------------------------------------

/** Delivery fee for an order amount, after any free delivery threshold. */
function delivery_fee_for(float $amount): float
{
    $fee = (float) setting('delivery_fee', '0');
    $freeOver = (float) setting('delivery_free_over', '0');
    return $fee <= 0 || ($freeOver > 0 && $amount >= $freeOver) ? 0.0 : $fee;
}

/** @return array{subtotal:float,discount:float,delivery_fee:float,total:float} */
function order_totals(float $subtotal, string $fulfilment, ?array $coupon): array
{
    $discount = $coupon ? coupon_discount($coupon, $subtotal) : 0.0;
    $fee = $fulfilment === 'delivery' ? delivery_fee_for($subtotal - $discount) : 0.0;
    return [
        'subtotal'     => round($subtotal, 2),
        'discount'     => $discount,
        'delivery_fee' => $fee,
        'total'        => round($subtotal - $discount + $fee, 2),
    ];
}

/** Status for lists: [badge style, label]. */
function coupon_status(array $coupon): array
{
    $today = date('Y-m-d');
    return match (true) {
        !(int) $coupon['is_active']                          => ['inactive', 'Off'],
        $coupon['expires_at'] && $coupon['expires_at'] < $today => ['cancelled', 'Expired'],
        $coupon['max_uses'] !== null && (int) $coupon['used_count'] >= (int) $coupon['max_uses'] => ['cancelled', 'Used up'],
        $coupon['starts_at'] && $coupon['starts_at'] > $today => ['pending', 'Starts ' . date('j M', strtotime($coupon['starts_at']))],
        default                                              => ['active', 'Active'],
    };
}
