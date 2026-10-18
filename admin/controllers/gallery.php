<?php
require_admin();

if (is_post()) {
    $id = post_int('id');
    $action = input('action');
    $photo = $id ? db_one('SELECT * FROM gallery_images WHERE id = ?', [$id]) : null;

    if ($action === 'upload') {
        $caption = str_limit(trim(input('caption')), 160);
        [$image, $error] = save_public_image('image', 'gallery', 1080, 1440, $caption ?: 'cake');
        if ($error || !$image) {
            flash('error', $error ?? 'Please choose a photo to upload.');
        } else {
            $next = (int) db_value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM gallery_images');
            db_run('INSERT INTO gallery_images (image, caption, sort_order) VALUES (?, ?, ?)', [$image, $caption ?: 'Custom cake by ' . setting('store_short_name'), $next]);
            flash('success', 'Photo added to the gallery.');
        }
    } elseif ($photo && $action === 'save') {
        db_run('UPDATE gallery_images SET caption = ?, sort_order = ? WHERE id = ?', [str_limit(trim(input('caption')), 160) ?: $photo['caption'], (int) input('sort_order'), $photo['id']]);
        flash('success', 'Photo details saved.');
    } elseif ($photo && $action === 'toggle') {
        db_run('UPDATE gallery_images SET is_active = 1 - is_active WHERE id = ?', [$photo['id']]);
        flash('success', $photo['is_active'] ? 'Photo hidden from the website.' : 'Photo shown on the website.');
    } elseif ($photo && $action === 'hero') {
        setting_save('hero_image', $photo['image']);
        flash('success', 'This photo is now the main homepage photo.');
    } elseif ($photo && $action === 'delete') {
        if ($photo['image'] === setting('hero_image')) {
            flash('error', 'This photo is used on the homepage. Choose another homepage photo first.');
        } else {
            db_run('DELETE FROM gallery_images WHERE id = ?', [$photo['id']]);
            delete_public_image($photo['image']);
            flash('success', 'Photo deleted.');
        }
    }
    admin_back('gallery');
}

$result = paginate('SELECT * FROM gallery_images ORDER BY sort_order, id', 'SELECT COUNT(*) FROM gallery_images', [], 24);
$hero = setting('hero_image');
admin_view('gallery', compact('result', 'hero') + ['title' => 'Gallery']);
