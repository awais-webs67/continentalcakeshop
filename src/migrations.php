<?php
declare(strict_types=1);

// Database migrations: SQL files in database/migrations/ that bring an older database up to date.
// Run them with `php database/migrate.php`, or from Admin > System check on hosting without SSH.

// Errors that only mean "this change is already in place".
const MIGRATION_ALREADY_APPLIED = [1050, 1060, 1061, 1062, 1022, 1826];

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

function migration_files(): array
{
    $files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
    sort($files);
    return $files;
}

/** Names of migration files that have not been run on this database yet. */
function pending_migrations(): array
{
    db()->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(190) NOT NULL PRIMARY KEY,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $done = array_column(db_all('SELECT migration FROM schema_migrations'), 'migration');
    return array_values(array_filter(array_map('basename', migration_files()), static fn ($name) => !in_array($name, $done, true)));
}

/**
 * Runs every pending migration in order. $progress is called with each file name.
 * Returns the names that ran; throws on the first real error.
 */
function run_migrations(?callable $progress = null): array
{
    $ran = [];
    foreach (pending_migrations() as $name) {
        $progress && $progress($name);
        foreach (split_sql((string) file_get_contents(BASE_PATH . '/database/migrations/' . $name)) as $statement) {
            try {
                db()->exec($statement);
            } catch (PDOException $e) {
                if (!in_array((int) ($e->errorInfo[1] ?? 0), MIGRATION_ALREADY_APPLIED, true)) {
                    throw new RuntimeException($name . ': ' . $e->getMessage(), 0, $e);
                }
            }
        }
        db_run('INSERT INTO schema_migrations (migration) VALUES (?)', [$name]);
        $ran[] = $name;
    }
    return $ran;
}
