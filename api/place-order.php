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
use App\Core\Mailer;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Setting;

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

    // Respond to the customer immediately — email is sent *after* the
    // response is flushed, so a slow or unreachable SMTP server can
    // never add latency to checkout. fastcgi_finish_request() (PHP-FPM)
    // does this properly; other SAPIs (e.g. the built-in dev server,
    // plain mod_php) fall back to a manual flush, which is weaker but
    // still returns the response before the mail attempt starts.
    http_response_code(200);
    echo json_encode(['success' => true, 'order_number' => $result['order_number']]);

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
    }

    try {
        send_order_emails(
            $result['order_number'],
            $name,
            $email,
            $address,
            $city,
            $paymentMethod,
            $items,
            $subtotal,
            $shippingFee,
            $total
        );
    } catch (\Throwable $e) {
        error_log('[api/place-order] Email failed for ' . $result['order_number'] . ': ' . $e->getMessage());
    }

    exit;
} catch (\Throwable $e) {
    error_log('[api/place-order] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Could not place order. Please try again.'], 500);
}

/**
 * Sends the customer confirmation (if they gave an email) and an admin
 * notification to the store's own contact email. Both are best-effort.
 *
 * @param array<int, array<string, mixed>> $items From Cart::getItemsWithProduct()
 */
function send_order_emails(
    string $orderNumber,
    string $customerName,
    ?string $customerEmail,
    string $address,
    string $city,
    string $paymentMethod,
    array $items,
    float $subtotal,
    float $shippingFee,
    float $total
): void {
    $paymentLabels = [
        'cod' => 'Cash on Delivery',
        'jazzcash' => 'JazzCash',
        'easypaisa' => 'EasyPaisa',
        'bank_transfer' => 'Bank Transfer',
        'card' => 'Credit/Debit Card',
    ];
    $paymentLabel = $paymentLabels[$paymentMethod] ?? $paymentMethod;
    $storeName = Setting::get('store_name', 'ShopMate Pakistan');

    $safeName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
    $safeAddress = htmlspecialchars($address, ENT_QUOTES, 'UTF-8');
    $safeCity = htmlspecialchars($city, ENT_QUOTES, 'UTF-8');
    $safeOrderNumber = htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8');
    $safeStoreName = htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8');

    $itemsHtml = '';
    foreach ($items as $item) {
        $lineTotal = (float) $item['price'] * (int) $item['qty'];
        $itemsHtml .= sprintf(
            '<tr><td style="padding:8px;border-bottom:1px solid #eee;">%s</td>'
            . '<td style="padding:8px;border-bottom:1px solid #eee;text-align:center;">%d</td>'
            . '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">Rs. %s</td></tr>',
            htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'),
            (int) $item['qty'],
            number_format($lineTotal)
        );
    }

    $summaryHtml = <<<HTML
        <table style="width:100%;border-collapse:collapse;margin:16px 0;">
          <thead>
            <tr style="background:#f5f5f5;">
              <th style="padding:8px;text-align:left;">Item</th>
              <th style="padding:8px;text-align:center;">Qty</th>
              <th style="padding:8px;text-align:right;">Price</th>
            </tr>
          </thead>
          <tbody>{$itemsHtml}</tbody>
        </table>
        <table style="width:100%;">
          <tr><td>Subtotal</td><td style="text-align:right;">Rs. {$subtotal}</td></tr>
          <tr><td>Shipping</td><td style="text-align:right;">{shipping}</td></tr>
          <tr style="font-weight:bold;"><td>Total</td><td style="text-align:right;">Rs. {$total}</td></tr>
        </table>
        HTML;
    $summaryHtml = str_replace(
        ['{$subtotal}', '{shipping}', '{$total}'],
        [number_format($subtotal), $shippingFee === 0.0 ? 'FREE' : ('Rs. ' . number_format($shippingFee)), number_format($total)],
        $summaryHtml
    );

    if ($customerEmail) {
        $customerHtml = <<<HTML
            <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">
              <h2 style="color:#16A34A;">Thank you for your order, {$safeName}!</h2>
              <p>Your order <strong>{$safeOrderNumber}</strong> has been placed successfully.</p>
              {$summaryHtml}
              <p><strong>Shipping to:</strong><br>{$safeAddress}, {$safeCity}</p>
              <p><strong>Payment method:</strong> {$paymentLabel}</p>
              <p>We'll notify you as your order moves through processing. You can track it anytime using your order number.</p>
              <p style="color:#888;font-size:13px;margin-top:24px;">— {$safeStoreName}</p>
            </div>
            HTML;
        Mailer::send($customerEmail, $customerName, "Order Confirmed — {$orderNumber}", $customerHtml);
    }

    $adminEmail = Setting::get('contact_email');
    if ($adminEmail) {
        $adminHtml = <<<HTML
            <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">
              <h2>New order received: {$safeOrderNumber}</h2>
              <p><strong>Customer:</strong> {$safeName}<br>
                 <strong>Delivery:</strong> {$safeAddress}, {$safeCity}<br>
                 <strong>Payment:</strong> {$paymentLabel}</p>
              {$summaryHtml}
            </div>
            HTML;
        Mailer::send($adminEmail, $storeName, "New Order — {$orderNumber}", $adminHtml);
    }
}
