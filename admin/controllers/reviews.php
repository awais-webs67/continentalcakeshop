<?php
require_admin();

if (is_post()) {
    $id = post_int('id');
    $action = input('action');
    if ($action === 'save') {
        $data = [
            'author'     => str_limit(trim(input('author')), 120),
            'source'     => str_limit(trim(input('source', 'Google')), 40) ?: 'Google',
            'body'       => str_limit(trim(input('body')), 2000),
            'is_active'  => input('is_active') === '1' ? 1 : 0,
            'sort_order' => (int) input('sort_order', '0'),
        ];
        if ($data['author'] === '' || $data['body'] === '') {
            flash('error', 'Please enter the name and the review text.');
        } elseif ($id) {
            db_run('UPDATE reviews SET author = ?, source = ?, body = ?, is_active = ?, sort_order = ? WHERE id = ?', [...array_values($data), $id]);
            flash('success', 'Review saved.');
        } else {
            db_run('INSERT INTO reviews (author, source, body, is_active, sort_order) VALUES (?, ?, ?, ?, ?)', array_values($data));
            flash('success', 'Review added.');
        }
    } elseif ($action === 'toggle') {
        db_run('UPDATE reviews SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('success', 'Review visibility updated.');
    } elseif ($action === 'delete') {
        db_run('DELETE FROM reviews WHERE id = ?', [$id]);
        flash('success', 'Review deleted.');
    }
    redirect(admin_url('reviews'));
}

$reviews = db_all('SELECT * FROM reviews ORDER BY sort_order, id');
$editing = query_int('edit') ? db_one('SELECT * FROM reviews WHERE id = ?', [query_int('edit')]) : null;
admin_view('reviews', compact('reviews', 'editing') + ['title' => 'Reviews']);
