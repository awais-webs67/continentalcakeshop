<?php
require_admin();
$id = query_int('id') ?: post_int('id');
$page = $id ? db_one('SELECT * FROM pages WHERE id = ?', [$id]) : null;
if (!$page) {
    flash('error', 'That page was not found.');
    redirect(admin_url('pages'));
}
$errors = [];

if (is_post()) {
    $data = [
        'title'            => str_limit(trim(input('title')), 160),
        'summary'          => str_limit(trim(input('summary')), 255),
        'body'             => clean_page_html(input('body')),
        'is_active'        => input('is_active') === '1' ? 1 : 0,
        'meta_title'       => str_limit(trim(input('meta_title')), 160),
        'meta_description' => str_limit(trim(input('meta_description')), 255),
    ];
    if ($data['title'] === '') {
        $errors['title'] = 'Please enter a page title.';
    }
    if (trim(strip_tags($data['body'])) === '') {
        $errors['body'] = 'The page needs some content.';
    }
    if (!$errors) {
        foreach (['summary', 'meta_title', 'meta_description'] as $optional) {
            $data[$optional] = $data[$optional] !== '' ? $data[$optional] : null;
        }
        db_run('UPDATE pages SET title = ?, summary = ?, body = ?, is_active = ?, meta_title = ?, meta_description = ? WHERE id = ?', [...array_values($data), $page['id']]);
        flash('success', 'Page saved.');
        redirect(admin_url('pages/edit', ['id' => $page['id']]));
    }
    $page = array_merge($page, $data);
}

admin_view('page-edit', compact('page', 'errors') + ['title' => 'Edit ' . $page['title']]);
