<?php
/**
 * app/Models/Testimonial.php
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Testimonial extends Model
{
    protected static string $table = 'testimonials';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function active(): array
    {
        $stmt = static::db()->query(
            'SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
        return $stmt->fetchAll();
    }
}
