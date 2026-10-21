<?php
declare(strict_types=1);

const PRODUCT_SORTS = [
    'featured'   => 'Featured',
    'price-asc'  => 'Price: low to high',
    'price-desc' => 'Price: high to low',
    'name'       => 'Name: A to Z',
];

// Every listing query selects the same columns plus the price range of active sizes.
const PRODUCT_LIST_SQL = '
    SELECT p.*, c.name AS category_name, c.slug AS category_slug,
           MIN(s.price) AS price_from, MAX(s.price) AS price_to, COUNT(s.id) AS size_count,
           (SELECT COUNT(*) FROM product_options po WHERE po.product_id = p.id AND po.is_active = 1) AS option_count,
           (SELECT s2.id FROM product_sizes s2 WHERE s2.product_id = p.id AND s2.is_active = 1 ORDER BY s2.sort_order, s2.price LIMIT 1) AS default_size_id
    FROM products p
    JOIN categories c ON c.id = p.category_id AND c.is_active = 1
    LEFT JOIN product_sizes s ON s.product_id = p.id AND s.is_active = 1
    WHERE p.is_active = 1
      AND (p.is_enquiry = 1 OR EXISTS (SELECT 1 FROM product_sizes s3 WHERE s3.product_id = p.id AND s3.is_active = 1))';

function get_categories(): array
{
    return db_all(
        'SELECT c.*, MIN(s.price) AS price_from, COUNT(DISTINCT p.id) AS product_count,
                (SELECT p2.image FROM products p2 WHERE p2.category_id = c.id AND p2.is_active = 1 AND p2.image IS NOT NULL
                 ORDER BY p2.is_featured DESC, p2.sort_order LIMIT 1) AS cover_image
         FROM categories c
         JOIN products p ON p.category_id = c.id AND p.is_active = 1
         LEFT JOIN product_sizes s ON s.product_id = p.id AND s.is_active = 1
         WHERE c.is_active = 1 AND (p.is_enquiry = 1 OR s.id IS NOT NULL)
         GROUP BY c.id
         ORDER BY c.sort_order, c.name'
    );
}

function get_category_by_slug(string $slug): ?array
{
    return db_one('SELECT * FROM categories WHERE slug = ? AND is_active = 1', [$slug]);
}

function get_products(?int $categoryId = null, string $sort = 'featured', ?string $search = null): array
{
    $sql = PRODUCT_LIST_SQL;
    $params = [];
    if ($categoryId !== null) {
        $sql .= ' AND p.category_id = ?';
        $params[] = $categoryId;
    }
    if ($search !== null && $search !== '') {
        $sql .= ' AND (p.name LIKE ? OR p.short_description LIKE ?)';
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
        array_push($params, $like, $like);
    }
    $sql .= ' GROUP BY p.id ORDER BY ' . match ($sort) {
        'price-asc'  => 'price_from IS NULL, price_from ASC, p.name',
        'price-desc' => 'price_from IS NULL, price_from DESC, p.name',
        'name'       => 'p.name ASC',
        default      => 'p.is_featured DESC, p.image IS NULL, c.sort_order, p.sort_order, p.name',
    };
    return db_all($sql, $params);
}

function get_featured_products(int $limit = 8): array
{
    return db_all(PRODUCT_LIST_SQL . ' AND p.is_featured = 1 GROUP BY p.id ORDER BY p.badge IS NULL, c.sort_order, p.sort_order LIMIT ' . max(1, $limit));
}

function get_related_products(array $product, int $limit = 4): array
{
    return db_all(
        PRODUCT_LIST_SQL . ' AND p.category_id = ? AND p.id <> ? GROUP BY p.id ORDER BY p.image IS NULL, p.is_featured DESC, p.sort_order LIMIT ' . max(1, $limit),
        [$product['category_id'], $product['id']]
    );
}

function get_product_by_slug(string $slug): ?array
{
    return db_one(
        'SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM products p JOIN categories c ON c.id = p.category_id AND c.is_active = 1
         WHERE p.slug = ? AND p.is_active = 1',
        [$slug]
    );
}

