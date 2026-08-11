<?php
/**
 * app/Models/Setting.php
 *
 * Simple key-value store (store name, contact info, free shipping
 * threshold, etc.) backed by the `settings` table.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Setting
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1'
        );
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : $value;
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        $stmt = Database::getConnection()->query('SELECT setting_key, setting_value FROM settings');
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public static function set(string $key, string $value): void
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value = :value2'
        );
        $stmt->execute(['key' => $key, 'value' => $value, 'value2' => $value]);
    }
}
