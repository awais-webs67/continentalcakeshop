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
