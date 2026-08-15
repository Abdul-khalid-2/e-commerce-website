<?php
/**
 * api/contact.php
 *
 * POST action=submit  name, email, subject, message  -> { success }
 * Public (no login required), CSRF-protected.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Csrf;
use App\Models\ContactMessage;

header('Content-Type: application/json');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'submit') {
    respond(['success' => false, 'message' => 'Unknown action'], 400);
}

Csrf::requireValidJson($_POST['csrf_token'] ?? null);

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    respond(['success' => false, 'message' => 'Please fill in all fields'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(['success' => false, 'message' => 'Please enter a valid email address'], 422);
}
if (mb_strlen($message) < 10) {
    respond(['success' => false, 'message' => 'Please write a bit more detail in your message'], 422);
}
if (mb_strlen($message) > 5000) {
    respond(['success' => false, 'message' => 'Message is too long (max 5000 characters)'], 422);
}
if (mb_strlen($name) > 150 || mb_strlen($subject) > 200) {
    respond(['success' => false, 'message' => 'Name or subject is too long'], 422);
}

try {
    ContactMessage::submit($name, $email, $subject, $message);
    respond(['success' => true]);
} catch (\Throwable $e) {
    error_log('[api/contact] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Something went wrong, please try again'], 500);
}
