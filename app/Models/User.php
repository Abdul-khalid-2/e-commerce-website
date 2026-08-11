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
}
