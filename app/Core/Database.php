<?php
/**
 * app/Core/Database.php
 *
 * Single, shared PDO connection for the whole app.
 *
 * Security notes:
 *  - PDO::ATTR_EMULATE_PREPARES is OFF, so every ->prepare()/->execute()
 *    call goes through the MySQL server's real prepared-statement engine
 *    instead of PHP faking it — this is what actually prevents SQL
 *    injection when parameters are bound, not just string escaping.
 *  - Errors throw exceptions instead of silently returning false, so a
 *    failed query can never be mistaken for an empty result.
 *  - Raw connection/credential errors are never shown to the visitor in
 *    production (APP_DEBUG=false) — they're logged instead.
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    private function __construct()
    {
        // Static-only class.
    }

    private function __clone()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = self::connect(DB_NAME);
        }

        return self::$connection;
    }

    /**
     * Connect to a specific database, or to the server with no database
     * selected (pass null) — used once by the migration runner to create
     * the database itself before it exists.
     */
    public static function connect(?string $database): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;%scharset=%s',
            DB_HOST,
            DB_PORT,
            $database ? "dbname={$database};" : '',
            DB_CHARSET
        );

        try {
            return new PDO(dsn: $dsn, username: DB_USER, password: DB_PASS, options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
            ]);
        } catch (PDOException $e) {
            error_log('[Database] Connection failed: ' . $e->getMessage());

            if (APP_DEBUG) {
                throw $e;
            }

            throw new PDOException('Unable to connect to the database. Please try again later.');
        }
    }
}
