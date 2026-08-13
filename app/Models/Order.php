<?php
/**
 * app/Models/Order.php
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Order
{
    public static function findByNumber(string $orderNumber): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM orders WHERE order_number = :num LIMIT 1');
        $stmt->execute(['num' => $orderNumber]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getItems(int $orderId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT oi.*,
                (SELECT image_url FROM product_images
                 WHERE product_id = oi.product_id ORDER BY sort_order ASC, id ASC LIMIT 1) AS image
             FROM order_items oi
             WHERE oi.order_id = :oid
             ORDER BY oi.id ASC'
        );
        $stmt->execute(['oid' => $orderId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function findByNumbers(array $orderNumbers): array
    {
        if (empty($orderNumbers)) {
            return [];
        }
        $db = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($orderNumbers), '?'));
        $stmt = $db->prepare(
            "SELECT * FROM orders WHERE order_number IN ({$placeholders}) ORDER BY created_at DESC"
        );
        $stmt->execute(array_values($orderNumbers));
        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function recentByUser(int $userId, int $limit = 10): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC LIMIT ' . max(1, $limit)
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Creates the order and its line items from a cart's contents, all
     * in one transaction. Prices are read fresh from the products table
     * at order time (via Cart::getItemsWithProduct) — never trusted
     * from the client.
     *
     * @param array<int, array<string, mixed>> $cartItems From Cart::getItemsWithProduct()
     */
    public static function createFromCart(array $orderData, array $cartItems): array
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $orderNumber = self::generateOrderNumber();

            $stmt = $db->prepare(
                'INSERT INTO orders
                    (order_number, user_id, customer_name, customer_email, customer_phone,
                     shipping_address, city, postal_code, payment_method,
                     subtotal, shipping_fee, discount, total, status)
                 VALUES
                    (:order_number, :user_id, :customer_name, :customer_email, :customer_phone,
                     :shipping_address, :city, :postal_code, :payment_method,
                     :subtotal, :shipping_fee, :discount, :total, :status)'
            );
            $stmt->execute([
                'order_number' => $orderNumber,
                'user_id' => $orderData['user_id'] ?? null,
                'customer_name' => $orderData['customer_name'],
                'customer_email' => $orderData['customer_email'] ?? null,
                'customer_phone' => $orderData['customer_phone'],
                'shipping_address' => $orderData['shipping_address'],
                'city' => $orderData['city'],
                'postal_code' => $orderData['postal_code'] ?? null,
                'payment_method' => $orderData['payment_method'],
                'subtotal' => $orderData['subtotal'],
                'shipping_fee' => $orderData['shipping_fee'],
                'discount' => $orderData['discount'] ?? 0,
                'total' => $orderData['total'],
                'status' => 'Pending',
            ]);
            $orderId = (int) $db->lastInsertId();

            $itemStmt = $db->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, price, qty, color, size)
                 VALUES (:order_id, :product_id, :product_name, :price, :qty, :color, :size)'
            );
            foreach ($cartItems as $item) {
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'color' => $item['color'],
                    'size' => $item['size'],
                ]);
            }

            $db->commit();

            return ['id' => $orderId, 'order_number' => $orderNumber];
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private static function generateOrderNumber(): string
    {
        // ORD-<year><6 random digits> — short, human-readable, and
        // collision-checked against the unique order_number column.
        do {
            $candidate = 'ORD-' . date('y') . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::findByNumber($candidate) !== null);

        return $candidate;
    }
}
