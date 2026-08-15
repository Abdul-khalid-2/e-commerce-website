<?php
/**
 * app/Models/ContactMessage.php
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ContactMessage extends Model
{
    protected static string $table = 'contact_messages';

    public static function submit(string $name, string $email, string $subject, string $message): int
    {
        return static::create([
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'status' => 'New',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function allOrdered(?string $status = null): array
    {
        $db = static::db();
        if ($status !== null) {
            $stmt = $db->prepare(
                'SELECT * FROM contact_messages WHERE status = :status ORDER BY created_at DESC'
            );
            $stmt->execute(['status' => $status]);
        } else {
            $stmt = $db->query('SELECT * FROM contact_messages ORDER BY created_at DESC');
        }
        return $stmt->fetchAll();
    }

    public static function countByStatus(string $status): int
    {
        $stmt = static::db()->prepare('SELECT COUNT(*) FROM contact_messages WHERE status = :status');
        $stmt->execute(['status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    public static function updateStatus(int $id, string $status): void
    {
        static::update($id, ['status' => $status]);
    }
}
