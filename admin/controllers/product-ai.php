<?php
// AI SEO writer for cakes (answers the product editor and the "many cakes" list with JSON).
//  - write:    writes the SEO fields from the product form as it is now; nothing is saved
//  - field:    writes one field (the AI button beside each field); nothing is saved
//  - keywords: real Google searches for the cake (no AI needed)
//  - rewrite:  writes and saves them for a saved cake; the old text goes to its earlier versions
require_admin();

if (!is_post()) {
    json_out(['ok' => false, 'error' => 'Use the buttons in the product editor.'], 405);
}
@set_time_limit(180);

try {
    if (input('action') === 'rewrite') {
        json_out(['ok' => true] + ai_rewrite_product(post_int('id'), (string) current_admin()['name']));
    }
    if (input('action') === 'keywords') {
        $category = (string) db_value('SELECT name FROM categories WHERE id = ?', [post_int('category_id')]);
        json_out(['ok' => true, 'ideas' => keyword_ideas(str_limit(input('name'), 160), $category)]);
    }
    $product = [
        'id'                => post_int('id'),
        'name'              => str_limit(input('name'), 160),
        'slug'              => slugify(input('slug') ?: input('name')),
        'category_id'       => post_int('category_id'),
        'short_description' => str_limit(input('short_description'), 255),
        'description'       => clean_html(str_limit(input('description'), 20000)),
        'focus_keyword'     => str_limit(input('focus_keyword'), 80),
        'meta_title'        => str_limit(input('meta_title'), 160),
        'meta_description'  => str_limit(input('meta_description'), 255),
        'search_words'      => str_limit(input('search_words'), 500),
        'image_alt'         => str_limit(input('image_alt'), 160),
        'is_enquiry'        => input('is_enquiry') === '1',
    ];
    $sizes = array_values(array_filter(array_map(static fn ($r) => ['label' => trim((string) ($r['label'] ?? '')), 'servings' => trim((string) ($r['servings'] ?? ''))], (array) ($_POST['sizes'] ?? [])), static fn ($s) => $s['label'] !== ''));
    $options = array_values(array_filter(array_map(static fn ($r) => str_limit(trim((string) ($r['name'] ?? '')), 80), (array) ($_POST['options'] ?? []))));
    $only = input('action') === 'field' ? input('field') : null;
    $fields = ai_write_product($product, $sizes, $options, str_limit(input('ai_notes'), 1500), $only);
    json_out(['ok' => true, 'fields' => array_intersect_key($fields, array_flip(AI_PRODUCT_FIELDS))] + array_intersect_key($fields, array_flip(['service', 'tokens', 'keywords', 'failed'])));
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 502);
}
