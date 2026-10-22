<?php
declare(strict_types=1);

// Database backups: a compressed SQL file of every table, saved in storage/backups (never
// web accessible). cron/run.php makes one every night; the owner can also make one and
// download it from Admin > System check. Restore by importing the file in phpMyAdmin.

const BACKUP_DIR = STORAGE_PATH . '/backups';

/** Writes a full backup and returns its file name. Old backups are removed after the chosen number of days. */
function backup_database(): string
{
    if (!is_dir(BACKUP_DIR) && !mkdir(BACKUP_DIR, 0755, true) && !is_dir(BACKUP_DIR)) {
        throw new RuntimeException('Could not create storage/backups.');
    }
    $name = 'db-' . date('Y-m-d-His') . '.sql.gz';
    $path = BACKUP_DIR . '/' . $name;
    $gz = gzopen($path . '.part', 'wb6');
    if (!$gz) {
        throw new RuntimeException('Could not write the backup file.');
    }
    $pdo = db();
    // Dates are written in UTC (and the file says so), so a restore on any server gets the same times.
    $pdo->exec("SET time_zone = '+00:00'");
    try {
        gzwrite($gz, "-- " . setting('store_name') . " database backup, " . date('j F Y g:ia') . "\n"
            . "-- Restore: phpMyAdmin > your database > Import this file.\n\n"
            . "SET NAMES utf8mb4;\nSET time_zone = '+00:00';\nSET FOREIGN_KEY_CHECKS = 0;\n\n");
        foreach ($pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_NUM) as [$table]) {
            $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '', $table) . '`')->fetch(PDO::FETCH_NUM)[1];
            gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n{$create};\n\n");
            $rows = $pdo->query('SELECT * FROM `' . str_replace('`', '', $table) . '`', PDO::FETCH_NUM);
            $batch = [];
            foreach ($rows as $row) {
                $batch[] = '(' . implode(',', array_map(static fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)) . ')';
                if (count($batch) === 200) {
                    gzwrite($gz, "INSERT INTO `{$table}` VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }
            if ($batch) {
                gzwrite($gz, "INSERT INTO `{$table}` VALUES\n" . implode(",\n", $batch) . ";\n");
            }
            gzwrite($gz, "\n");
        }
        gzwrite($gz, "SET FOREIGN_KEY_CHECKS = 1;\n");
    } finally {
        gzclose($gz);
        $pdo->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
    }
    rename($path . '.part', $path);
    @chmod($path, 0600);
    prune_backups();
    return $name;
}

/** Backups on disk, newest first: [name, size in bytes, time]. */
function list_backups(): array
{
    $files = glob(BACKUP_DIR . '/db-*.sql.gz') ?: [];
    rsort($files);
    return array_map(static fn ($f) => ['name' => basename($f), 'size' => (int) filesize($f), 'time' => (int) filemtime($f)], $files);
}

/** Full path of a backup by name, or null (names are checked so no other file can be reached). */
function backup_path(string $name): ?string
{
    return preg_match('/^db-\d{4}-\d{2}-\d{2}-\d{6}\.sql\.gz$/', $name) && is_file(BACKUP_DIR . '/' . $name) ? BACKUP_DIR . '/' . $name : null;
}

/** Keeps the newest backups for the chosen number of days (at least 3 files always stay). */
function prune_backups(): void
{
    $keepDays = max(3, (int) setting('backup_keep_days', '14'));
    foreach (array_slice(list_backups(), 3) as $backup) {
        if ($backup['time'] < time() - $keepDays * 86400) {
            @unlink(BACKUP_DIR . '/' . $backup['name']);
        }
    }
}

/** Cron job: one backup a night, after 2am. */
function backup_task(DateTimeImmutable $now): string
{
    if (setting('backup_enabled', '1') !== '1') {
        return 'Backups are switched off.';
    }
    if (setting('backup_last_run') === $now->format('Y-m-d') || (int) $now->format('G') < 2) {
        return '';
    }
    setting_save('backup_last_run', $now->format('Y-m-d'));
    return 'Backup saved: ' . backup_database() . '.';
}

/**
 * Groups of customer data the owner can clear before going live (Admin > System), e.g. the test
 * orders made while setting the website up. Menu, pages, settings, photos and staff stay.
 * Each group: label, what it removes, the tables counted, and the tables whose numbering restarts.
 */
