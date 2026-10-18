<?php
declare(strict_types=1);

// Customer uploads (payment receipts, custom cake references) are stored outside the
// public folder in storage/uploads. Only the admin panel will be able to view them.

const UPLOAD_MAX_BYTES = 5 * 1024 * 1024;

const UPLOAD_IMAGE_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/heic' => 'heic',
];

const UPLOAD_RECEIPT_TYPES = UPLOAD_IMAGE_TYPES + ['application/pdf' => 'pdf'];

function has_upload(string $field): bool
{
    return isset($_FILES[$field]) && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

/**
 * Validates and stores an uploaded file.
 * Returns [path relative to storage/uploads, null] on success or [null, error message].
 *
 * @return array{0:?string,1:?string}
 */
function store_private_upload(string $field, string $folder, array $allowedTypes): array
{
    $file = $_FILES[$field] ?? null;
    if (!$file || !is_array($file) || is_array($file['error'])) {
        return [null, 'Please choose a file to upload.'];
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > UPLOAD_MAX_BYTES) {
        return [null, 'The file is too large. Please upload a file under 5 MB.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return [null, 'The upload did not finish. Please try again.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    if (!isset($allowedTypes[$mime])) {
        $names = strtoupper(implode(', ', array_unique(array_values($allowedTypes))));
        return [null, 'That file type is not supported. Please upload ' . $names . '.'];
    }

    $folder = trim($folder, '/');
    $dir = STORAGE_PATH . '/uploads/' . $folder . '/' . date('Y/m');
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return [null, 'We could not save your file right now. Please try again.'];
    }

    $name = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        return [null, 'We could not save your file right now. Please try again.'];
    }
    return [$folder . '/' . date('Y/m') . '/' . $name, null];
}
