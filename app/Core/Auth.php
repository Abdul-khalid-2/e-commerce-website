<?php
/**
 * app/Core/Auth.php
 */

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private function __construct()
    {
    }

    public static function isAdmin(): bool
    {
        return ($_SESSION['admin_id'] ?? null) !== null;
    }

    /**
     * Call at the top of every admin page (after bootstrap.php). Redirects
     * to the admin login page if there's no logged-in admin, preserving
     * the page they were trying to reach so login can send them back.
     */
    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            $return = $_SERVER['REQUEST_URI'] ?? 'index.php';
            header('Location: login.php?redirect=' . urlencode($return));
            exit;
        }
    }
}
