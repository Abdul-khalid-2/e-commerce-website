<?php
/**
 * app/Models/Review.php
 *
 * Real reviews, one per logged-in user per product (enforced by a
 * unique key in the migration, double-checked here before insert).
 *
 * products.rating / products.reviews_count stay as a small denormalized
 * cache — recalculateProductRating() keeps them in sync after every new
 * review, so every other page that reads a product's rating (product
 * cards, listings) doesn't need to join/aggregate product_reviews on
 * every request.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Review
{
    /**
     * @return array<int, array<string, mixed>> Newest first, with the
     *         reviewer's name joined in.
     */
    public static function forProduct(int $productId): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT r.id, r.rating, r.comment, r.created_at, u.name AS reviewer_name
             FROM product_reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.product_id = :pid
             ORDER BY r.created_at DESC'
        );
        $stmt->execute(['pid' => $productId]);
        return $stmt->fetchAll();
    }

    public static function userHasReviewed(int $productId, int $userId): bool
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT 1 FROM product_reviews WHERE product_id = :pid AND user_id = :uid LIMIT 1'
        );
        $stmt->execute(['pid' => $productId, 'uid' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * @throws \RuntimeException if this user already reviewed this product
     */
    public static function create(int $productId, int $userId, int $rating, string $comment): int
    {
        if (self::userHasReviewed($productId, $userId)) {
            throw new \RuntimeException('You have already reviewed this product.');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO product_reviews (product_id, user_id, rating, comment)
             VALUES (:pid, :uid, :rating, :comment)'
        );
        $stmt->execute([
            'pid' => $productId,
            'uid' => $userId,
            'rating' => $rating,
            'comment' => $comment,
        ]);
        $id = (int) $db->lastInsertId();

        self::recalculateProductRating($productId);

        return $id;
    }

    /**
     * Recomputes products.rating (average, rounded to 1 decimal) and
     * products.reviews_count from the real product_reviews rows, and
     * writes them back onto the product. Call after any review
     * create/update/delete.
     */
    public static function recalculateProductRating(int $productId): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare(
            'SELECT COUNT(*) AS cnt, COALESCE(AVG(rating), 0) AS avg_rating
             FROM product_reviews WHERE product_id = :pid'
        );
        $stmt->execute(['pid' => $productId]);
        $row = $stmt->fetch();

        $count = (int) $row['cnt'];
        if ($count === 0) {
            // No real reviews yet — leave the product's existing
            // (seeded/manual baseline) rating alone rather than
            // zeroing it out.
            return;
        }

        $avg = round((float) $row['avg_rating'], 1);

        $update = $db->prepare(
            'UPDATE products SET rating = :rating, reviews_count = :count WHERE id = :pid'
        );
        $update->execute(['rating' => $avg, 'count' => $count, 'pid' => $productId]);
    }
}
