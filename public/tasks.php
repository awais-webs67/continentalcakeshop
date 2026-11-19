<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// The outside timer: a free service such as cron-job.org opens this secret address every minute
// (Admin > System check shows it). It reads the shops' inboxes when they are due, so customers'
// emails reach Messages within about a minute even when nobody has the panel open. If the cPanel
// cron job has stopped, it also runs the other scheduled jobs (at most every 15 minutes).

$key = (string) setting('tasks_key');
if ($key === '' || !hash_equals($key, input('key'))) {
    not_found();
}
@set_time_limit(120);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
$read = inbox_sync();
$cronLate = strtotime((string) setting('cron_last_run')) < time() - 900;
if ($cronLate) {
    run_scheduled_tasks();
}
echo 'OK' . ($read ? ', inboxes read: ' . $read : '') . ($cronLate ? ', scheduled jobs run' : '') . "\n";
