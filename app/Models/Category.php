<?php
/**
 * app/Models/Category.php
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

final class Category extends Model
{
    protected static string $table = 'categories';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function active(): array
    {
        $stmt = static::db()->query(
            'SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC'
        );
        return $stmt->fetchAll();
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = static::db()->prepare('SELECT * FROM categories WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Count of active products in each category, keyed by category id.
     *
     * @return array<int, int>
     */
    public static function productCounts(): array
    {
        $stmt = static::db()->query(
            'SELECT category_id, COUNT(*) AS total FROM products
             WHERE is_active = 1 AND category_id IS NOT NULL
             GROUP BY category_id'
        );
        $counts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[(int) $row['category_id']] = (int) $row['total'];
        }
        return $counts;
    }
}
