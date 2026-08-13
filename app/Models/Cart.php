<?php
/**
 * app/Models/Cart.php
 *
 * Cart is identified by the PHP session id, so it works for guests with
 * zero friction; if a user logs in, mergeSessionIntoUser() folds their
 * guest cart into their account cart so items aren't lost.
 *
 * Two ways to get "the current cart":
 *  - peekForSession(): read-only, never creates a row. Use this for
 *    anything that just needs to *display* a count/total (e.g. the
 *    navbar badge) so browsing the site doesn't create empty cart rows
 *    for every visitor.
 *  - current(): get-or-create. Use this the moment an item is actually
 *    being added.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Cart
{
    /**
     * Read-only lookup — returns null if this session has no cart yet.
     */
    public static function peekForSession(string $sessionId, ?int $userId): ?array
    {
        $db = Database::getConnection();

        if ($userId !== null) {
            $stmt = $db->prepare('SELECT * FROM carts WHERE user_id = :uid LIMIT 1');
            $stmt->execute(['uid' => $userId]);
            $cart = $stmt->fetch();
            if ($cart) {
                return $cart;
            }
        }

        $stmt = $db->prepare('SELECT * FROM carts WHERE session_id = :sid AND user_id IS NULL LIMIT 1');
        $stmt->execute(['sid' => $sessionId]);
        $cart = $stmt->fetch();
        return $cart ?: null;
    }

    /**
     * Get this session's cart, creating one if it doesn't exist yet.
     */
    public static function current(string $sessionId, ?int $userId): array
    {
        $cart = self::peekForSession($sessionId, $userId);
        if ($cart) {
            return $cart;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('INSERT INTO carts (user_id, session_id) VALUES (:uid, :sid)');
        $stmt->execute(['uid' => $userId, 'sid' => $sessionId]);
        $id = (int) $db->lastInsertId();

        return ['id' => $id, 'user_id' => $userId, 'session_id' => $sessionId];
    }

    public static function addItem(int $cartId, int $productId, int $qty, ?string $color, ?string $size): void
    {
        $db = Database::getConnection();

        // Same product + same color/size choice -> bump quantity instead
        // of creating a duplicate row.
        $stmt = $db->prepare(
            'SELECT id, qty FROM cart_items
             WHERE cart_id = :cid AND product_id = :pid
               AND color <=> :color AND size <=> :size
             LIMIT 1'
        );
        $stmt->execute(['cid' => $cartId, 'pid' => $productId, 'color' => $color, 'size' => $size]);
        $existing = $stmt->fetch();

        if ($existing) {
            $update = $db->prepare('UPDATE cart_items SET qty = :qty WHERE id = :id');
            $update->execute(['qty' => (int) $existing['qty'] + $qty, 'id' => $existing['id']]);
            return;
        }

        $insert = $db->prepare(
            'INSERT INTO cart_items (cart_id, product_id, qty, color, size)
             VALUES (:cid, :pid, :qty, :color, :size)'
        );
        $insert->execute(['cid' => $cartId, 'pid' => $productId, 'qty' => $qty, 'color' => $color, 'size' => $size]);
    }

    public static function updateItemQty(int $cartId, int $itemId, int $qty): void
    {
        $qty = max(1, $qty);
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE cart_items SET qty = :qty WHERE id = :id AND cart_id = :cid');
        $stmt->execute(['qty' => $qty, 'id' => $itemId, 'cid' => $cartId]);
    }

    public static function removeItem(int $cartId, int $itemId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM cart_items WHERE id = :id AND cart_id = :cid');
        $stmt->execute(['id' => $itemId, 'cid' => $cartId]);
    }

    public static function clear(int $cartId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM cart_items WHERE cart_id = :cid');
        $stmt->execute(['cid' => $cartId]);
    }

    /**
     * Cart items joined with their product's current name/price/image,
     * for rendering. Prices always come from the products table (the
     * live price), never trusted from anywhere client-side.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getItemsWithProduct(int $cartId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT
                ci.id AS item_id, ci.qty, ci.color, ci.size,
                p.id AS product_id, p.name, p.price, p.slug,
                (SELECT image_url FROM product_images
                 WHERE product_id = p.id ORDER BY sort_order ASC, id ASC LIMIT 1) AS image
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             WHERE ci.cart_id = :cid
             ORDER BY ci.id ASC'
        );
        $stmt->execute(['cid' => $cartId]);
        return $stmt->fetchAll();
    }

    public static function getItemCount(int $cartId): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COALESCE(SUM(qty), 0) FROM cart_items WHERE cart_id = :cid');
        $stmt->execute(['cid' => $cartId]);
        return (int) $stmt->fetchColumn();
    }

    public static function getSubtotal(int $cartId): float
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT COALESCE(SUM(ci.qty * p.price), 0)
             FROM cart_items ci JOIN products p ON p.id = ci.product_id
             WHERE ci.cart_id = :cid'
        );
        $stmt->execute(['cid' => $cartId]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Called right after a successful login: folds a guest session's
     * cart into the user's own cart (merging quantities for matching
     * product/color/size), so items picked before logging in aren't lost.
     */
    public static function mergeSessionIntoUser(string $sessionId, int $userId): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT * FROM carts WHERE session_id = :sid AND user_id IS NULL LIMIT 1');
        $stmt->execute(['sid' => $sessionId]);
        $guestCart = $stmt->fetch();
        if (!$guestCart) {
            return;
        }

        $stmt = $db->prepare('SELECT * FROM carts WHERE user_id = :uid LIMIT 1');
        $stmt->execute(['uid' => $userId]);
        $userCart = $stmt->fetch();

        if (!$userCart) {
            // No existing user cart — just claim the guest cart.
            $update = $db->prepare('UPDATE carts SET user_id = :uid, session_id = :sid WHERE id = :id');
            $update->execute(['uid' => $userId, 'sid' => $sessionId, 'id' => $guestCart['id']]);
            return;
        }

        // Both exist — merge guest items into the user's cart, then
        // discard the now-empty guest cart.
        foreach (self::getItemsWithProduct((int) $guestCart['id']) as $item) {
            self::addItem(
                (int) $userCart['id'],
                (int) $item['product_id'],
                (int) $item['qty'],
                $item['color'],
                $item['size']
            );
        }
        self::clear((int) $guestCart['id']);
        $stmt = $db->prepare('DELETE FROM carts WHERE id = :id');
        $stmt->execute(['id' => $guestCart['id']]);
    }
}
