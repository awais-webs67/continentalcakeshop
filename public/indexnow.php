<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// The IndexNow key file: Bing opens /{key}.txt to check that our submissions are really ours.

if (($_GET['key'] ?? '') !== setting('indexnow_key')) {
    require __DIR__ . '/404.php';
    exit;
}
header('Content-Type: text/plain; charset=utf-8');
echo setting('indexnow_key');
