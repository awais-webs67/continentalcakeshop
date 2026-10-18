<?php
// Copy this file to config/config.php and fill in your own values.
// config/config.php is ignored by git so passwords never end up in the repository.

return [
    // Full public URL of the site with no trailing slash, e.g. https://www.continentalcakeshop.com.au
    'app_url'  => 'http://localhost:8000',
    'debug'    => false,
    'timezone' => 'Australia/Sydney', // Canberra follows Sydney time, including daylight saving

    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'continental_cakes',
        'user'     => 'cakeshop',
        'password' => 'change-me',
    ],

    // Address used as the sender of order emails.
    'mail_from' => 'orders@continentalcakeshop.com.au',
];
