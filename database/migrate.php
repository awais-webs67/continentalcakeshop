<?php
declare(strict_types=1);

// Brings an existing database up to date by running every file in database/migrations/
// that has not been run yet. Safe to run as often as you like.
//
//   php database/migrate.php
//   (XAMPP on Windows: C:\xampp\php\php.exe database\migrate.php)

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';

// Errors that only mean "this change is already in place".
const ALREADY_APPLIED = [1050, 1060, 1061, 1062, 1022, 1826];

/** Splits a SQL file into statements, ignoring semicolons inside quoted text. */
function split_sql(string $sql): array
{
    $statements = [];
    $current = '';
    $quote = null;
    $length = strlen($sql);
    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        if ($quote === null && $char === '-' && ($sql[$i + 1] ?? '') === '-') {
            $end = strpos($sql, "\n", $i);
            $i = $end === false ? $length : $end;
            continue;
        }
        if ($quote !== null && $char === '\\') {
            $current .= $char . ($sql[++$i] ?? '');
            continue;
        }
        if ($char === "'" || $char === '"') {
            if ($quote === null) {
                $quote = $char;
            } elseif ($quote === $char) {
                if (($sql[$i + 1] ?? '') === $char) {
                    $current .= $char . $char;
                    $i++;
                    continue;
                }
                $quote = null;
            }
        }
        if ($char === ';' && $quote === null) {
            if (trim($current) !== '') {
                $statements[] = trim($current);
            }
            $current = '';
            continue;
        }
        $current .= $char;
    }
    if (trim($current) !== '') {
        $statements[] = trim($current);
    }
    return $statements;
}

$pdo = db();
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
    migration VARCHAR(190) NOT NULL PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$done = array_column(db_all('SELECT migration FROM schema_migrations'), 'migration');
$files = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($files);

$ran = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $done, true)) {
        continue;
    }
    echo "Running {$name}... ";
    foreach (split_sql((string) file_get_contents($file)) as $statement) {
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            if (!in_array((int) ($e->errorInfo[1] ?? 0), ALREADY_APPLIED, true)) {
                echo "FAILED\n" . $e->getMessage() . "\n";
                exit(1);
            }
        }
    }
    db_run('INSERT INTO schema_migrations (migration) VALUES (?)', [$name]);
    echo "done\n";
    $ran++;
}

echo $ran ? "Database is up to date.\n" : "Nothing to do, the database is already up to date.\n";