const CLEARABLE_DATA = [
    'orders'    => ['Orders', 'Orders with their items, payments, history and order chats. Coupon use counts go back to 0.', ['orders'], ['orders', 'order_items', 'order_events', 'payments']],
    'customers' => ['Customer accounts', 'Registered customer logins. Staff logins are not touched.', ['users'], ['users', 'password_resets']],
    'requests'  => ['Custom cake requests', 'Quote requests with their chats and reference photos.', ['custom_requests'], ['custom_requests']],
    'messages'  => ['Messages', 'Chats and contact form messages.', ['threads', 'contact_messages'], ['threads', 'thread_messages', 'contact_messages']],
    'logs'      => ['Logs', 'The "page not found" list in Admin > SEO and the login and spam limits.', ['not_found_log', 'rate_limits'], []],
];

/** How many rows each clearable group holds now. */
function clearable_counts(): array
{
    $counts = [];
    foreach (CLEARABLE_DATA as $key => [, , $tables]) {
        $counts[$key] = array_sum(array_map(static fn ($t) => (int) db_value('SELECT COUNT(*) FROM ' . $t), $tables));
    }
    return $counts;
}

/**
 * Deletes the chosen groups and returns how many rows went per group. A backup is made first;
 * nothing is deleted if the backup fails. Numbering (order numbers inside the database, ids)
 * starts again from 1 for tables that end up empty.
 */
function clear_shop_data(array $groups): array
{
    $groups = array_values(array_intersect(array_keys(CLEARABLE_DATA), $groups));
    if (!$groups) {
        return [];
    }
    $backup = backup_database();
    $before = clearable_counts();
    db()->beginTransaction();
    try {
        foreach ($groups as $group) {
            match ($group) {
                'orders'    => [db_run('DELETE FROM orders'), db_run('UPDATE coupons SET used_count = 0')],
                'customers' => db_run('DELETE FROM users'),
                'requests'  => db_run('DELETE FROM custom_requests'),
                'messages'  => [db_run('DELETE FROM threads'), db_run('DELETE FROM contact_messages')],
                'logs'      => [db_run('DELETE FROM not_found_log'), db_run('DELETE FROM rate_limits')],
            };
        }
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    // ALTER TABLE ends a transaction, so numbering is reset after the deletes are saved.
    foreach ($groups as $group) {
        foreach (CLEARABLE_DATA[$group][3] as $table) {
            if ((int) db_value('SELECT COUNT(*) FROM ' . $table) === 0) {
                db()->exec('ALTER TABLE ' . $table . ' AUTO_INCREMENT = 1');
            }
        }
    }
    // Payment screenshots and reference photos that no order or request uses any more (older than an hour,
    // so a customer uploading right now keeps theirs).
    remove_unused_uploads(new DateTimeImmutable('now'), 3600);
    $after = clearable_counts();
    $removed = ['backup' => $backup];
    foreach ($groups as $group) {
        $removed[$group] = $before[$group] - $after[$group];
    }
    return $removed;
}

/**
 * Which version of the code is running: the short git commit and its date, read straight from
 * .git (no git command needed). Shown in Admin > System check so the owner can see whether an
 * update has arrived. Null when the site was uploaded without the .git folder.
 */
function code_version(): ?array
{
    $git = BASE_PATH . '/.git';
    $head = is_file($git . '/HEAD') ? trim((string) file_get_contents($git . '/HEAD')) : '';
    if ($head === '') {
        return null;
    }
    $hash = $head;
    if (str_starts_with($head, 'ref: ')) {
        $ref = substr($head, 5);
        $hash = is_file($git . '/' . $ref) ? trim((string) file_get_contents($git . '/' . $ref)) : '';
        if ($hash === '' && is_file($git . '/packed-refs')) {
            foreach (file($git . '/packed-refs', FILE_IGNORE_NEW_LINES) ?: [] as $line) {
                if (str_ends_with($line, ' ' . $ref)) {
                    $hash = strtok($line, ' ');
                }
            }
        }
    }
    if (!preg_match('/^[0-9a-f]{40}$/', (string) $hash)) {
        return null;
    }
    $file = $git . '/' . (str_starts_with($head, 'ref: ') ? substr($head, 5) : 'HEAD');
    return ['commit' => substr($hash, 0, 7), 'time' => is_file($file) ? filemtime($file) : null];
}
