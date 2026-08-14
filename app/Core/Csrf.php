<?php
/**
 * app/Core/Csrf.php
 *
 * One token per session, generated lazily on first use. Every
 * state-changing form (login, signup, checkout, admin forms) and every
 * state-changing AJAX endpoint (api/*.php) requires it.
 *
 * - Full-page forms: embed with Csrf::field(), verify with
 *   Csrf::verify($_POST['csrf_token'] ?? null) and handle failure like
 *   any other validation error (re-show the form with a message).
 * - JSON/AJAX endpoints: verify with Csrf::requireValidJson($token),
 *   which sends a 419 JSON response and exits on failure.
 */

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    private function __construct()
    {
    }

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            self::regenerate();
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Call after a successful login (in addition to
     * session_regenerate_id()) so a token issued before authenticating
     * can't be replayed afterward.
     */
    public static function regenerate(): string
    {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $token): bool
    {
        return is_string($token)
            && $token !== ''
            && isset($_SESSION[self::SESSION_KEY])
            && hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    /**
     * For JSON API endpoints: verify or send a 419 JSON response and exit.
     */
    public static function requireValidJson(?string $token): void
    {
        if (!self::verify($token)) {
            http_response_code(419);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Your session has expired. Please refresh the page and try again.',
            ]);
            exit;
        }
    }
}
