<?php
// Receives a picture from the text editor and returns its public address.
require_admin();
header('Content-Type: application/json; charset=utf-8');
if (!is_post()) {
    http_response_code(405);
    exit(json_encode(['error' => 'Use POST.']));
}
[$path, $error] = save_content_image('image');
if ($error) {
    http_response_code(422);
    exit(json_encode(['error' => $error]));
}
echo json_encode(['url' => url('uploads/' . $path)]);