function get_product_sizes(int $productId): array
{
    return db_all(
        'SELECT * FROM product_sizes WHERE product_id = ? AND is_active = 1 ORDER BY sort_order, price',
        [$productId]
    );
}

function get_product_options(int $productId): array
{
    return db_all('SELECT * FROM product_options WHERE product_id = ? AND is_active = 1 ORDER BY sort_order, id', [$productId]);
}

/** Custom cake form, pre-filled with the cake a customer is asking about. */
function enquiry_url(array $product): string
{
    return url('custom-cakes') . '?cake=' . rawurlencode($product['slug']) . '#quote';
}

function product_url(array $product): string
{
    return url('cakes/' . $product['slug']);
}

function category_url(array $category): string
{
    return url('shop/' . $category['slug']);
}

/** Uploaded product photos live in public/uploads/products; falls back to a neutral placeholder. */
function product_image_url(?string $image): string
{
    if ($image) {
        return preg_match('#^https?://#', $image) ? $image : url('uploads/' . ltrim($image, '/'));
    }
    return asset('img/placeholder-cake.svg');
}

function price_label(array $product): string
{
    if (!empty($product['is_enquiry']) || $product['price_from'] === null) {
        return 'Price on request';
    }
    if ((int) ($product['size_count'] ?? 1) > 1 && (float) $product['price_from'] !== (float) $product['price_to']) {
        return 'From ' . money($product['price_from']);
    }
    return money($product['price_from']);
}

// ---------------------------------------------------------------------------
// Sold out cakes and pausing online orders
// ---------------------------------------------------------------------------

const SOLD_OUT_FOREVER = '9999-12-31';

/** True while a cake is marked sold out (the mark ends by itself after its last day). */
function product_sold_out(array $product): bool
{
    $until = (string) ($product['sold_out_until'] ?? '');
    return $until !== '' && $until >= date('Y-m-d');
}

/** When a sold out cake is back, e.g. "tomorrow" or "Saturday 2 November". Empty when unknown. */
function product_back_label(array $product): string
{
    $until = (string) ($product['sold_out_until'] ?? '');
    if ($until === '' || $until === SOLD_OUT_FOREVER) {
        return '';
    }
    $back = (new DateTimeImmutable($until))->modify('+1 day');
    return $back->format('Y-m-d') === date('Y-m-d', strtotime('+1 day')) ? 'tomorrow' : $back->format('l j F');
}

/** Online ordering switched off in Admin > Settings > Ordering (ends by itself after the chosen date). */
function ordering_paused(): bool
{
    if (!setting_bool('ordering_paused')) {
        return false;
    }
    $until = setting('ordering_paused_until');
    return $until === '' || $until >= date('Y-m-d');
}

function ordering_paused_message(): string
{
    $message = setting('ordering_paused_message', 'We are not taking online orders right now. Please call us and we will do our best to help.');
    $until = setting('ordering_paused_until');
    if ($until !== '' && !str_contains($message, '{date}')) {
        $message .= ' Online orders open again on ' . (new DateTimeImmutable($until))->modify('+1 day')->format('l j F') . '.';
    }
    return str_replace('{date}', $until !== '' ? (new DateTimeImmutable($until))->modify('+1 day')->format('l j F') : '', $message);
}

// ---------------------------------------------------------------------------
// WhatsApp chat button
// ---------------------------------------------------------------------------

/** wa.me link for the shop's WhatsApp number (set in Settings), or '' when none is set. */
function whatsapp_url(string $text = ''): string
{
    $digits = preg_replace('/\D/', '', setting('whatsapp_number'));
    if ($digits === '') {
        return '';
    }
    if (str_starts_with($digits, '0')) {
        $digits = '61' . substr($digits, 1);
    }
    $text = $text !== '' ? $text : setting('whatsapp_message');
    return 'https://wa.me/' . $digits . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

function get_gallery(?int $limit = null, int $offset = 0): array
{
    $sql = 'SELECT * FROM gallery_images WHERE is_active = 1 ORDER BY sort_order, id';
    if ($limit) {
        $sql .= ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
    }
    return db_all($sql);
}

function count_gallery(): int
{
    return (int) db_value('SELECT COUNT(*) FROM gallery_images WHERE is_active = 1');
}
