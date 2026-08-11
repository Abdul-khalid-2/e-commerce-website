<?php
/**
 * app/Core/Str.php
 *
 * Tiny string helper. Only what the app actually needs — not a full
 * utility library.
 */

declare(strict_types=1);

namespace App\Core;

final class Str
{
    private function __construct()
    {
        // Static-only class.
    }

    /**
     * Turn "Samsung Galaxy A55 5G" into "samsung-galaxy-a55-5g".
     */
    public static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
}
