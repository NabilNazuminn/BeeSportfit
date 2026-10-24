<?php
/** PDO connection. Use db() anywhere; it connects once and reuses it. */

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("SET time_zone = '+07:00'");   // same clock as PHP (Asia/Jakarta) - otherwise times can be 7 h off
        } catch (PDOException $e) {
            http_response_code(500);
            exit(DEBUG ? 'Database error: ' . htmlspecialchars($e->getMessage())
                       : 'Website sedang dalam perbaikan. Silakan coba lagi nanti.');
        }
    }
    return $pdo;
}

/** Shortcut: run a query and return all rows. */
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Shortcut: run a query and return ONE row (or null). */
function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row ?: null;
}

/** Shortcut: run INSERT/UPDATE/DELETE and return affected rows. */
function db_run(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}
