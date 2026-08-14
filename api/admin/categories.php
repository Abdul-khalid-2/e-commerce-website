<?php
/**
 * api/admin/categories.php
 *
 * POST action=save   id (optional), name, icon, image, active (0/1)
 *                     -> { success, id }
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Str;
use App\Models\Category;

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
if ($action === 'save') {
    Csrf::requireValidJson($_POST['csrf_token'] ?? null);
}

try {
    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $icon = trim((string) ($_POST['icon'] ?? '')) ?: 'bi-grid';
        $image = trim((string) ($_POST['image'] ?? ''))
            ?: 'https://images.pexels.com/photos/230544/pexels-photo-230544.jpeg?auto=compress&cs=tinysrgb&h=400&w=400';
        $active = ((string) ($_POST['active'] ?? '1')) === '1' ? 1 : 0;

        if ($name === '') {
            respond(['success' => false, 'message' => 'Please enter a category name'], 422);
        }

        if ($id > 0) {
            if (!Category::find($id)) {
                respond(['success' => false, 'message' => 'Category not found'], 404);
            }
            Category::update($id, [
                'name' => $name,
                'icon' => $icon,
                'image' => $image,
                'is_active' => $active,
            ]);
            respond(['success' => true, 'id' => $id]);
        }

        $newId = Category::create([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . substr((string) time(), -5),
            'icon' => $icon,
            'image' => $image,
            'is_active' => $active,
            'sort_order' => Category::count(),
        ]);
        respond(['success' => true, 'id' => $newId]);
    }

    respond(['success' => false, 'message' => 'Unknown action'], 400);
} catch (\Throwable $e) {
    error_log('[api/admin/categories] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong'], 500);
}
