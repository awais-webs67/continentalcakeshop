<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

// Receives a payment screenshot or custom cake photo before the form is sent, so the
// customer sees the upload progress. Returns a token that the form then submits.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$reply = static function (int $status, array $data): never {
    http_response_code($status);
    echo json_encode($data);
    exit;
};

if (!is_post()) {
    $reply(405, ['error' => 'Please send the file with POST.']);
}
// A file larger than post_max_size arrives with an empty body, including the token.
if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $reply(413, ['error' => 'The file is too large. Please upload a file under 10 MB.']);
}
if (!hash_equals(csrf_token(), (string) ($_POST['_token'] ?? ''))) {
    $reply(419, ['error' => 'Your session expired. Please refresh the page and try again.']);
}

// 20 uploads an hour from one connection is plenty for a real customer and stops disk filling.
if (rate_hit('upload', client_ip(), 20, 3600)) {
    $reply(429, ['error' => 'Too many uploads. Please wait a while and try again, or email the file to us.']);
}

[$token, $error] = store_async_upload(input('purpose'));
if ($error) {
    $reply(422, ['error' => $error]);
}
$reply(200, ['token' => $token]);
