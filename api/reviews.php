<?php
/**
 * api/reviews.php
 *
 * GET  action=list product_id       -> { reviews: [...] } (public)
 * POST action=create product_id, rating, comment  -> { success, review, rating, reviews_count }
 *      (requires login + CSRF)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Csrf;
use App\Models\Product;
use App\Models\Review;

header('Content-Type: application/json');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'list') {
    $productId = (int) ($_GET['product_id'] ?? 0);
    if (!$productId) {
        respond(['success' => false, 'message' => 'Invalid product'], 422);
    }
    respond(['reviews' => Review::forProduct($productId)]);
}

if ($action === 'create') {
    Csrf::requireValidJson($_POST['csrf_token'] ?? null);

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        respond(['success' => false, 'message' => 'Please log in to write a review'], 401);
    }

    $productId = (int) ($_POST['product_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim((string) ($_POST['comment'] ?? ''));

    if (!$productId || !Product::find($productId)) {
        respond(['success' => false, 'message' => 'Product not found'], 404);
    }
    if ($rating < 1 || $rating > 5) {
        respond(['success' => false, 'message' => 'Please select a rating from 1 to 5'], 422);
    }
    if ($comment === '' || mb_strlen($comment) < 5) {
        respond(['success' => false, 'message' => 'Please write a short review (at least 5 characters)'], 422);
    }
    if (mb_strlen($comment) > 1000) {
        respond(['success' => false, 'message' => 'Review is too long (max 1000 characters)'], 422);
    }

    try {
        Review::create($productId, (int) $userId, $rating, $comment);
    } catch (\RuntimeException $e) {
        respond(['success' => false, 'message' => $e->getMessage()], 409);
    } catch (\Throwable $e) {
        error_log('[api/reviews] ' . $e->getMessage());
        respond(['success' => false, 'message' => 'Something went wrong'], 500);
    }

    $product = Product::find($productId);
    $reviews = Review::forProduct($productId);

    respond([
        'success' => true,
        'reviews' => $reviews,
        'rating' => (float) $product['rating'],
        'reviews_count' => (int) $product['reviews_count'],
    ]);
}

respond(['success' => false, 'message' => 'Unknown action'], 400);
