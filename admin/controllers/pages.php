<?php
require_admin();

// A new landing page, e.g. "Cupcakes in Canberra". It starts hidden with a short outline to edit.
if (is_post() && input('action') === 'create') {
    $title = str_limit(trim(input('title')), 160);
    $slug = slugify(input('slug') !== '' ? input('slug') : $title);
    if ($title === '') {
        flash('error', 'Give the page a title, e.g. "Cupcakes in Canberra".');
        redirect(admin_url('pages'));
    }
    if (in_array($slug, RESERVED_SLUGS, true)) {
        $slug .= '-canberra';
    }
    $slug = unique_slug('pages', $slug);
    db_run(
        "INSERT INTO pages (slug, type, title, summary, body, is_active, sort_order) VALUES (?, 'landing', ?, ?, ?, 0, ?)",
        [$slug, $title, 'A short line that sums up the page.', '<h2>About these cakes</h2><p>Write a few paragraphs here: what you make, flavours, sizes, how to order and why customers love it.</p>',
         (int) db_value("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM pages WHERE type = 'landing'")]
    );
    flash('success', 'Page created. Write the text, then switch on "Show on the website".');
    redirect(admin_url('pages/edit', ['id' => db()->lastInsertId()]));
}

$pages = db_all('SELECT id, slug, type, title, summary, is_active, updated_at FROM pages ORDER BY type, sort_order, title');
admin_view('pages', compact('pages') + ['title' => 'Pages']);
