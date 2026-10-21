<?php
declare(strict_types=1);

// Adding or updating many products from a spreadsheet (Admin > Products > Import).
// The same columns are used for the template, the export of current products and the import.

const IMPORT_COLUMNS = [
    'name'             => 'Cake name (required)',
    'slug'             => 'Web address, e.g. red-velvet-cake. Leave empty for new cakes',
    'category'         => 'Category name, e.g. Tortes (required)',
    'short_description'=> 'One line shown under the name',
    'description'      => 'Full description',
    'size1_name'       => 'e.g. Midi',
    'size1_serves'     => 'e.g. Serves 8',
    'size1_price'      => 'e.g. 44.95',
    'size2_name'       => 'e.g. Large',
    'size2_serves'     => 'e.g. Serves 14',
    'size2_price'      => 'e.g. 54.95',
    'size3_name'       => 'Optional third size',
    'size3_serves'     => '',
    'size3_price'      => '',
    'visible'          => 'yes or no',
    'featured'         => 'yes or no (homepage)',
    'price_on_request' => 'yes or no (shows Enquire instead of Add to Cart)',
    'allow_message'    => 'yes or no (free message on the cake)',
    'badge'            => 'e.g. Bestseller',
    'notice_hours'     => 'Hours of notice, empty = shop default',
    'sort_order'       => 'Lower numbers show first',
    'image_url'        => 'Optional link to a photo (https://...)',
    'meta_title'       => 'Optional page title for Google',
    'meta_description' => 'Optional description for Google',
    'focus_keyword'    => 'Optional words people search for, e.g. red velvet cake canberra',
    'image_alt'        => 'Optional description of the photo',
];

