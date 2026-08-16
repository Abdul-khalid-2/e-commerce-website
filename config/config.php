<?php
/**
 * config/config.php
 *
 * Loads environment variables from .env (if present) and defines the
 * application-wide constants every other file relies on. This file has
 * no dependencies — it's the very first thing bootstrap.php loads.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------
// Load .env into getenv()/$_ENV — no external library needed.
// ---------------------------------------------------------------------
$envPath = dirname(__DIR__) . '/.env';

if (is_file($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Strip matching surrounding quotes, if any.
        if (strlen($value) >= 2 && $value[0] === $value[-1] && in_array($value[0], ['"', "'"], true)) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Read an environment variable with a fallback default.
 */
function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return match (strtolower($value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        default => $value,
    };
}

// ---------------------------------------------------------------------
// App constants
// ---------------------------------------------------------------------
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', (bool) env('APP_DEBUG', false));
define('APP_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/'));
define('APP_TIMEZONE', env('APP_TIMEZONE', 'Asia/Karachi'));

// ---------------------------------------------------------------------
// Database constants
// ---------------------------------------------------------------------
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'shopmate'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// ---------------------------------------------------------------------
// Mail (SMTP) constants
// ---------------------------------------------------------------------
// These default to placeholder values — see .env.example and the
// README "Email setup" section. Order confirmation emails will fail
// silently (logged, never blocking checkout) until real credentials
// are supplied.
define('MAIL_HOST', env('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', (int) env('MAIL_PORT', 587));
define('MAIL_ENCRYPTION', env('MAIL_ENCRYPTION', 'tls'));
define('MAIL_USERNAME', env('MAIL_USERNAME', 'youremail@gmail.com'));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', 'your-16-char-app-password'));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'youremail@gmail.com'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'ShopMate Pakistan'));

// ---------------------------------------------------------------------
// Base paths — used by the autoloader, migrator, and includes later.
// ---------------------------------------------------------------------
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

// ---------------------------------------------------------------------
// Runtime settings
// ---------------------------------------------------------------------
date_default_timezone_set(APP_TIMEZONE);

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

// A dedicated error log keeps this out of the webserver's shared log.
$logDir = BASE_PATH . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/php-error.log');
