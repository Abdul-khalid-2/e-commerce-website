<?php
/**
 * api/wishlist.php
 *
 * Logged-in users only — guests keep using localStorage client-side
 * (see assets/js/common.js). Every action here requires
 * $_SESSION['user_id']; without it every action responds 401 so the
 * client-side code knows to fall back to localStorage.
 *
 * GET  ?action=list                    -> { ids: [...] }
 * POST action=add     product_id       -> { success, count }
 * POST action=remove  product_id       -> { success, count }
 * POST action=sync     ids (csv)        -> merges a guest's localStorage
 *                                          wishlist into the account,
 *                                          -> { success, ids }
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Csrf;
use App\Models\Wishlist;

header('Content-Type: application/json');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    respond(['success' => false, 'message' => 'Not logged in'], 401);
}
$userId = (int) $userId;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (in_array($action, ['add', 'remove', 'sync'], true)) {
    Csrf::requireValidJson($_POST['csrf_token'] ?? null);
}

try {
    if ($action === 'list') {
        respond(['ids' => Wishlist::getProductIds($userId)]);
    }

    if ($action === 'add') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        if ($productId <= 0) {
            respond(['success' => false, 'message' => 'Invalid product'], 422);
        }
        Wishlist::add($userId, $productId);
        respond(['success' => true, 'count' => Wishlist::count($userId)]);
    }

    if ($action === 'remove') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        Wishlist::remove($userId, $productId);
        respond(['success' => true, 'count' => Wishlist::count($userId)]);
    }

    if ($action === 'sync') {
        $ids = array_filter(array_map('intval', explode(',', (string) ($_POST['ids'] ?? ''))));
        Wishlist::mergeGuestIds($userId, $ids);
        respond(['success' => true, 'ids' => Wishlist::getProductIds($userId)]);
    }

    respond(['success' => false, 'message' => 'Unknown action'], 400);
} catch (\Throwable $e) {
    error_log('[api/wishlist] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong'], 500);
}
