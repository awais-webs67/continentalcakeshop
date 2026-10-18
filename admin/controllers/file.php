<?php
// Streams a private upload (payment screenshot or custom cake photo) to logged-in staff only.
require_admin();
$root = realpath(STORAGE_PATH . '/uploads');
$path = realpath(STORAGE_PATH . '/uploads/' . ltrim((string) ($_GET['path'] ?? ''), '/'));
if (!$root || !$path || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) {
    http_response_code(404);
    exit('File not found');
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . basename($path) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
