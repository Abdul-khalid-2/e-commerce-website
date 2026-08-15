<?php
/**
 * app/Models/Product.php
 *
 * A product's images/colors/sizes/specs live in their own tables
 * (see database/migrations/004-006). find()/findBySlug() attach them
 * automatically; all()/byCategory()/search() return bare rows for
 * listing pages, where loading every relation for every card would be
 * wasteful — call withRelations() explicitly if a listing ever needs it.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

final class Product extends Model
{
    protected static string $table = 'products';

    public static function find(int $id): ?array
    {
        $product = parent::find($id);
        return $product ? self::withRelations($product) : null;
    }

    /**
     * Fallback for when no valid id/slug is given — the lowest-id active
     * product, with relations attached.
     */
    public static function first(): ?array
    {
        $stmt = static::db()->query(
            'SELECT * FROM products WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
        );
        $product = $stmt->fetch();
        return $product ? self::withRelations($product) : null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = static::db()->prepare('SELECT * FROM products WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $product = $stmt->fetch();
        return $product ? self::withRelations($product) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function allActive(?int $limit = null): array
    {
        $sql = 'SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }
        return static::db()->query($sql)->fetchAll();
    }

    /**
     * Same as allActive() but with images/colors/sizes/specs attached to
     * every row. Fine for a small catalog (a handful of extra queries per
     * product); avoid calling this for large result sets.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function allActiveWithRelations(?int $limit = null): array
    {
        return array_map([self::class, 'withRelations'], self::allActive($limit));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function byCategory(int $categoryId): array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM products WHERE category_id = :cid AND is_active = 1 ORDER BY created_at DESC'
        );
        $stmt->execute(['cid' => $categoryId]);
        return $stmt->fetchAll();
    }

    /**
     * Full-text search across name + description.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function search(string $term): array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM products
             WHERE is_active = 1
               AND MATCH(name, description) AGAINST(:term IN NATURAL LANGUAGE MODE)
             ORDER BY created_at DESC'
        );
        $stmt->execute(['term' => $term]);
        $results = $stmt->fetchAll();

        // FULLTEXT needs a handful of matching rows to kick in well; for a
        // small catalog, fall back to a plain LIKE search when it finds nothing.
        if (empty($results)) {
            $stmt = static::db()->prepare(
                'SELECT * FROM products
                 WHERE is_active = 1 AND (name LIKE :term1 OR brand LIKE :term2)
                 ORDER BY created_at DESC'
            );
            $like = '%' . $term . '%';
            $stmt->execute(['term1' => $like, 'term2' => $like]);
            $results = $stmt->fetchAll();
        }

        return $results;
    }

    /**
     * @return array<int, array<string, mixed>> [{id, image_url, sort_order}, ...]
     */
    public static function getImages(int $productId): array
    {
        $stmt = static::db()->prepare(
            'SELECT id, image_url, sort_order FROM product_images
             WHERE product_id = :id ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['id' => $productId]);
        return $stmt->fetchAll();
    }

    /**
     * Appends one image at the end of the product's gallery. If this is
     * the product's only image and it's the generic placeholder used
     * when a product is first created, the placeholder is removed so
     * the first real upload replaces it instead of sitting alongside it.
     */
    public static function addImage(int $productId, string $imageUrl): int
    {
        $db = static::db();

        $existing = self::getImages($productId);
        if (count($existing) === 1 && self::isPlaceholderImage($existing[0]['image_url'])) {
            self::removeImage((int) $existing[0]['id']);
            $existing = [];
        }

        $nextSort = 0;
        foreach ($existing as $img) {
            $nextSort = max($nextSort, (int) $img['sort_order'] + 1);
        }

        $stmt = $db->prepare(
            'INSERT INTO product_images (product_id, image_url, sort_order) VALUES (:pid, :url, :sort)'
        );
        $stmt->execute(['pid' => $productId, 'url' => $imageUrl, 'sort' => $nextSort]);

        return (int) $db->lastInsertId();
    }

    /**
     * Deletes the DB row and returns the image_url that was removed
     * (so the caller can delete the on-disk file for locally-uploaded
     * images — never for external/seeded URLs).
     */
    public static function removeImage(int $imageId): ?string
    {
        $db = static::db();

        $stmt = $db->prepare('SELECT image_url FROM product_images WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $imageId]);
        $url = $stmt->fetchColumn();
        if ($url === false) {
            return null;
        }

        $stmt = $db->prepare('DELETE FROM product_images WHERE id = :id');
        $stmt->execute(['id' => $imageId]);

        return (string) $url;
    }

    public static function isPlaceholderImage(string $imageUrl): bool
    {
        return str_contains($imageUrl, 'pexels-photo-230544');
    }

    /**
     * Attach images/colors/sizes/specs to an already-fetched product row.
     */
    public static function withRelations(array $product): array
    {
        $db = static::db();
        $id = (int) $product['id'];

        $stmt = $db->prepare(
            'SELECT image_url FROM product_images WHERE product_id = :id ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['id' => $id]);
        $product['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $db->prepare(
            'SELECT color_name FROM product_colors WHERE product_id = :id ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['id' => $id]);
        $product['colors'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $db->prepare(
            'SELECT size_name FROM product_sizes WHERE product_id = :id ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['id' => $id]);
        $product['sizes'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $db->prepare(
            'SELECT spec_key, spec_value FROM product_specs
             WHERE product_id = :id ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['id' => $id]);
        $product['specs'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return $product;
    }

    /**
     * Create a product together with its images/colors/sizes/specs in
     * one call. $specs is an associative array: ['Display' => '6.6"...'].
     */
    public static function createFull(
        array $data,
        array $images = [],
        array $colors = [],
        array $sizes = [],
        array $specs = []
    ): int {
        $id = static::create($data);
        $db = static::db();

        $imgStmt = $db->prepare(
            'INSERT INTO product_images (product_id, image_url, sort_order) VALUES (:pid, :url, :sort)'
        );
        foreach (array_values($images) as $i => $url) {
            $imgStmt->execute(['pid' => $id, 'url' => $url, 'sort' => $i]);
        }

        $colorStmt = $db->prepare(
            'INSERT INTO product_colors (product_id, color_name, sort_order) VALUES (:pid, :name, :sort)'
        );
        foreach (array_values($colors) as $i => $name) {
            $colorStmt->execute(['pid' => $id, 'name' => $name, 'sort' => $i]);
        }

        $sizeStmt = $db->prepare(
            'INSERT INTO product_sizes (product_id, size_name, sort_order) VALUES (:pid, :name, :sort)'
        );
        foreach (array_values($sizes) as $i => $name) {
            $sizeStmt->execute(['pid' => $id, 'name' => $name, 'sort' => $i]);
        }

        $specStmt = $db->prepare(
            'INSERT INTO product_specs (product_id, spec_key, spec_value, sort_order)
             VALUES (:pid, :key, :value, :sort)'
        );
        $i = 0;
        foreach ($specs as $key => $value) {
            $specStmt->execute(['pid' => $id, 'key' => $key, 'value' => $value, 'sort' => $i]);
            $i++;
        }

        return $id;
    }

    /**
     * Replace a product's images/colors/sizes/specs wholesale (used by
     * the admin edit form later — simpler and less error-prone than
     * diffing individual rows for a small catalog).
     */
    public static function replaceRelations(
        int $productId,
        array $images,
        array $colors,
        array $sizes,
        array $specs
    ): void {
        $db = static::db();

        foreach (['product_images', 'product_colors', 'product_sizes', 'product_specs'] as $table) {
            $stmt = $db->prepare("DELETE FROM {$table} WHERE product_id = :id");
            $stmt->execute(['id' => $productId]);
        }

        $imgStmt = $db->prepare(
            'INSERT INTO product_images (product_id, image_url, sort_order) VALUES (:pid, :url, :sort)'
        );
        foreach (array_values($images) as $i => $url) {
            $imgStmt->execute(['pid' => $productId, 'url' => $url, 'sort' => $i]);
        }

        $colorStmt = $db->prepare(
            'INSERT INTO product_colors (product_id, color_name, sort_order) VALUES (:pid, :name, :sort)'
        );
        foreach (array_values($colors) as $i => $name) {
            $colorStmt->execute(['pid' => $productId, 'name' => $name, 'sort' => $i]);
        }

        $sizeStmt = $db->prepare(
            'INSERT INTO product_sizes (product_id, size_name, sort_order) VALUES (:pid, :name, :sort)'
        );
        foreach (array_values($sizes) as $i => $name) {
            $sizeStmt->execute(['pid' => $productId, 'name' => $name, 'sort' => $i]);
        }

        $specStmt = $db->prepare(
            'INSERT INTO product_specs (product_id, spec_key, spec_value, sort_order)
             VALUES (:pid, :key, :value, :sort)'
        );
        $i = 0;
        foreach ($specs as $key => $value) {
            $specStmt->execute(['pid' => $productId, 'key' => $key, 'value' => $value, 'sort' => $i]);
            $i++;
        }
    }
}
