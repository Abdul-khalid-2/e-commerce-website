<?php
/**
 * app/Core/Autoloader.php
 *
 * A minimal PSR-4-style autoloader so the app can use namespaces and
 * classes without requiring Composer. Register a namespace prefix once
 * (from bootstrap.php) and every "App\Foo\Bar" class resolves to
 * app/Foo/Bar.php automatically.
 */

declare(strict_types=1);

namespace App\Core;

final class Autoloader
{
    /** @var array<string, string> Namespace prefix => base directory */
    private static array $prefixes = [];

    private function __construct()
    {
        // Static-only class.
    }

    public static function register(string $prefix, string $baseDir): void
    {
        $prefix = rtrim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, '/') . '/';
        self::$prefixes[$prefix] = $baseDir;

        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
        foreach (self::$prefixes as $prefix => $baseDir) {
            if (!str_starts_with($class, $prefix)) {
                continue;
            }

            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (is_file($file)) {
                require $file;
                return;
            }
        }
    }
}
