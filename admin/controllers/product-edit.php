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
        $extraFiles = array_column(get_product_images((int) $product['id']), 'image');
        db_run('DELETE FROM products WHERE id = ?', [$product['id']]);
        foreach ($extraFiles as $file) {
            delete_public_image($file);
        }
        if ($product['image'] && str_starts_with($product['image'], 'products/')) {
            delete_public_image($product['image']);
        }
        flash('success', '"' . $product['name'] . '" was deleted.');
        redirect(admin_url('products'));
    }

    // Copy a product with its sizes, as a hidden draft, to add a similar cake quickly.
    if (input('action') === 'duplicate' && $product) {
        $copy = array_diff_key($product, array_flip(['id', 'created_at', 'updated_at']));
        $copy['name'] = str_limit($product['name'] . ' (copy)', 160);
        $copy['slug'] = unique_slug('products', slugify($copy['name']));
        $copy['is_active'] = 0;
        $copy['is_featured'] = 0;
        $pdo = db();
        $pdo->beginTransaction();
        $columns = array_keys($copy);
        db_run('INSERT INTO products (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($copy));
        $newId = (int) $pdo->lastInsertId();
        db_run('INSERT INTO product_sizes (product_id, label, servings, price, sort_order, is_active) SELECT ?, label, servings, price, sort_order, is_active FROM product_sizes WHERE product_id = ?', [$newId, $product['id']]);
        db_run('INSERT INTO product_options (product_id, name, price, sort_order, is_active) SELECT ?, name, price, sort_order, is_active FROM product_options WHERE product_id = ?', [$newId, $product['id']]);
        db_run('INSERT INTO product_images (product_id, image, alt, sort_order) SELECT ?, image, alt, sort_order FROM product_images WHERE product_id = ?', [$newId, $product['id']]);
        $pdo->commit();
        flash('success', 'Copy created. Change the name, photo and prices, then switch on "Show in the shop".');
        redirect(admin_url('products/edit', ['id' => $newId]));
    }

    $data = [
        'name'              => str_limit(input('name'), 160),
        'slug'              => slugify(input('slug') ?: input('name')),
        'category_id'       => post_int('category_id'),
        'short_description' => str_limit(input('short_description'), 255),
        'description'       => clean_html(str_limit(input('description'), 20000)),
        'badge'             => str_limit(input('badge'), 40),
        'is_featured'       => input('is_featured') === '1' ? 1 : 0,
        'is_active'         => input('is_active') === '1' ? 1 : 0,
        'allow_message'     => input('allow_message') === '1' ? 1 : 0,
        'is_enquiry'        => input('is_enquiry') === '1' ? 1 : 0,
        'lead_time_hours'   => input('lead_time_hours') !== '' ? max(0, (int) input('lead_time_hours')) : null,
        'sort_order'        => (int) input('sort_order', '0'),
        'meta_title'        => str_limit(input('meta_title'), 160),
        'meta_description'  => str_limit(input('meta_description'), 255),
        'image_alt'         => str_limit(trim(input('image_alt')), 160),
        'focus_keyword'     => str_limit(mb_strtolower(trim(input('focus_keyword'))), 80),
        'sold_out_until'    => match (input('sold_out')) {
            'today'   => date('Y-m-d'),
            'forever' => SOLD_OUT_FOREVER,
            'until'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', input('sold_out_until')) && input('sold_out_until') >= date('Y-m-d') ? input('sold_out_until') : date('Y-m-d'),
            default   => null,
        },
    ];
    $options = [];
    foreach ((array) ($_POST['options'] ?? []) as $row) {
        $name = str_limit(trim((string) ($row['name'] ?? '')), 80);
        $priceText = trim(str_replace(['$', ',', '+'], '', (string) ($row['price'] ?? '')));
        if ($name === '' && $priceText === '') {
            continue;
        }
        if ($name === '' || ($priceText !== '' && (!is_numeric($priceText) || (float) $priceText < 0))) {
            $errors['options'] = 'Every option needs a name, and the extra price must be 0 or more.';
        }
        $options[] = ['id' => (int) ($row['id'] ?? 0), 'name' => $name, 'price' => round((float) $priceText, 2)];
    }
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
    if (!$sizes && !$data['is_enquiry']) {
        $errors['sizes'] = 'Add at least one size with a price, or switch on "Price on request".';
    }
    [$image, $imageError] = $errors ? [null, null] : save_public_image('image', 'products', 900, 900, $data['name']);
    if ($imageError) {
        $errors['image'] = $imageError;
    }

    if (!$errors) {
        $data['slug'] = unique_slug('products', $data['slug'], (int) ($product['id'] ?? 0));
        foreach (['short_description', 'description', 'badge', 'meta_title', 'meta_description', 'image_alt', 'focus_keyword'] as $optional) {
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
        $in = implode(',', array_map('intval', $keep ?: [0]));
        db_run("DELETE FROM product_sizes WHERE product_id = ? AND id NOT IN ({$in})", [$productId]);
        $keepOptions = [0];
        foreach ($options as $order => $option) {
            if ($option['id'] && db_value('SELECT 1 FROM product_options WHERE id = ? AND product_id = ?', [$option['id'], $productId])) {
                db_run('UPDATE product_options SET name = ?, price = ?, sort_order = ?, is_active = 1 WHERE id = ?', [$option['name'], $option['price'], $order, $option['id']]);
                $keepOptions[] = $option['id'];
            } else {
                db_run('INSERT INTO product_options (product_id, name, price, sort_order) VALUES (?, ?, ?, ?)', [$productId, $option['name'], $option['price'], $order]);
                $keepOptions[] = (int) $pdo->lastInsertId();
            }
        }
        db_run('DELETE FROM product_options WHERE product_id = ? AND id NOT IN (' . implode(',', array_map('intval', $keepOptions)) . ')', [$productId]);
        $pdo->commit();

        // A new web address: the old one keeps working with a permanent redirect, so Google rankings carry over.
        if ($product && $product['slug'] !== $data['slug']) {
            add_seo_redirect('cakes/' . $product['slug'], 'cakes/' . $data['slug']);
            flash('info', 'The web address changed. The old address now redirects to the new one.');
        }
        $photoNote = save_product_photos($productId, $data['name']);
        if ($photoNote !== '') {
            flash('error', $photoNote);
        }

        if (($image || input('remove_image') === '1') && $product && $product['image'] && str_starts_with($product['image'], 'products/') && $product['image'] !== ($data['image'] ?? null)) {
            delete_public_image($product['image']);
        }
        flash('success', $product ? 'Product saved.' : 'Product added.');
        redirect(admin_url('products/edit', ['id' => $productId]));
    }
    $product = array_merge($product ?? [], $data, ['image' => $product['image'] ?? null]);
    $sizeRows = $sizes;
    $optionRows = $options;
    $extraPhotos = $product && !empty($product['id']) ? get_product_images((int) $product['id']) : [];
} else {
    $extraPhotos = $product ? get_product_images((int) $product['id']) : [];
    $optionRows = $product ? db_all('SELECT * FROM product_options WHERE product_id = ? ORDER BY sort_order, id', [$product['id']]) : [];
    $sizeRows = $product ? db_all('SELECT * FROM product_sizes WHERE product_id = ? ORDER BY sort_order, price', [$product['id']]) : [['id' => 0, 'label' => 'Midi', 'servings' => 'Serves 8', 'price' => ''], ['id' => 0, 'label' => 'Large', 'servings' => 'Serves 14', 'price' => '']];
}

$product ??= ['name' => '', 'slug' => '', 'category_id' => 0, 'short_description' => '', 'description' => '', 'badge' => '', 'is_featured' => 0, 'is_active' => 1, 'allow_message' => 1, 'is_enquiry' => 0, 'lead_time_hours' => null, 'sold_out_until' => null, 'sort_order' => 0, 'meta_title' => '', 'meta_description' => '', 'image' => null, 'image_alt' => '', 'focus_keyword' => ''];
admin_view('product-edit', compact('product', 'categories', 'sizeRows', 'optionRows', 'extraPhotos', 'errors', 'id') + ['title' => $id ? 'Edit ' . $product['name'] : 'Add product']);
