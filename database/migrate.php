<?php
/**
 * database/migrate.php
 *
 * Run from the command line:
 *   php database/migrate.php            Apply all pending migrations
 *   php database/migrate.php status     Show which migrations have run
 *
 * On first run this also creates the database itself (DB_NAME from your
 * .env) if it doesn't already exist, so setup is a single command.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

use App\Core\Database;
use App\Core\Migrator;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

// Connect with no database selected yet, so we can create it if missing.
try {
    $server = Database::connect(null);
    $server->exec(
        'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` '
        . 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
} catch (\Throwable $e) {
    fwrite(STDERR, 'Could not reach MySQL/MariaDB: ' . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, 'Check DB_HOST, DB_PORT, DB_USER, DB_PASS in your .env file.' . PHP_EOL);
    exit(1);
}

$db = Database::getConnection();
$migrator = new Migrator($db, __DIR__ . '/migrations');

$command = $argv[1] ?? 'migrate';

if ($command === 'status') {
    echo 'Migration status for database `' . DB_NAME . '`:' . PHP_EOL . PHP_EOL;
    foreach ($migrator->status() as $file => $status) {
        $marker = $status === 'Ran' ? '✔' : '·';
        echo sprintf('  %s  %-45s %s%s', $marker, $file, $status, PHP_EOL);
    }
    exit(0);
}

try {
    $applied = $migrator->run();

    if (empty($applied)) {
        echo 'Nothing to migrate — database is already up to date.' . PHP_EOL;
        exit(0);
    }

    echo 'Applied ' . count($applied) . ' migration(s):' . PHP_EOL;
    foreach ($applied as $name) {
        echo '  ✔ ' . $name . PHP_EOL;
    }
} catch (\Throwable $e) {
    fwrite(STDERR, 'Migration error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
