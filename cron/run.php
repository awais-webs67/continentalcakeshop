<?php
declare(strict_types=1);

// Scheduled jobs. Run it every 15 minutes from cPanel > Cron Jobs:
//
//   */15 * * * * /usr/local/bin/php /home/CPANEL_USER/public_html/cron/run.php >/dev/null 2>&1
//
// It sends the morning email before the shop opens (Admin > Settings > Emails).

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';

echo date('Y-m-d H:i') . ' ' . run_scheduled_tasks() . "\n";
