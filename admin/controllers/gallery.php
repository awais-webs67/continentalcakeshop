<?php
require_admin();

if (is_post()) {
    $id = post_int('id');
    $action = input('action');
    $photo = $id ? db_one('SELECT * FROM gallery_images WHERE id = ?', [$id]) : null;

    if ($action === 'upload') {
        // Photos keep their own shape (height 0) so the gallery page looks like Pinterest.
        // The page's script sends one photo per request, so phone photos never pass the server's size limit.
        $occasion = in_array(input('occasion'), CAKE_OCCASIONS, true) ? input('occasion') : '';
        $caption = str_limit(trim(input('caption')), 160) ?: gallery_default_caption($occasion);
        $fileName = implode(' ', array_slice(preg_split('/\s+/', $caption), 0, 6)); // e.g. birthday-cake-by-the-continental-cake
        [$saved, $errors] = save_public_images('images', 'gallery', 1080, 0, $fileName);
        [$single, $singleError] = save_public_image('image', 'gallery', 1080, 0, $fileName);
        if ($single) {
            $saved[] = $single;
        } elseif ($singleError) {
            $errors[] = $singleError;
        }
        foreach ($saved as $image) {
            $next = (int) db_value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM gallery_images');
            db_run('INSERT INTO gallery_images (image, caption, sort_order) VALUES (?, ?, ?)', [$image, $caption, $next]);
        }
        if (is_fetch_request()) {
            json_out(['ok' => (bool) $saved, 'saved' => count($saved), 'error' => $errors ? implode(' ', $errors) : null], $saved || !$errors ? 200 : 422);
        }
        if ($saved) {
            flash('success', count($saved) === 1 ? 'Photo added to the gallery.' : count($saved) . ' photos added to the gallery.');
        }
        if ($errors) {
            flash('error', implode(' ', array_slice($errors, 0, 3)));
        } elseif (!$saved) {
            flash('error', 'Please choose a photo to upload.');
        }
    } elseif ($action === 'settings') {
        setting_save('custom_show_gallery', input('custom_show_gallery') === '1' ? '1' : '0');
        setting_save('custom_gallery_count', (string) min(24, max(2, (int) input('custom_gallery_count', '8'))));
        setting_save('gallery_title', str_limit(trim(input('gallery_title')), 80));
        setting_save('gallery_intro', str_limit(trim(input('gallery_intro')), 400));
        flash('success', 'Gallery settings saved.');
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
