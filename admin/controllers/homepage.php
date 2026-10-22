<?php
require_admin();

// Plain text fields on the homepage, with their maximum length.
const HOME_TEXT_FIELDS = [
    'announcement_text' => 200,
    'hero_eyebrow' => 120, 'hero_title' => 120, 'hero_subtitle' => 400,
    'hero_btn1_text' => 40, 'hero_btn1_url' => 255, 'hero_btn2_text' => 40, 'hero_btn2_url' => 255,
    'home_categories_title' => 120, 'home_categories_text' => 255,
    'home_featured_title' => 120, 'home_featured_text' => 255,
    'home_custom_title' => 120, 'home_custom_btn_text' => 40,
    'home_text_title' => 120,
    'home_reviews_title' => 120, 'home_reviews_text' => 255,
    'google_rating' => 3, 'google_review_count' => 10, 'google_reviews_url' => 500,
    'home_visit_title' => 120,
];

if (is_post()) {
    $values = [];
    foreach (HOME_TEXT_FIELDS as $key => $max) {
        $values[$key] = str_limit(trim(input($key)), $max);
    }
    $values['hero_show_shop_card'] = input('hero_show_shop_card') === '1' ? '1' : '0';
    $values['home_featured_count'] = (string) min(24, max(1, (int) input('home_featured_count', '8')));
    $values['home_text_style'] = input('home_text_style') === 'plain' ? 'plain' : 'tint';
    $values['home_categories_style'] = input('home_categories_style') === 'circles' ? 'circles' : 'tiles';
    $values['home_custom_text'] = clean_html(str_limit(input('home_custom_text'), 20000));
    $values['home_text_body'] = clean_html(str_limit(input('home_text_body'), 50000));
    $values['home_usp_items'] = json_encode(array_slice(array_map(static fn ($v) => str_limit(trim((string) $v), 80), (array) ($_POST['usp_items'] ?? [])), 0, 4));
    $steps = [];
    foreach ((array) ($_POST['steps'] ?? []) as $step) {
        $steps[] = [str_limit(trim((string) ($step['title'] ?? '')), 60), str_limit(trim((string) ($step['text'] ?? '')), 160)];
    }
    $values['home_custom_steps'] = json_encode(array_slice($steps, 0, 3));

    // Order and on/off state of the sections.
    $sections = [];
    foreach ((array) ($_POST['order'] ?? []) as $key) {
        if (isset(HOME_SECTIONS[$key]) && !isset($sections[$key])) {
            $sections[$key] = ['key' => $key, 'on' => !empty($_POST['on'][$key]) ? 1 : 0];
        }
    }
    foreach (array_keys(HOME_SECTIONS) as $key) {
        $sections[$key] ??= ['key' => $key, 'on' => 0];
    }
    $values['home_sections'] = json_encode(array_values($sections));

    $errors = [];
    if ($values['hero_title'] === '') {
        $errors[] = 'The hero needs a main title.';
    }
    $rating = (float) $values['google_rating'];
    $values['google_rating'] = $rating > 0 ? number_format(min(5, $rating), 1) : '';
    if ($values['google_reviews_url'] !== '' && !safe_web_url($values['google_reviews_url'])) {
        $errors[] = 'Paste the full Google reviews link, starting with https://';
    }
    if (!$errors) {
        [$hero, $heroError] = save_public_image('hero_upload', 'gallery', 1080, 1440, 'hero');
        if ($heroError) {
            $errors[] = $heroError;
        } elseif ($hero) {
            $values['hero_image'] = $hero;
        }
    }
    if ($errors) {
        flash('error', implode(' ', $errors));
        redirect(admin_url('homepage'));
    }
    foreach ($values as $key => $value) {
        setting_save($key, $value);
    }
    flash('success', 'Homepage saved.');
    redirect(admin_url('homepage') . (input('_open') !== '' ? '#' . preg_replace('/[^a-z]/', '', input('_open')) : ''));
}

admin_view('homepage', ['title' => 'Homepage', 'sections' => home_sections()]);
