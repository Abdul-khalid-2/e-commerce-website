<?php
/**
 * api/admin/product-images.php
 *
 * GET  action=list   product_id            -> { images: [...] }
 * POST action=upload product_id, images[]  -> { success, images }
 * POST action=delete image_id              -> { success, images }
 *
 * Uploaded files are validated by their actual content (finfo reads the
 * real magic bytes, not the filename extension or the browser-reported
 * Content-Type — both of those can be spoofed), renamed to a random
 * filename before being written to disk, and stored under
 * assets/img/products/. Image URLs are saved as root-absolute paths
 * (leading slash) so they resolve correctly from both root-level pages
 * and one-level-deep admin pages alike.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
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

const MAX_FILE_BYTES = 5 * 1024 * 1024; // 5MB
const ALLOWED_MIME_TO_EXT = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];
const MAX_IMAGES_PER_PRODUCT = 10;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (in_array($action, ['upload', 'delete'], true)) {
    Csrf::requireValidJson($_POST['csrf_token'] ?? null);
}

try {
    if ($action === 'list') {
        $productId = (int) ($_GET['product_id'] ?? 0);
        if (!$productId || !Product::find($productId)) {
            respond(['success' => false, 'message' => 'Product not found'], 404);
        }
        respond(['images' => Product::getImages($productId)]);
    }

    if ($action === 'upload') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        if (!$productId || !Product::find($productId)) {
            respond(['success' => false, 'message' => 'Product not found'], 404);
        }

        $existingCount = count(Product::getImages($productId));
        $files = $_FILES['images'] ?? null;
        if (!$files || empty($files['tmp_name'])) {
            respond(['success' => false, 'message' => 'No files received'], 422);
        }

        $uploadDir = dirname(__DIR__, 2) . '/assets/img/products';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            respond(['success' => false, 'message' => 'Upload directory is not writable'], 500);
        }

        $count = is_array($files['tmp_name']) ? count($files['tmp_name']) : 1;
        $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
        $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];

        $uploaded = 0;
        $skipped = [];

        for ($i = 0; $i < $count; $i++) {
            if ($existingCount + $uploaded >= MAX_IMAGES_PER_PRODUCT) {
                $skipped[] = 'Limit of ' . MAX_IMAGES_PER_PRODUCT . ' images per product reached';
                break;
            }
            if ($errors[$i] !== UPLOAD_ERR_OK) {
                $skipped[] = 'Upload error';
                continue;
            }
            if ($sizes[$i] > MAX_FILE_BYTES) {
                $skipped[] = 'File too large (max 5MB)';
                continue;
            }
            if (!is_uploaded_file($tmpNames[$i])) {
                $skipped[] = 'Invalid upload';
                continue;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpNames[$i]);
            finfo_close($finfo);

            if (!isset(ALLOWED_MIME_TO_EXT[$mime])) {
                $skipped[] = 'Unsupported file type (jpg/png/webp/gif only)';
                continue;
            }

            $ext = ALLOWED_MIME_TO_EXT[$mime];
            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
            $destination = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($tmpNames[$i], $destination)) {
                $skipped[] = 'Could not save file';
                continue;
            }

            Product::addImage($productId, '/assets/img/products/' . $filename);
            $uploaded++;
        }

        if ($uploaded === 0) {
            respond(['success' => false, 'message' => $skipped[0] ?? 'No images were uploaded'], 422);
        }

        respond([
            'success' => true,
            'uploaded' => $uploaded,
            'skipped' => $skipped,
            'images' => Product::getImages($productId),
        ]);
    }

    if ($action === 'delete') {
        $imageId = (int) ($_POST['image_id'] ?? 0);
        $productId = (int) ($_POST['product_id'] ?? 0);

        $url = Product::removeImage($imageId);
        if ($url === null) {
            respond(['success' => false, 'message' => 'Image not found'], 404);
        }

        // Only unlink files we actually uploaded ourselves (root-absolute
        // /assets/img/products/... paths) — never touch external/seeded URLs.
        if (str_starts_with($url, '/assets/img/products/')) {
            $realUploadDir = realpath(dirname(__DIR__, 2) . '/assets/img/products');
            $realPath = realpath(dirname(__DIR__, 2) . $url);
            if ($realPath && $realUploadDir && str_starts_with($realPath, $realUploadDir)) {
                @unlink($realPath);
            }
        }

        respond(['success' => true, 'images' => Product::getImages($productId)]);
    }

    respond(['success' => false, 'message' => 'Unknown action'], 400);
} catch (\Throwable $e) {
    error_log('[api/admin/product-images] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong'], 500);
}
