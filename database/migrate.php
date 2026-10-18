<?php
declare(strict_types=1);

// Brings an existing database up to date by running every file in database/migrations/
// that has not been run yet. Safe to run as often as you like.
//
//   php database/migrate.php
//   (XAMPP on Windows: C:\xampp\php\php.exe database\migrate.php)
//
// No SSH on your hosting? Log in to the admin panel as the owner and use System check.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';

try {
    $ran = run_migrations(static function (string $name): void {
        echo "Running {$name}...\n";
    });
} catch (RuntimeException $e) {
    echo "FAILED\n" . $e->getMessage() . "\n";
    exit(1);
}

echo $ran ? "Database is up to date.\n" : "Nothing to do, the database is already up to date.\n";
