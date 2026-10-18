<?php
require_admin();
$pages = db_all('SELECT id, slug, title, summary, is_active, updated_at FROM pages ORDER BY title');
admin_view('pages', compact('pages') + ['title' => 'Policy pages']);
