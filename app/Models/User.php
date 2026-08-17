<?php
/**
 * app/Models/User.php
 *
 * Passwords are always handled through password_hash()/password_verify()
 * (bcrypt by default) — never stored or compared as plain text.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        $stmt = static::db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Create a user, hashing the plain-text password before it ever
     * touches the database.
     */
    public static function register(
        string $name,
        string $email,
        string $plainPassword,
        string $role = 'customer',
        ?string $phone = null
    ): int {
        return static::create([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
            'phone' => $phone,
            'role' => $role,
        ]);
    }

    public static function verifyPassword(array $user, string $plainPassword): bool
    {
        return password_verify($plainPassword, $user['password_hash']);
    }

    /**
     * Stores a hash of the reset token (never the raw token — same
     * principle as password storage: even a DB compromise shouldn't
     * hand out directly-usable secrets) with an expiry.
     */
    public static function setResetToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        static::update($userId, [
            'reset_token_hash' => $tokenHash,
            'reset_token_expires_at' => $expiresAt,
        ]);
    }

    /**
     * Looks up a user by email and checks the given raw token hashes to
     * their stored reset_token_hash and hasn't expired. Returns null on
     * any mismatch (wrong email, wrong token, or expired) without
     * revealing which — callers should show one generic message either way.
     */
    public static function findByValidResetToken(string $email, string $rawToken): ?array
    {
        $user = self::findByEmail($email);
        if (!$user || empty($user['reset_token_hash']) || empty($user['reset_token_expires_at'])) {
            return null;
        }
        if (strtotime($user['reset_token_expires_at']) < time()) {
            return null;
        }
        $tokenHash = hash('sha256', $rawToken);
        if (!hash_equals($user['reset_token_hash'], $tokenHash)) {
            return null;
        }
        return $user;
    }

    public static function clearResetToken(int $userId): void
    {
        static::update($userId, [
            'reset_token_hash' => null,
            'reset_token_expires_at' => null,
        ]);
    }

    public static function updatePassword(int $userId, string $newPlainPassword): void
    {
        static::update($userId, [
            'password_hash' => password_hash($newPlainPassword, PASSWORD_DEFAULT),
        ]);
    }
}