/** Rows of the current products in the import format, for the export and the template. */
function products_as_import_rows(): array
{
    $rows = [];
    foreach (db_all('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id ORDER BY c.sort_order, p.sort_order, p.name') as $p) {
        $sizes = db_all('SELECT label, servings, price FROM product_sizes WHERE product_id = ? AND is_active = 1 ORDER BY sort_order, price LIMIT 3', [$p['id']]);
        $row = [
            'name' => $p['name'], 'slug' => $p['slug'], 'category' => $p['category_name'],
            'short_description' => (string) $p['short_description'], 'description' => content_text((string) $p['description']),
        ];
        for ($i = 1; $i <= 3; $i++) {
            $size = $sizes[$i - 1] ?? null;
            $row["size{$i}_name"] = $size['label'] ?? '';
            $row["size{$i}_serves"] = (string) ($size['servings'] ?? '');
            $row["size{$i}_price"] = $size ? number_format((float) $size['price'], 2, '.', '') : '';
        }
        $row += [
            'visible' => $p['is_active'] ? 'yes' : 'no', 'featured' => $p['is_featured'] ? 'yes' : 'no',
            'price_on_request' => $p['is_enquiry'] ? 'yes' : 'no', 'allow_message' => $p['allow_message'] ? 'yes' : 'no',
            'badge' => (string) $p['badge'], 'notice_hours' => (string) $p['lead_time_hours'], 'sort_order' => (string) $p['sort_order'],
            'image_url' => $p['image'] ? product_image_url($p['image']) : '',
            'meta_title' => (string) $p['meta_title'], 'meta_description' => (string) $p['meta_description'],
            'focus_keyword' => (string) ($p['focus_keyword'] ?? ''), 'image_alt' => (string) ($p['image_alt'] ?? ''),
        ];
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Reads an uploaded CSV into rows keyed by column name. Accepts files saved by Excel
 * (with or without the UTF-8 marker, comma or semicolon separated).
 *
 * @return array{0:array,1:?string}
 */
function read_import_csv(string $path): array
{
    $raw = (string) file_get_contents($path);
    if (!mb_check_encoding($raw, 'UTF-8')) {
        $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
    }
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    $firstLine = strtok($raw, "\n");
    $delimiter = substr_count((string) $firstLine, ';') > substr_count((string) $firstLine, ',') ? ';' : ',';
    $handle = fopen('php://temp', 'r+');
    fwrite($handle, $raw);
    rewind($handle);
    $header = fgetcsv($handle, 0, $delimiter, '"', '');
    if (!$header) {
        return [[], 'The file is empty.'];
    }
    $header = array_map(static fn ($h) => strtolower(trim(str_replace([' ', '-'], '_', (string) $h))), $header);
    if (!in_array('name', $header, true) || !in_array('category', $header, true)) {
        return [[], 'The first row must have the column names from the template (at least "name" and "category").'];
    }
    $rows = [];
    while (($line = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
        if (count(array_filter($line, static fn ($v) => trim((string) $v) !== '')) === 0) {
            continue;
        }
        $row = [];
        foreach ($header as $i => $key) {
            $row[$key] = trim((string) ($line[$i] ?? ''));
        }
        $rows[] = $row;
        if (count($rows) > 1000) {
            return [[], 'Please import at most 1000 products at a time.'];
        }
    }
    fclose($handle);
    return [$rows, $rows ? null : 'The file has no products below the header row.'];
}

function import_yes(string $value, bool $default): bool
{
    $value = strtolower(trim($value));
    return $value === '' ? $default : in_array($value, ['yes', 'y', '1', 'true', 'on'], true);
}

/**
 * Checks one row and turns it into product data. Returns [plan, errors].
 * The plan says whether the product is new or updated, and holds the clean values.
 */
function plan_import_row(array $row, array $categories, bool $createCategories): array
{
    $errors = [];
    $name = str_limit($row['name'] ?? '', 160);
    if ($name === '') {
        $errors[] = 'Name is missing.';
    }
    $categoryName = trim($row['category'] ?? '');
    $categoryId = $categories[strtolower($categoryName)] ?? null;
    if ($categoryName === '') {
        $errors[] = 'Category is missing.';
    } elseif (!$categoryId && !$createCategories) {
        $errors[] = 'Category "' . $categoryName . '" does not exist. Tick "Create missing categories" or fix the name.';
    }
    $sizes = [];
    for ($i = 1; $i <= 3; $i++) {
        $label = str_limit($row["size{$i}_name"] ?? '', 60);
        $priceText = str_replace(['$', ',', ' '], '', $row["size{$i}_price"] ?? '');
        if ($label === '' && $priceText === '') {
            continue;
        }
        if ($label === '' || !is_numeric($priceText) || (float) $priceText <= 0) {
            $errors[] = "Size {$i} needs a name and a price above 0.";
            continue;
        }
        $sizes[] = ['label' => $label, 'servings' => str_limit($row["size{$i}_serves"] ?? '', 60), 'price' => round((float) $priceText, 2)];
    }
    $enquiry = import_yes($row['price_on_request'] ?? '', false);
    if (!$sizes && !$enquiry) {
        $errors[] = 'Add at least one size with a price, or set price_on_request to yes.';
    }
    $imageUrl = trim($row['image_url'] ?? '');
    if ($imageUrl !== '' && !preg_match('#^https?://#i', $imageUrl)) {
        $errors[] = 'image_url must start with https://';
    }
    $slug = slugify(($row['slug'] ?? '') !== '' ? $row['slug'] : $name);
    $existing = db_one('SELECT id, image, description FROM products WHERE slug = ?', [$slug]);
    $notice = trim($row['notice_hours'] ?? '');

    // The export flattens formatted descriptions to plain text; when that text comes back
    // unchanged, the formatted version (bold, lists, links) is kept.
    $keepDescription = $existing && trim((string) ($row['description'] ?? '')) === content_text((string) $existing['description']);

    $plan = [
        'action'       => $existing ? 'update' : 'create',
        'id'           => $existing['id'] ?? null,
        'has_image'    => !empty($existing['image']),
        'category'     => $categoryName,
        'image_url'    => $imageUrl,
        'sizes'        => $sizes,
        'data'         => [
            'name'              => $name,
            'slug'              => $slug,
            'short_description' => str_limit($row['short_description'] ?? '', 255) ?: null,
            'description'       => ($row['description'] ?? '') !== '' ? '<p>' . nl2br(e(str_limit($row['description'], 5000))) . '</p>' : null,
            'is_active'         => import_yes($row['visible'] ?? '', true) ? 1 : 0,
            'is_featured'       => import_yes($row['featured'] ?? '', false) ? 1 : 0,
            'is_enquiry'        => $enquiry ? 1 : 0,
            'allow_message'     => import_yes($row['allow_message'] ?? '', true) ? 1 : 0,
            'badge'             => str_limit($row['badge'] ?? '', 40) ?: null,
            'lead_time_hours'   => $notice !== '' ? max(0, (int) $notice) : null,
            'sort_order'        => (int) ($row['sort_order'] ?? 0),
            'meta_title'        => str_limit($row['meta_title'] ?? '', 160) ?: null,
            'meta_description'  => str_limit($row['meta_description'] ?? '', 255) ?: null,
        ] + (array_key_exists('focus_keyword', $row) ? ['focus_keyword' => str_limit(mb_strtolower($row['focus_keyword']), 80) ?: null] : [])
          + (array_key_exists('image_alt', $row) ? ['image_alt' => str_limit($row['image_alt'], 160) ?: null] : []),
    ];
    if ($keepDescription) {
        unset($plan['data']['description']);
    }
    return [$plan, $errors];
}

/** Downloads a photo from a link into a temporary file. Returns the path or null. */
function download_import_image(string $url): ?string
{
    // Only public internet addresses, never the server's own network.
    $host = (string) parse_url($url, PHP_URL_HOST);
    $ip = gethostbyname($host);
    if ($host === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return null;
    }
    $tmp = tempnam(sys_get_temp_dir(), 'img');
    $ch = curl_init($url);
    $fh = fopen($tmp, 'wb');
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fh, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3, CURLOPT_TIMEOUT => 20,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_NOPROGRESS => false,
        // Stop large downloads early.
        CURLOPT_PROGRESSFUNCTION => static fn ($c, $total, $done) => $done > 10 * 1024 * 1024 ? 1 : 0,
    ]);
    $ok = curl_exec($ch) && (int) curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    fclose($fh);
    if (!$ok) {
        @unlink($tmp);
        return null;
    }
    return $tmp;
}

/** Saves one planned row. Returns a note about the photo, if any. */
function apply_import_row(array $plan, bool $replaceImages, array &$categories): string
{
    $pdo = db();
    $key = strtolower($plan['category']);
    if (!isset($categories[$key])) {
        db_run('INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, ?)', [$plan['category'], unique_slug('categories', slugify($plan['category'])), 99]);
        $categories[$key] = (int) $pdo->lastInsertId();
    }
    $data = $plan['data'] + ['category_id' => $categories[$key]];
    $note = '';
    if ($plan['image_url'] !== '' && (!$plan['has_image'] || $replaceImages)) {
        $tmp = download_import_image($plan['image_url']);
        [$image, $error] = $tmp ? store_image_file($tmp, 'products', 900, 900, $data['name']) : [null, 'could not download'];
        $tmp && @unlink($tmp);
        if ($image) {
            $data['image'] = $image;
        } else {
            $note = 'Photo skipped (' . $error . ').';
        }
    }

    $pdo->beginTransaction();
    $columns = array_keys($data);
    if ($plan['action'] === 'update') {
        db_run('UPDATE products SET ' . implode(', ', array_map(static fn ($c) => "{$c} = ?", $columns)) . ' WHERE id = ?', [...array_values($data), $plan['id']]);
        $productId = (int) $plan['id'];
    } else {
        db_run('INSERT INTO products (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
        $productId = (int) $pdo->lastInsertId();
    }
    // Sizes are matched by name, so carts and past orders keep pointing at the same size.
    $current = array_column(db_all('SELECT id, label FROM product_sizes WHERE product_id = ?', [$productId]), 'id', 'label');
    $keep = [0];
    foreach ($plan['sizes'] as $i => $size) {
        if (isset($current[$size['label']])) {
            db_run('UPDATE product_sizes SET servings = ?, price = ?, sort_order = ?, is_active = 1 WHERE id = ?', [$size['servings'] ?: null, $size['price'], $i + 1, $current[$size['label']]]);
            $keep[] = (int) $current[$size['label']];
        } else {
            db_run('INSERT INTO product_sizes (product_id, label, servings, price, sort_order) VALUES (?, ?, ?, ?, ?)', [$productId, $size['label'], $size['servings'] ?: null, $size['price'], $i + 1]);
            $keep[] = (int) $pdo->lastInsertId();
        }
    }
    db_run('DELETE FROM product_sizes WHERE product_id = ? AND id NOT IN (' . implode(',', $keep) . ')', [$productId]);
    $pdo->commit();
    return $note;
}
