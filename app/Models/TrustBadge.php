<?php
/**
 * app/Models/TrustBadge.php
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TrustBadge extends Model
{
    protected static string $table = 'trust_badges';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function active(): array
    {
        $stmt = static::db()->query(
            'SELECT * FROM trust_badges WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
        return $stmt->fetchAll();
    }
}
