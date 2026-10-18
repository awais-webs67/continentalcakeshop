<?php
require_admin();

if (is_post()) {
    $id = post_int('id');
    $action = input('action');
    if ($action === 'toggle') {
        db_run('UPDATE categories SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('success', 'Category visibility updated.');
    }
    if ($action === 'sort') {
        foreach ((array) ($_POST['sort'] ?? []) as $categoryId => $order) {
            db_run('UPDATE categories SET sort_order = ? WHERE id = ?', [(int) $order, (int) $categoryId]);
        }
        flash('success', 'Order saved.');
    }
    admin_back('categories');
}

$categories = db_all(
    'SELECT c.*, COUNT(p.id) AS product_count, SUM(p.is_active) AS visible_count
     FROM categories c LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id ORDER BY c.sort_order, c.name'
);
admin_view('categories', compact('categories') + ['title' => 'Categories']);
