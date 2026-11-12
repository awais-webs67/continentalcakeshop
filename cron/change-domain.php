<?php
declare(strict_types=1);

// After moving the site to another address: replaces links to the old address that were saved
// in the database (images and links in pages, FAQs and cake descriptions, menu links, settings)
// with the address in config.php (app_url). It makes a database backup first.
// Run it once in cPanel > Terminal, for example:
//
//   php ~/public_html/cron/change-domain.php cake.skillsnexuspro.com
//
// Add --dry to only count what would change. Command line only.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';

$args = array_slice($argv, 1);
$dry = in_array('--dry', $args, true);
$oldHost = strtolower(trim((string) (array_values(array_filter($args, static fn ($a) => $a !== '--dry'))[0] ?? '')));
$oldHost = (string) preg_replace('#^https?://#', '', rtrim($oldHost, '/'));
$new = rtrim((string) config('app_url', ''), '/');
$newHost = strtolower((string) parse_url($new, PHP_URL_HOST));

if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $oldHost)) {
    fwrite(STDERR, "Give the old address, e.g.: php cron/change-domain.php cake.skillsnexuspro.com\n");
    exit(1);
}
if ($newHost === '' || $newHost === $oldHost) {
    fwrite(STDERR, "app_url in config/config.php must be the new address (it is now: {$new}).\n");
    exit(1);
}

// Each spelling of the old address, also as it is written inside JSON ("https:\/\/...").
$pairs = [];
foreach (['https://', 'http://'] as $scheme) {
    foreach ([$oldHost, 'www.' . $oldHost] as $host) {
        $pairs[$scheme . $host] = $new;
        $pairs[str_replace('/', '\/', $scheme . $host)] = str_replace('/', '\/', $new);
    }
}

$columns = db_all(
    "SELECT TABLE_NAME AS t, COLUMN_NAME AS c FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND DATA_TYPE IN ('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext')"
);

// Where the old address appears.
$found = [];
foreach ($columns as $col) {
    $table = '`' . str_replace('`', '', $col['t']) . '`';
    $column = '`' . str_replace('`', '', $col['c']) . '`';
    $rows = (int) db_value("SELECT COUNT(*) FROM {$table} WHERE {$column} LIKE ?", ['%' . $oldHost . '%']);
    if ($rows > 0) {
        $found[] = [$table, $column, $col['t'] . '.' . $col['c'], $rows];
    }
}
if ($found && !$dry) {
    echo 'Backup first: storage/backups/' . backup_database() . "\n";
}
$total = 0;
foreach ($found as [$table, $column, $label, $rows]) {
    if (!$dry) {
        $expr = $column;
        $params = [];
        foreach ($pairs as $from => $to) {
            $expr = "REPLACE({$expr}, ?, ?)";
            array_push($params, $from, $to);
        }
        db_run("UPDATE {$table} SET {$column} = {$expr} WHERE {$column} LIKE ?", [...$params, '%' . $oldHost . '%']);
    }
    echo ($dry ? 'would change ' : 'changed ') . "{$rows} in {$label}\n";
    $total += $rows;
}
echo $total ? ($dry ? "{$total} to change. Run again without --dry to change them.\n" : "Done: {$total} changed to {$new}.\n")
    : "Nothing in the database points to {$oldHost}.\n";
