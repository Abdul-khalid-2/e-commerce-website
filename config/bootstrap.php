<?php
/**
 * config/bootstrap.php
 *
 * Every PHP entry point (pages, admin, cron scripts, CLI tools) should
 * require this file first and nothing else. It wires up config, the
 * autoloader, and a secure session — in that order.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once APP_PATH . '/Core/Autoloader.php';

App\Core\Autoloader::register('App', APP_PATH);

// ---------------------------------------------------------------------
// Secure session defaults.
// httponly  -> JS can't read the session cookie (mitigates XSS cookie theft)
// samesite  -> Lax stops the cookie being sent on most cross-site requests (CSRF hardening)
// secure    -> only sent over HTTPS once the site is served over HTTPS
// ---------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
