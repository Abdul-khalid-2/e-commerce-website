<?php
/**
 * api/admin/messages.php
 *
 * POST action=mark-read     id -> { success }
 * POST action=mark-replied  id -> { success }
 * POST action=delete        id -> { success }
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\ContactMessage;

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
Csrf::requireValidJson($_POST['csrf_token'] ?? null);

$id = (int) ($_POST['id'] ?? 0);
if (!$id || !ContactMessage::find($id)) {
    respond(['success' => false, 'message' => 'Message not found'], 404);
}

try {
    if ($action === 'mark-read') {
        ContactMessage::updateStatus($id, 'Read');
        respond(['success' => true]);
    }

    if ($action === 'mark-replied') {
        ContactMessage::updateStatus($id, 'Replied');
        respond(['success' => true]);
    }

    if ($action === 'delete') {
        ContactMessage::delete($id);
        respond(['success' => true]);
    }

    respond(['success' => false, 'message' => 'Unknown action'], 400);
} catch (\Throwable $e) {
    error_log('[api/admin/messages] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong'], 500);
}
