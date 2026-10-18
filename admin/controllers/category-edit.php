<?php
require_admin();
$id = query_int('id') ?: post_int('id');
$category = $id ? db_one('SELECT * FROM categories WHERE id = ?', [$id]) : null;
if ($id && !$category) {
    flash('error', 'That category was not found.');
    redirect(admin_url('categories'));
}
$productCount = $category ? (int) db_value('SELECT COUNT(*) FROM products WHERE category_id = ?', [$category['id']]) : 0;
$errors = [];

if (is_post()) {
    if (input('action') === 'delete' && $category) {
        if ($productCount > 0) {
            flash('error', 'Move or delete the ' . $productCount . ' products in this category first.');
            redirect(admin_url('categories/edit', ['id' => $category['id']]));
        }
        db_run('DELETE FROM categories WHERE id = ?', [$category['id']]);
        if ($category['image'] && str_starts_with($category['image'], 'categories/')) {
            delete_public_image($category['image']);
        }
        flash('success', '"' . $category['name'] . '" was deleted.');
        redirect(admin_url('categories'));
    }

    $data = [
        'name'             => str_limit(input('name'), 120),
        'slug'             => slugify(input('slug') ?: input('name')),
        'description'      => clean_html(str_limit(input('description'), 10000)),
        'is_active'        => input('is_active') === '1' ? 1 : 0,
        'sort_order'       => (int) input('sort_order', '0'),
        'meta_title'       => str_limit(input('meta_title'), 160),
        'meta_description' => str_limit(input('meta_description'), 255),
    ];
    if ($data['name'] === '') {
        $errors['name'] = 'Please enter the category name.';
    }
    [$image, $imageError] = $errors ? [null, null] : save_public_image('image', 'categories', 1000, 800, $data['name']);
    if ($imageError) {
        $errors['image'] = $imageError;
    }

    if (!$errors) {
        $data['slug'] = unique_slug('categories', $data['slug'], (int) ($category['id'] ?? 0));
        foreach (['description', 'meta_title', 'meta_description'] as $optional) {
            $data[$optional] = $data[$optional] !== '' ? $data[$optional] : null;
        }
        if ($image) {
            $data['image'] = $image;
        } elseif (input('remove_image') === '1') {
            $data['image'] = null;
        }
        $columns = array_keys($data);
        if ($category) {
            db_run('UPDATE categories SET ' . implode(', ', array_map(static fn ($c) => "{$c} = ?", $columns)) . ' WHERE id = ?', [...array_values($data), $category['id']]);
            $categoryId = (int) $category['id'];
            if (array_key_exists('image', $data) && $category['image'] && str_starts_with($category['image'], 'categories/') && $category['image'] !== $data['image']) {
                delete_public_image($category['image']);
            }
        } else {
            db_run('INSERT INTO categories (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
            $categoryId = (int) db()->lastInsertId();
        }
        flash('success', $category ? 'Category saved.' : 'Category added.');
        redirect(admin_url('categories/edit', ['id' => $categoryId]));
    }
    $category = array_merge($category ?? [], $data, ['image' => $category['image'] ?? null]);
}

$category ??= ['name' => '', 'slug' => '', 'description' => '', 'image' => null, 'is_active' => 1, 'sort_order' => 0, 'meta_title' => '', 'meta_description' => ''];
admin_view('category-edit', compact('category', 'errors', 'id', 'productCount') + ['title' => $id ? 'Edit ' . $category['name'] : 'Add category']);
