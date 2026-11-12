<?php
declare(strict_types=1);

// Applies the database changes that came with new code, straight after an update
// (.cpanel.yml "Deploy HEAD Commit" and cron/deploy.sh run it). Without it the first visit
// to the site does the same. Command line only.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';

$before = pending_migrations();
auto_migrate();
$left = pending_migrations();
echo date('Y-m-d H:i') . ' database: ' . ($before ? 'applied ' . implode(', ', array_diff($before, $left)) : 'up to date')
    . ($left ? ', still waiting: ' . implode(', ', $left) : '') . "\n";
exit($left ? 1 : 0);
