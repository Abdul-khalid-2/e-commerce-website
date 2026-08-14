<?php
/**
 * app/Models/Wishlist.php
 *
 * Only for logged-in users — the wishlists table requires a user_id.
 * Guests still use localStorage client-side (see assets/js/common.js);
 * this model is what that client code talks to once someone is logged
 * in, via api/wishlist.php.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Wishlist
{
    /**
     * @return int[] Product ids in this user's wishlist.
     */
    public static function getProductIds(int $userId): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT product_id FROM wishlists WHERE user_id = :uid ORDER BY created_at DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function add(int $userId, int $productId): void
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (:uid, :pid)'
        );
        $stmt->execute(['uid' => $userId, 'pid' => $productId]);
    }

    public static function remove(int $userId, int $productId): void
    {
        $stmt = Database::getConnection()->prepare(
            'DELETE FROM wishlists WHERE user_id = :uid AND product_id = :pid'
        );
        $stmt->execute(['uid' => $userId, 'pid' => $productId]);
    }

    public static function has(int $userId, int $productId): bool
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT 1 FROM wishlists WHERE user_id = :uid AND product_id = :pid LIMIT 1'
        );
        $stmt->execute(['uid' => $userId, 'pid' => $productId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function count(int $userId): int
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT COUNT(*) FROM wishlists WHERE user_id = :uid'
        );
        $stmt->execute(['uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Folds a guest's localStorage wishlist (product ids) into the
     * user's account wishlist on login/signup. Ignores ids that are
     * already there or don't correspond to a real product.
     *
     * @param int[] $productIds
     */
    public static function mergeGuestIds(int $userId, array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id FROM products WHERE id = :id LIMIT 1');
        $insert = $db->prepare('INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (:uid, :pid)');

        foreach (array_unique(array_map('intval', $productIds)) as $productId) {
            $stmt->execute(['id' => $productId]);
            if ($stmt->fetchColumn()) {
                $insert->execute(['uid' => $userId, 'pid' => $productId]);
            }
        }
    }
}
