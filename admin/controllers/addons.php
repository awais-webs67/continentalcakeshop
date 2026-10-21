<?php
// Extras customers can add to any order: candles, cake knife, topper, gift card.
require_admin();

if (is_post()) {
    $id = post_int('id');
    $action = input('action');

    if ($action === 'feature') {
        setting_save('addons_enabled', input('addons_enabled') === '1' ? '1' : '0');
        setting_save('addons_title', str_limit(trim(input('addons_title')), 80) ?: 'Add something extra');
        flash('success', input('addons_enabled') === '1' ? 'Extras are shown in the cart.' : 'Extras are switched off.');
        redirect(admin_url('addons'));
    }
    if ($action === 'save') {
        $data = [
            'name'        => str_limit(trim(input('name')), 120),
            'description' => str_limit(trim(input('description')), 255),
            'price'       => round((float) str_replace(['$', ','], '', input('price')), 2),
            'max_qty'     => min(99, max(1, (int) input('max_qty', '10'))),
            'sort_order'  => (int) input('sort_order', '0'),
            'is_active'   => input('is_active') === '1' ? 1 : 0,
        ];
        if ($data['name'] === '' || $data['price'] <= 0) {
            flash('error', 'Please enter a name and a price above $0.');
            redirect(admin_url('addons', $id ? ['edit' => $id] : []) . '#addon-form');
        }
        $data['description'] = $data['description'] !== '' ? $data['description'] : null;
        if ($id) {
            db_run('UPDATE addons SET name = ?, description = ?, price = ?, max_qty = ?, sort_order = ?, is_active = ? WHERE id = ?', [...array_values($data), $id]);
            flash('success', $data['name'] . ' saved.');
        } else {
            db_run('INSERT INTO addons (name, description, price, max_qty, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)', array_values($data));
            flash('success', $data['name'] . ' added.');
        }
    } elseif ($action === 'toggle') {
        db_run('UPDATE addons SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('success', 'Extra updated.');
    } elseif ($action === 'delete') {
        db_run('DELETE FROM addons WHERE id = ?', [$id]);
        flash('success', 'Extra deleted. Past orders keep it.');
    }
    redirect(admin_url('addons'));
}

$addons = db_all(
    'SELECT a.*, (SELECT COALESCE(SUM(i.quantity), 0) FROM order_items i JOIN orders o ON o.id = i.order_id
                  WHERE i.addon_id = a.id AND o.order_status <> \'cancelled\') AS sold
     FROM addons a ORDER BY a.sort_order, a.name'
);
$editing = query_int('edit') ? db_one('SELECT * FROM addons WHERE id = ?', [query_int('edit')]) : null;
admin_view('addons', compact('addons', 'editing') + ['title' => 'Extras']);
