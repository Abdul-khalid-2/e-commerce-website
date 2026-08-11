<?php
/**
 * app/Core/Model.php
 *
 * Base class every model (Product, Category, Order, ...) extends.
 * Provides simple, secure CRUD without an ORM — every method below
 * uses PDO prepared statements for values.
 *
 * Security note: table/column *names* can never be bound as PDO
 * parameters (only values can) — they have to be concatenated into the
 * SQL string. To keep that safe, validateIdentifier() whitelists names
 * to [a-zA-Z_][a-zA-Z0-9_]* before they're used, so even if a caller
 * passes something unexpected into where()/all() it fails safely
 * instead of building broken/injectable SQL. Table names themselves
 * come only from each model's own $table property, never from a
 * request.
 */

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use PDO;

abstract class Model
{
    protected static string $table;
    protected static string $primaryKey = 'id';

    protected static function db(): PDO
    {
        return Database::getConnection();
    }

    public static function find(int $id): ?array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(?string $orderBy = null): array
    {
        $sql = 'SELECT * FROM ' . static::$table;
        if ($orderBy !== null) {
            $sql .= ' ORDER BY ' . self::validateOrderBy($orderBy);
        }
        return static::db()->query($sql)->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function where(string $column, mixed $value): array
    {
        self::validateIdentifier($column);
        $stmt = static::db()->prepare('SELECT * FROM ' . static::$table . " WHERE {$column} = :value");
        $stmt->execute(['value' => $value]);
        return $stmt->fetchAll();
    }

    /**
     * Insert a row. $data keys must be real column names — values are
     * always bound as parameters, never concatenated.
     */
    public static function create(array $data): int
    {
        $columns = array_keys($data);
        array_walk($columns, [self::class, 'validateIdentifier']);

        $placeholders = array_map(fn($c) => ":{$c}", $columns);
        $sql = 'INSERT INTO ' . static::$table
            . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';

        $stmt = static::db()->prepare($sql);
        $stmt->execute($data);

        return (int) static::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $columns = array_keys($data);
        array_walk($columns, [self::class, 'validateIdentifier']);

        $set = implode(', ', array_map(fn($c) => "{$c} = :{$c}", $columns));
        $sql = 'UPDATE ' . static::$table . " SET {$set} WHERE " . static::$primaryKey . ' = :__id';

        $stmt = static::db()->prepare($sql);
        return $stmt->execute([...$data, '__id' => $id]);
    }

    public static function delete(int $id): bool
    {
        $stmt = static::db()->prepare(
            'DELETE FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = :id'
        );
        return $stmt->execute(['id' => $id]);
    }

    public static function count(): int
    {
        return (int) static::db()->query('SELECT COUNT(*) FROM ' . static::$table)->fetchColumn();
    }

    private static function validateIdentifier(string $name): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Invalid column identifier: {$name}");
        }
    }

    private static function validateOrderBy(string $orderBy): string
    {
        // Allows "column" or "column ASC"/"column DESC", nothing else.
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\s+(ASC|DESC))?$/i', trim($orderBy))) {
            throw new InvalidArgumentException("Invalid ORDER BY clause: {$orderBy}");
        }
        return $orderBy;
    }
}
