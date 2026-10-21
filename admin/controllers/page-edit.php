<?php
require_admin();
$id = query_int('id') ?: post_int('id');
$page = $id ? db_one('SELECT * FROM pages WHERE id = ?', [$id]) : null;
if (!$page) {
    flash('error', 'That page was not found.');
    redirect(admin_url('pages'));
}
$errors = [];
$landing = ($page['type'] ?? 'policy') === 'landing';

if (is_post() && $landing && input('action') === 'delete') {
    db_run('DELETE FROM pages WHERE id = ?', [$page['id']]);
    flash('success', '"' . $page['title'] . '" was deleted. Its address now shows the "not found" page.');
    redirect(admin_url('pages'));
}

if (is_post()) {
    $data = [
        'title'            => str_limit(trim(input('title')), 160),
        'summary'          => str_limit(trim(input('summary')), 255),
        'body'             => clean_html(input('body')),
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
    $extra = [];
    if ($landing) {
        $slug = slugify(input('slug') !== '' ? input('slug') : $data['title']);
        if (in_array($slug, RESERVED_SLUGS, true)) {
            $errors['slug'] = 'That address is used by another part of the website. Try adding "-canberra".';
        } elseif (db_value('SELECT 1 FROM pages WHERE slug = ? AND id <> ?', [$slug, $page['id']])) {
            $errors['slug'] = 'Another page already uses that address.';
        }
        $extra = [
            'slug'         => $slug,
            'occasion'     => in_array(input('occasion'), CAKE_OCCASIONS, true) ? input('occasion') : null,
            'show_gallery' => input('show_gallery') === '1' ? 1 : 0,
            'sort_order'   => (int) input('sort_order', '0'),
        ];
    }
    if (!$errors) {
        foreach (['summary', 'meta_title', 'meta_description'] as $optional) {
            $data[$optional] = $data[$optional] !== '' ? $data[$optional] : null;
        }
        db_run('UPDATE pages SET title = ?, summary = ?, body = ?, is_active = ?, meta_title = ?, meta_description = ? WHERE id = ?', [...array_values($data), $page['id']]);
        if ($extra) {
            db_run('UPDATE pages SET slug = ?, occasion = ?, show_gallery = ?, sort_order = ? WHERE id = ?', [...array_values($extra), $page['id']]);
            if ($extra['slug'] !== $page['slug']) {
                add_seo_redirect($page['slug'], $extra['slug']);
                flash('info', 'The web address changed. The old address now redirects to the new one.');
            }
        }
        flash('success', 'Page saved.');
        redirect(admin_url('pages/edit', ['id' => $page['id']]));
    }
    $page = array_merge($page, $data, $extra);
}

admin_view('page-edit', compact('page', 'errors') + ['title' => 'Edit ' . $page['title']]);
