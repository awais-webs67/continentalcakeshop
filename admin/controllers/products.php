<?php
require_admin();

if (is_post()) {
    $id = post_int('id');
    $action = input('action');
    if ($action === 'toggle') {
        db_run('UPDATE products SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('success', 'Product visibility updated.');
    }
    if ($action === 'delete') {
        $product = db_one('SELECT * FROM products WHERE id = ?', [$id]);
        if ($product) {
            db_run('DELETE FROM products WHERE id = ?', [$id]);
            delete_public_image($product['image'] && str_starts_with($product['image'], 'products/') ? $product['image'] : null);
            flash('success', '"' . $product['name'] . '" was deleted. Past orders keep their details.');
        }
    }
    admin_back('products');
}

$category = query_int('category');
$q = trim((string) ($_GET['q'] ?? ''));
$where = ['1 = 1'];
$params = [];
if ($category) {
    $where[] = 'p.category_id = ?';
    $params[] = $category;
}
if ($q !== '') {
    $where[] = 'p.name LIKE ?';
    $params[] = '%' . $q . '%';
}
$whereSql = implode(' AND ', $where);
$result = paginate(
    "SELECT p.*, c.name AS category_name, MIN(s.price) AS price_from, COUNT(s.id) AS size_count
     FROM products p JOIN categories c ON c.id = p.category_id
     LEFT JOIN product_sizes s ON s.product_id = p.id AND s.is_active = 1
     WHERE {$whereSql} GROUP BY p.id ORDER BY c.sort_order, p.sort_order, p.name",
    "SELECT COUNT(*) FROM products p WHERE {$whereSql}",
    $params,
    50
);
$categories = db_all('SELECT id, name FROM categories ORDER BY sort_order, name');
admin_view('products', compact('result', 'categories', 'category', 'q') + ['title' => 'Products']);
