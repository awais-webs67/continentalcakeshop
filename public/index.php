<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$heroImg = setting('hero_image') ?: (get_gallery(1)[0]['image'] ?? '');
$page = [
    'title'         => setting('seo_home_title'),
    'description'   => setting('seo_home_description'),
    'canonical'     => url(),
    'preload_image' => $heroImg !== '' ? product_image_url($heroImg) : '',
    'body_class'    => 'page-home',
    'schema'      => [[
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'       => setting('store_name'),
        'url'        => url(),
        'inLanguage' => 'en-AU',
        'publisher'  => ['@id' => url('#bakery')],
    ]],
];
render('header', compact('page'));

// Each section is a template in templates/home/, shown in the order and on/off state set in Admin > Homepage.
foreach (home_sections() as $section => $enabled) {
    if ($enabled) {
        render('home/' . $section);
    }
}

render('footer');
