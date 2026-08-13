<?php
/**
 * api/cart.php
 *
 * JSON endpoint backing the cart. The cart itself is identified by the
 * PHP session (see App\Models\Cart), so this works for guests with no
 * login required, exactly like the old localStorage cart did.
 *
 * GET  ?action=count                              -> { count }
 * GET  ?action=items                               -> { items, count, subtotal }
 * POST action=add    product_id, qty, color, size  -> { success, count, subtotal }
 * POST action=update item_id, qty                  -> { success, count, subtotal }
 * POST action=remove item_id                       -> { success, count, subtotal }
 * POST action=clear                                -> { success, count, subtotal }
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Models\Cart;
use App\Models\Product;

header('Content-Type: application/json');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
$sessionId = session_id();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'count') {
        $cart = Cart::peekForSession($sessionId, $userId);
        $count = $cart ? Cart::getItemCount((int) $cart['id']) : 0;
        respond(['count' => $count]);
    }

    if ($action === 'items') {
        $cart = Cart::peekForSession($sessionId, $userId);
        if (!$cart) {
            respond(['items' => [], 'count' => 0, 'subtotal' => 0]);
        }
        $cartId = (int) $cart['id'];
        respond([
            'items' => Cart::getItemsWithProduct($cartId),
            'count' => Cart::getItemCount($cartId),
            'subtotal' => Cart::getSubtotal($cartId),
        ]);
    }

    if ($action === 'add') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $qty = max(1, (int) ($_POST['qty'] ?? 1));
        $color = trim((string) ($_POST['color'] ?? '')) ?: null;
        $size = trim((string) ($_POST['size'] ?? '')) ?: null;

        if ($productId <= 0 || !Product::find($productId)) {
            respond(['success' => false, 'message' => 'Product not found'], 404);
        }

        $cart = Cart::current($sessionId, $userId);
        Cart::addItem((int) $cart['id'], $productId, $qty, $color, $size);

        $cartId = (int) $cart['id'];
        respond([
            'success' => true,
            'count' => Cart::getItemCount($cartId),
            'subtotal' => Cart::getSubtotal($cartId),
        ]);
    }

    if ($action === 'update') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $qty = (int) ($_POST['qty'] ?? 1);

        $cart = Cart::peekForSession($sessionId, $userId);
        if (!$cart) {
            respond(['success' => false, 'message' => 'Cart not found'], 404);
        }
        $cartId = (int) $cart['id'];
        Cart::updateItemQty($cartId, $itemId, $qty);

        respond([
            'success' => true,
            'count' => Cart::getItemCount($cartId),
            'subtotal' => Cart::getSubtotal($cartId),
        ]);
    }

    if ($action === 'remove') {
        $itemId = (int) ($_POST['item_id'] ?? 0);

        $cart = Cart::peekForSession($sessionId, $userId);
        if (!$cart) {
            respond(['success' => false, 'message' => 'Cart not found'], 404);
        }
        $cartId = (int) $cart['id'];
        Cart::removeItem($cartId, $itemId);

        respond([
            'success' => true,
            'count' => Cart::getItemCount($cartId),
            'subtotal' => Cart::getSubtotal($cartId),
        ]);
    }

    if ($action === 'clear') {
        $cart = Cart::peekForSession($sessionId, $userId);
        if ($cart) {
            Cart::clear((int) $cart['id']);
        }
        respond(['success' => true, 'count' => 0, 'subtotal' => 0]);
    }

    respond(['success' => false, 'message' => 'Unknown action'], 400);
} catch (\Throwable $e) {
    error_log('[api/cart] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong'], 500);
}
