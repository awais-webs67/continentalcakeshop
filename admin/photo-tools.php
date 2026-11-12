<?php
declare(strict_types=1);

// Admin > Gallery > Photo tools: make the website's photos smaller, and download photos as a ZIP.
//
// Only photos added in the admin panel are made smaller (their names end in a random code, e.g.
// "birthday-cake-3fa21c8b.webp"). The starter photos that come with the code are left alone, so
// the server's copy of the code never differs from GitHub. A photo keeps its name and place, so
// nothing on the website changes address, and it is only replaced when that saves at least 15%:
// running it again does nothing to photos that are already small.

/** Folders in public/uploads and the widest a photo there needs to be (what the website shows). */
const PHOTO_FOLDERS = ['products' => 900, 'gallery' => 1080, 'categories' => 1000, 'popup' => 800, 'branches' => 1200, 'content' => 1600];
const PHOTO_QUALITY = 80;        // WebP quality: no visible difference on a phone or laptop screen
const PHOTO_MIN_BYTES = 60_000;  // smaller files are left as they are
const PHOTO_MIN_SAVING = 0.15;   // replace only when at least 15% smaller

/** Photos added in the admin panel, as paths in public/uploads, e.g. gallery/birthday-cake-3fa21c8b.webp */
function panel_photos(): array
{
    $list = [];
    foreach (array_keys(PHOTO_FOLDERS) as $folder) {
        foreach (glob(BASE_PATH . '/public/uploads/' . $folder . '/*.webp') ?: [] as $file) {
            if (preg_match('/-[0-9a-f]{6,8}\.webp$/', $file) && is_file($file)) {
                $list[] = $folder . '/' . basename($file);
            }
        }
    }
    sort($list);
    return $list;
}

/** Number of photos and their total size in bytes. */
function panel_photos_size(array $photos): int
{
    $bytes = 0;
    foreach ($photos as $photo) {
        $bytes += (int) @filesize(BASE_PATH . '/public/uploads/' . $photo);
    }
    return $bytes;
}

/** Makes one photo smaller if that is worth it. Returns [bytes before, bytes after]. */
function optimise_photo(string $photo): array
{
    $path = BASE_PATH . '/public/uploads/' . $photo;
    $before = (int) @filesize($path);
    $folder = strtok($photo, '/');
    if ($before < PHOTO_MIN_BYTES || str_contains($photo, '..') || !isset(PHOTO_FOLDERS[$folder])) {
        return [$before, $before];
    }
    image_too_big($path); // asks for enough memory
    $source = @imagecreatefromwebp($path);
    if (!$source) {
        return [$before, $before];
    }
    $w = imagesx($source);
    $h = imagesy($source);
    $tw = min($w, PHOTO_FOLDERS[$folder]);
    $th = (int) round($h * $tw / $w);
    $canvas = $source;
    if ($tw < $w) {
        $canvas = imagecreatetruecolor($tw, $th);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $tw, $th, $w, $h);
    } else {
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
    }
    $tmp = $path . '.tmp';
    $ok = imagewebp($canvas, $tmp, PHOTO_QUALITY);
    clearstatcache(true, $tmp);
    $after = $ok ? (int) @filesize($tmp) : 0;
    if ($ok && $after > 0 && $after <= $before * (1 - PHOTO_MIN_SAVING) && @rename($tmp, $path)) {
        @chmod($path, 0644);
        return [$before, $after];
    }
    @unlink($tmp);
    return [$before, $before];
}

/**
 * Works through the photos from $offset for about $seconds, so a big site never hits the
 * server's time limit; the page asks again with the next offset until all are done.
 */
function optimise_photos(int $offset, float $seconds = 12.0): array
{
    $photos = panel_photos();
    $start = microtime(true);
    $saved = 0;
    $changed = 0;
    $i = max(0, $offset);
    for (; $i < count($photos) && microtime(true) - $start < $seconds; $i++) {
        [$before, $after] = optimise_photo($photos[$i]);
        if ($after < $before) {
            $saved += $before - $after;
            $changed++;
        }
    }
    return ['next' => $i, 'total' => count($photos), 'saved' => $saved, 'changed' => $changed, 'done' => $i >= count($photos)];
}

/**
 * Sends a ZIP of photos: 'gallery' (the gallery's photos, in their order) or 'all' (every photo
 * in public/uploads). Photos are stored as they are (they are already compressed).
 */
function send_photos_zip(string $which): never
{
    if (!class_exists('ZipArchive')) {
        flash('error', 'This hosting cannot make ZIP files (the PHP zip extension is off). Ask the hosting to switch it on.');
        redirect(admin_url('gallery'));
    }
    $root = BASE_PATH . '/public/uploads/';
    $files = [];
    if ($which === 'gallery') {
        foreach (db_all('SELECT image FROM gallery_images ORDER BY sort_order, id') as $n => $row) {
            if (!str_contains($row['image'], '..') && is_file($root . $row['image'])) {
                $files[sprintf('%03d-', $n + 1) . basename($row['image'])] = $root . $row['image'];
            }
        }
    } else {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && preg_match('/\.(webp|jpe?g|png|gif)$/i', $file->getFilename())) {
                $files[substr($file->getPathname(), strlen($root))] = $file->getPathname();
            }
        }
        ksort($files);
    }
    if (!$files) {
        flash('error', 'There are no photos to download yet.');
        redirect(admin_url('gallery'));
    }
    @set_time_limit(300);
    $tmp = tempnam(sys_get_temp_dir(), 'photos');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        flash('error', 'The ZIP file could not be made.');
        redirect(admin_url('gallery'));
    }
    foreach ($files as $name => $path) {
        $zip->addFile($path, $name);
        $zip->setCompressionName($name, ZipArchive::CM_STORE);
    }
    $zip->close();
    $download = slugify(setting('store_short_name')) . '-' . ($which === 'gallery' ? 'gallery' : 'all-photos') . '-' . date('Y-m-d') . '.zip';
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $download . '"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: no-store');
    readfile($tmp);
    @unlink($tmp);
    exit;
}
