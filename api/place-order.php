<?php
/**
 * api/place-order.php
 *
 * POST-only. Reads the current session's cart server-side (never trusts
 * cart contents or totals sent by the client), creates the order +
 * order_items, clears the cart, and remembers the order number in the
 * session so orders.php can show it under "Recent Orders" without
 * requiring login.
 *
 * POST fields: customer_name, customer_phone, customer_email (optional),
 * shipping_address, city, postal_code (optional), payment_method, notes
 * (optional, not persisted — no column for it yet).
 *
 * Response: { success, order_number } or { success: false, message }
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Csrf;
use App\Models\Cart;
use App\Models\Order;

header('Content-Type: application/json');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Method not allowed'], 405);
}

Csrf::requireValidJson($_POST['csrf_token'] ?? null);

$name = trim((string) ($_POST['customer_name'] ?? ''));
$phone = trim((string) ($_POST['customer_phone'] ?? ''));
$email = trim((string) ($_POST['customer_email'] ?? '')) ?: null;
$address = trim((string) ($_POST['shipping_address'] ?? ''));
$city = trim((string) ($_POST['city'] ?? ''));
$postal = trim((string) ($_POST['postal_code'] ?? '')) ?: null;
$paymentMethod = (string) ($_POST['payment_method'] ?? 'cod');

$validPaymentMethods = ['cod', 'jazzcash', 'easypaisa', 'bank_transfer', 'card'];
if (!in_array($paymentMethod, $validPaymentMethods, true)) {
    $paymentMethod = 'cod';
}

if ($name === '' || $phone === '' || $address === '' || $city === '') {
    respond(['success' => false, 'message' => 'Please fill all required fields'], 422);
}
if (!preg_match('/^(03\d{2}-?\d{7}|\+92\d{10})$/', str_replace(' ', '', $phone))) {
    respond(['success' => false, 'message' => 'Please enter a valid Pakistani phone number'], 422);
}

$userId = $_SESSION['user_id'] ?? null;
$sessionId = session_id();

try {
    $cart = Cart::peekForSession($sessionId, $userId);
    if (!$cart) {
        respond(['success' => false, 'message' => 'Your cart is empty'], 422);
    }

    $cartId = (int) $cart['id'];
    $items = Cart::getItemsWithProduct($cartId);
    if (empty($items)) {
        respond(['success' => false, 'message' => 'Your cart is empty'], 422);
    }

    $subtotal = Cart::getSubtotal($cartId);
    $shippingFee = $subtotal > 2000 ? 0.0 : 200.0;
    $total = $subtotal + $shippingFee;

    $result = Order::createFromCart([
        'user_id' => $userId,
        'customer_name' => $name,
        'customer_email' => $email,
        'customer_phone' => $phone,
        'shipping_address' => $address,
        'city' => $city,
        'postal_code' => $postal,
        'payment_method' => $paymentMethod,
        'subtotal' => $subtotal,
        'shipping_fee' => $shippingFee,
        'total' => $total,
    ], $items);

    Cart::clear($cartId);

    $_SESSION['recent_orders'] = $_SESSION['recent_orders'] ?? [];
    array_unshift($_SESSION['recent_orders'], $result['order_number']);
    $_SESSION['recent_orders'] = array_slice(array_unique($_SESSION['recent_orders']), 0, 10);

    respond(['success' => true, 'order_number' => $result['order_number']]);
} catch (\Throwable $e) {
    error_log('[api/place-order] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Could not place order. Please try again.'], 500);
}
