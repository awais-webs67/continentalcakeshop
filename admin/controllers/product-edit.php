<?php
require_admin();
$id = query_int('id') ?: post_int('id');
$product = $id ? db_one('SELECT * FROM products WHERE id = ?', [$id]) : null;
if ($id && !$product) {
    flash('error', 'That product was not found.');
    redirect(admin_url('products'));
}
$categories = db_all('SELECT id, name FROM categories ORDER BY sort_order, name');
$errors = [];

if (is_post()) {
    if (input('action') === 'delete' && $product) {
        db_run('DELETE FROM products WHERE id = ?', [$product['id']]);
        if ($product['image'] && str_starts_with($product['image'], 'products/')) {
            delete_public_image($product['image']);
        }
        flash('success', '"' . $product['name'] . '" was deleted.');
        redirect(admin_url('products'));
    }

    $data = [
        'name'              => str_limit(input('name'), 160),
        'slug'              => slugify(input('slug') ?: input('name')),
        'category_id'       => post_int('category_id'),
        'short_description' => str_limit(input('short_description'), 255),
        'description'       => str_limit(input('description'), 5000),
        'badge'             => str_limit(input('badge'), 40),
        'is_featured'       => input('is_featured') === '1' ? 1 : 0,
        'is_active'         => input('is_active') === '1' ? 1 : 0,
        'allow_message'     => input('allow_message') === '1' ? 1 : 0,
        'lead_time_hours'   => input('lead_time_hours') !== '' ? max(0, (int) input('lead_time_hours')) : null,
        'sort_order'        => (int) input('sort_order', '0'),
        'meta_title'        => str_limit(input('meta_title'), 160),
        'meta_description'  => str_limit(input('meta_description'), 255),
    ];
    $sizes = [];
    foreach ((array) ($_POST['sizes'] ?? []) as $row) {
        $label = str_limit(trim((string) ($row['label'] ?? '')), 60);
        $price = (float) str_replace(['$', ','], '', (string) ($row['price'] ?? ''));
        if ($label === '' && $price <= 0) {
            continue;
        }
        if ($label === '' || $price <= 0) {
            $errors['sizes'] = 'Every size needs a name and a price above $0.';
        }
        $sizes[] = ['id' => (int) ($row['id'] ?? 0), 'label' => $label, 'servings' => str_limit(trim((string) ($row['servings'] ?? '')), 60), 'price' => round($price, 2)];
    }

    if ($data['name'] === '') {
        $errors['name'] = 'Please enter the product name.';
    }
    if (!in_array($data['category_id'], array_map('intval', array_column($categories, 'id')), true)) {
        $errors['category_id'] = 'Please choose a category.';
    }
    if (!$sizes) {
        $errors['sizes'] = 'Add at least one size with a price.';
    }
    [$image, $imageError] = $errors ? [null, null] : save_public_image('image', 'products', 900, 900, $data['name']);
    if ($imageError) {
        $errors['image'] = $imageError;
    }

    if (!$errors) {
        $data['slug'] = unique_slug('products', $data['slug'], (int) ($product['id'] ?? 0));
        foreach (['short_description', 'description', 'badge', 'meta_title', 'meta_description'] as $optional) {
            $data[$optional] = $data[$optional] !== '' ? $data[$optional] : null;
        }
        if ($image) {
            $data['image'] = $image;
        } elseif (input('remove_image') === '1') {
            $data['image'] = null;
        }

        $pdo = db();
        $pdo->beginTransaction();
        $columns = array_keys($data);
        if ($product) {
            db_run('UPDATE products SET ' . implode(', ', array_map(static fn ($c) => "{$c} = ?", $columns)) . ' WHERE id = ?', [...array_values($data), $product['id']]);
            $productId = (int) $product['id'];
        } else {
            db_run('INSERT INTO products (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
            $productId = (int) $pdo->lastInsertId();
        }
        $keep = [];
        foreach ($sizes as $order => $size) {
            if ($size['id'] && db_value('SELECT 1 FROM product_sizes WHERE id = ? AND product_id = ?', [$size['id'], $productId])) {
                db_run('UPDATE product_sizes SET label = ?, servings = ?, price = ?, sort_order = ?, is_active = 1 WHERE id = ?', [$size['label'], $size['servings'] ?: null, $size['price'], $order, $size['id']]);
                $keep[] = $size['id'];
            } else {
                db_run('INSERT INTO product_sizes (product_id, label, servings, price, sort_order) VALUES (?, ?, ?, ?, ?)', [$productId, $size['label'], $size['servings'] ?: null, $size['price'], $order]);
                $keep[] = (int) $pdo->lastInsertId();
            }
        }
        $in = implode(',', array_map('intval', $keep));
        db_run("DELETE FROM product_sizes WHERE product_id = ? AND id NOT IN ({$in})", [$productId]);
        $pdo->commit();

        if (($image || input('remove_image') === '1') && $product && $product['image'] && str_starts_with($product['image'], 'products/') && $product['image'] !== ($data['image'] ?? null)) {
            delete_public_image($product['image']);
        }
        flash('success', $product ? 'Product saved.' : 'Product added.');
        redirect(admin_url('products/edit', ['id' => $productId]));
    }
    $product = array_merge($product ?? [], $data, ['image' => $product['image'] ?? null]);
    $sizeRows = $sizes;
} else {
    $sizeRows = $product ? db_all('SELECT * FROM product_sizes WHERE product_id = ? ORDER BY sort_order, price', [$product['id']]) : [['id' => 0, 'label' => 'Full', 'servings' => '', 'price' => '']];
}

$product ??= ['name' => '', 'slug' => '', 'category_id' => 0, 'short_description' => '', 'description' => '', 'badge' => '', 'is_featured' => 0, 'is_active' => 1, 'allow_message' => 1, 'lead_time_hours' => null, 'sort_order' => 0, 'meta_title' => '', 'meta_description' => '', 'image' => null];
admin_view('product-edit', compact('product', 'categories', 'sizeRows', 'errors', 'id') + ['title' => $id ? 'Edit ' . $product['name'] : 'Add product']);
