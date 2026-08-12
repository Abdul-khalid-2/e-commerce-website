<?php
/**
 * app/Models/HeroSlide.php
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class HeroSlide extends Model
{
    protected static string $table = 'hero_slides';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function active(): array
    {
        $stmt = static::db()->query(
            'SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
        return $stmt->fetchAll();
    }
}
