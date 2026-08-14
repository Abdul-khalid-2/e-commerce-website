<?php
/**
 * api/admin/products.php
 *
 * POST action=save    id (optional), name, brand, category_id, price,
 *                      old_price (optional), stock, description
 *                      -> { success, id }
 * POST action=delete   id -> { success }
 *
 * Editing an existing product only touches its core fields (name,
 * brand, category, price, stock, description) — it never overwrites
 * that product's real images/colors/sizes/specs. New products get a
 * single placeholder image and default color/size until a proper
 * image-upload flow exists.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

use App\Core\Auth;
use App\Models\Product;

header('Content-Type: application/json');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (!Auth::isAdmin()) {
    respond(['success' => false, 'message' => 'Not authenticated'], 401);
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $brand = trim((string) ($_POST['brand'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0) ?: null;
        $price = (float) ($_POST['price'] ?? 0);
        $oldPrice = $_POST['old_price'] !== '' && isset($_POST['old_price']) ? (float) $_POST['old_price'] : null;
        $stock = max(0, (int) ($_POST['stock'] ?? 0));
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($name === '' || $price <= 0) {
            respond(['success' => false, 'message' => 'Please fill in the product name and a valid price'], 422);
        }

        $data = [
            'name' => $name,
            'brand' => $brand ?: null,
            'category_id' => $categoryId,
            'price' => $price,
            'old_price' => $oldPrice,
            'stock' => $stock,
            'description' => $description ?: null,
        ];

        if ($id > 0) {
            if (!Product::find($id)) {
                respond(['success' => false, 'message' => 'Product not found'], 404);
            }
            Product::update($id, $data);
            respond(['success' => true, 'id' => $id]);
        }

        $data['slug'] = \App\Core\Str::slug($name) . '-' . substr((string) time(), -5);
        $data['rating'] = 4.0;
        $data['reviews_count'] = 0;
        $data['is_active'] = 1;

        $newId = Product::createFull(
            $data,
            ['https://images.pexels.com/photos/230544/pexels-photo-230544.jpeg?auto=compress&cs=tinysrgb&h=650&w=940'],
            ['Default'],
            ['Standard'],
            []
        );
        respond(['success' => true, 'id' => $newId]);
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0 || !Product::find($id)) {
            respond(['success' => false, 'message' => 'Product not found'], 404);
        }
        Product::delete($id);
        respond(['success' => true]);
    }

    respond(['success' => false, 'message' => 'Unknown action'], 400);
} catch (\Throwable $e) {
    error_log('[api/admin/products] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong'], 500);
}
