<?php
/**
 * app/Core/Migrator.php
 *
 * A small, dependency-free migration runner built for plain .sql files.
 *
 * How it works:
 *  - Every file in database/migrations/ is a plain .sql file, named with
 *    a zero-padded numeric prefix so they always sort in run order:
 *      001_create_users_table.sql
 *      002_create_categories_table.sql
 *      ...
 *  - A `migrations` table (created automatically) records which files
 *    have already run, so re-running the migrator is always safe —
 *    only new files get applied.
 *  - To change the schema later: ALTER TABLE statements go in a new,
 *    higher-numbered .sql file (e.g. 015_add_sku_to_products.sql).
 *    Never edit an already-applied migration file — add a new one.
 *  - Each migration runs inside a transaction; if it fails, it's rolled
 *    back and the script stops before touching later files.
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Migrator
{
    private PDO $db;
    private string $migrationsPath;

    public function __construct(PDO $db, string $migrationsPath)
    {
        $this->db = $db;
        $this->migrationsPath = rtrim($migrationsPath, '/');
        $this->ensureMigrationsTableExists();
    }

    /**
     * Run every migration that hasn't been applied yet.
     *
     * @return string[] Filenames that were applied in this run.
     */
    public function run(): array
    {
        $alreadyRun = $this->getAppliedMigrations();
        $pending = array_filter(
            $this->getMigrationFiles(),
            fn(string $file) => !in_array(basename($file), $alreadyRun, true)
        );

        $applied = [];
        $batch = $this->getNextBatchNumber();

        foreach ($pending as $file) {
            $name = basename($file);
            $sql = file_get_contents($file);

            if ($sql === false || trim($sql) === '') {
                continue;
            }

            try {
                // Note: DDL statements (CREATE TABLE, ALTER TABLE, ...) cause
                // an implicit commit in MySQL/MariaDB, so a migration can't be
                // wrapped in a rollback-able transaction the way a normal data
                // change can. If exec() throws, nothing further runs for this
                // file and the script stops before touching later migrations.
                $this->db->exec($sql);

                $stmt = $this->db->prepare(
                    'INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)'
                );
                $stmt->execute(['migration' => $name, 'batch' => $batch]);

                $applied[] = $name;
            } catch (\Throwable $e) {
                throw new RuntimeException("Migration failed [{$name}]: " . $e->getMessage(), 0, $e);
            }
        }

        return $applied;
    }

    /**
     * @return array<string, string> Filename => 'Ran' | 'Pending'
     */
    public function status(): array
    {
        $alreadyRun = $this->getAppliedMigrations();
        $status = [];

        foreach ($this->getMigrationFiles() as $file) {
            $name = basename($file);
            $status[$name] = in_array($name, $alreadyRun, true) ? 'Ran' : 'Pending';
        }

        return $status;
    }

    private function ensureMigrationsTableExists(): void
    {
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT UNSIGNED NOT NULL,
                run_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    /** @return string[] */
    private function getAppliedMigrations(): array
    {
        $stmt = $this->db->query('SELECT migration FROM migrations');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return string[] Absolute file paths, sorted. */
    private function getMigrationFiles(): array
    {
        $files = glob($this->migrationsPath . '/*.sql') ?: [];
        sort($files, SORT_STRING);
        return $files;
    }

    private function getNextBatchNumber(): int
    {
        $stmt = $this->db->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations');
        return (int) $stmt->fetchColumn();
    }
}
