<?php
/**
 * api/admin/orders.php
 *
 * POST action=update-status   order_number, status -> { success }
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Models\Order;

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
    if ($action === 'update-status') {
        $orderNumber = trim((string) ($_POST['order_number'] ?? ''));
        $status = (string) ($_POST['status'] ?? '');

        $validStatuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled'];
        if (!in_array($status, $validStatuses, true)) {
            respond(['success' => false, 'message' => 'Invalid status'], 422);
        }

        $order = Order::findByNumber($orderNumber);
        if (!$order) {
            respond(['success' => false, 'message' => 'Order not found'], 404);
        }

        $stmt = Database::getConnection()->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $order['id']]);

        respond(['success' => true]);
    }

    respond(['success' => false, 'message' => 'Unknown action'], 400);
} catch (\Throwable $e) {
    error_log('[api/admin/orders] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong'], 500);
}
