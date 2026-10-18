<?php
require_admin();

if (is_post()) {
    $id = post_int('id');
    $action = input('action');

    if ($action === 'feature') {
        setting_save('coupons_enabled', input('coupons_enabled') === '1' ? '1' : '0');
        flash('success', input('coupons_enabled') === '1' ? 'Customers can now enter coupon codes at checkout.' : 'Coupon codes are switched off at checkout.');
        redirect(admin_url('coupons'));
    }

    if ($action === 'save') {
        $data = [
            'code'           => normalise_coupon_code(input('code')),
            'description'    => str_limit(trim(input('description')), 160),
            'discount_type'  => input('discount_type') === 'fixed' ? 'fixed' : 'percent',
            'discount_value' => round((float) str_replace(['$', '%', ','], '', input('discount_value')), 2),
            'min_order'      => round(max(0, (float) str_replace(['$', ','], '', input('min_order'))), 2),
            'max_uses'       => input('max_uses') !== '' ? max(1, (int) input('max_uses')) : null,
            'starts_at'      => preg_match('/^\d{4}-\d{2}-\d{2}$/', input('starts_at')) ? input('starts_at') : null,
            'expires_at'     => preg_match('/^\d{4}-\d{2}-\d{2}$/', input('expires_at')) ? input('expires_at') : null,
            'is_active'      => input('is_active') === '1' ? 1 : 0,
        ];
        $error = match (true) {
            strlen($data['code']) < 3                                              => 'The code needs at least 3 letters or numbers.',
            $data['discount_value'] <= 0                                           => 'Enter the discount amount.',
            $data['discount_type'] === 'percent' && $data['discount_value'] > 100  => 'A percentage discount cannot be more than 100%.',
            $data['starts_at'] && $data['expires_at'] && $data['expires_at'] < $data['starts_at'] => 'The expiry date must be after the start date.',
            (bool) db_value('SELECT 1 FROM coupons WHERE code = ? AND id <> ?', [$data['code'], $id]) => 'That code already exists. Choose another code.',
            default                                                                => null,
        };
        if ($error) {
            remember_input(array_map('strval', array_map(static fn ($v) => $v ?? '', $data)) + ['id' => (string) $id]);
            flash('error', $error);
            redirect(admin_url('coupons', $id ? ['edit' => $id] : ['new' => 1]) . '#coupon-form');
        }
        $data['description'] = $data['description'] !== '' ? $data['description'] : null;
        $columns = array_keys($data);
        if ($id) {
            db_run('UPDATE coupons SET ' . implode(', ', array_map(static fn ($c) => "{$c} = ?", $columns)) . ' WHERE id = ?', [...array_values($data), $id]);
            flash('success', 'Coupon ' . $data['code'] . ' saved.');
        } else {
            db_run('INSERT INTO coupons (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
            flash('success', 'Coupon ' . $data['code'] . ' created.');
        }
    } elseif ($action === 'toggle') {
        db_run('UPDATE coupons SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('success', 'Coupon updated.');
    } elseif ($action === 'delete') {
        db_run('DELETE FROM coupons WHERE id = ?', [$id]);
        if (setting('popup_coupon_id') === (string) $id) {
            setting_save('popup_coupon_id', '');
        }
        flash('success', 'Coupon deleted. Past orders keep the code they used.');
    }
    redirect(admin_url('coupons'));
}

$coupons = db_all(
    'SELECT c.*, (SELECT COUNT(*) FROM orders o WHERE o.coupon_code = c.code) AS order_count,
            (SELECT COALESCE(SUM(o.discount), 0) FROM orders o WHERE o.coupon_code = c.code AND o.order_status <> \'cancelled\') AS total_discount
     FROM coupons c ORDER BY c.is_active DESC, c.created_at DESC'
);
$editing = query_int('edit') ? db_one('SELECT * FROM coupons WHERE id = ?', [query_int('edit')]) : null;
admin_view('coupons', compact('coupons', 'editing') + ['title' => 'Coupons']);
