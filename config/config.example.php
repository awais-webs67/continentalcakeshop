<?php
// Copy this file to config/config.php and fill in your own values.
// config/config.php is ignored by git so passwords never end up in the repository.

return [
    // Full public URL of the site with no trailing slash. Open the site at exactly this address.
    // Live (cPanel): https://www.continentalcakeshop.com.au
    // XAMPP:         http://localhost/continentalcakeshop
    'app_url'  => 'http://localhost/continentalcakeshop',

    // Show PHP errors on screen. Always false on the live site.
    'debug'    => false,

    // Canberra follows Sydney time, including daylight saving.
    'timezone' => 'Australia/Sydney',

    // Database changes that come with an update are applied by themselves on the first visit.
    // Set to false to run them only from Admin > System check .
    'auto_migrate' => true,

    // MySQL database. On cPanel: MySQL Databases > create a database and a user, then add the user
    // to the database with ALL PRIVILEGES. cPanel adds your account name in front of both,
    // e.g. 'name' => 'cpuser_cakes' and 'user' => 'cpuser_cakeadmin'.
    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'continental_cakes',
        'user'     => 'root',
        'password' => '',
    ],

    // Outgoing email.
    //   'mail' = PHP mail(), works on most cPanel hosting without extra setup
    //   'smtp' = send through an email account (recommended on cPanel: create orders@yourdomain in
    //            Email Accounts first; the host, port and username are under "Connect Devices")
    //   'log'  = do not send; save each email to storage/logs/mail to preview it (good for local testing)
    'mail' => [
        'driver'       => 'mail',
        'from_address' => 'orders@continentalcakeshop.com.au',
        'from_name'    => 'The Continental Cake Shop',
        'smtp' => [
            'host'       => 'mail.continentalcakeshop.com.au',
            'port'       => 465,
            'encryption' => 'ssl', // 'ssl' for port 465, 'tls' for port 587
            'username'   => 'orders@continentalcakeshop.com.au',
            'password'   => '',
        ],
    ],
];
