<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$page = [
    'title'       => setting('seo_home_title'),
    'description' => setting('seo_home_description'),
    'canonical'   => url(),
    'body_class'  => 'page-home',
    'schema'      => [[
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => setting('store_name'),
        'url'      => url(),
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
