<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

use App\Models\Order;

$pageTitle = 'Track Your Orders - ShopMate Pakistan';
$activePage = 'orders';
$basePath = '';

$userId = $_SESSION['user_id'] ?? null;
$sessionOrderNumbers = $_SESSION['recent_orders'] ?? [];

$recentOrders = Order::findByNumbers($sessionOrderNumbers);
if ($userId) {
    $accountOrders = Order::recentByUser((int) $userId, 10);
    $seen = array_column($recentOrders, 'order_number');
    foreach ($accountOrders as $o) {
        if (!in_array($o['order_number'], $seen, true)) {
            $recentOrders[] = $o;
        }
    }
    usort($recentOrders, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
}

$requestedNumber = trim((string) ($_GET['number'] ?? ''));
$order = $requestedNumber !== '' ? Order::findByNumber($requestedNumber) : null;
if (!$order && $requestedNumber === '' && !empty($recentOrders)) {
    $order = $recentOrders[0];
}

$orderItems = $order ? Order::getItems((int) $order['id']) : [];

$orderStatuses = ['Order Placed', 'Confirmed', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered'];
$statusIndex = ['Pending' => 1, 'Shipped' => 3, 'Delivered' => 5, 'Cancelled' => 0];

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

    <div class="container section-pad pb-3">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-custom">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Track Order</li>
        </ol>
      </nav>
      <h1 class="section-title mb-0">Track Your Orders</h1>
    </div>

    <div class="container pb-5">
      <div class="row g-4">
        <div class="col-lg-4" data-aos="fade-up">
          <div class="summary-card">
            <h5 class="fw-700 mb-3">Find Your Order</h5>
            <form method="GET" action="orders.php">
              <div class="mb-3"><label class="form-label">Order Number</label><input type="text" class="form-control" name="number" placeholder="e.g. ORD-260001" value="<?= htmlspecialchars($requestedNumber, ENT_QUOTES, 'UTF-8') ?>"></div>
              <button class="btn-brand w-100" type="submit">Track Order</button>
            </form>
<?php if ($requestedNumber !== '' && !$order): ?>
            <div class="alert alert-warning py-2 fs-7 mt-3 mb-0"><i class="bi bi-exclamation-circle me-1"></i> No order found with that number.</div>
<?php endif; ?>
<?php if (!empty($recentOrders)): ?>
            <hr class="border-soft my-3">
            <h6 class="fw-700 mb-2">Recent Orders</h6>
            <div id="orderList">
<?php foreach (array_slice($recentOrders, 0, 5) as $o): ?>
              <a href="orders.php?number=<?= urlencode($o['order_number']) ?>" class="d-flex justify-content-between align-items-center p-2 rounded mb-1 text-decoration-none" style="background:var(--bg);">
                <div><div class="fw-600 fs-7" style="color:var(--text);"><?= htmlspecialchars($o['order_number'], ENT_QUOTES, 'UTF-8') ?></div><small class="text-muted-2"><?= date('d M Y', strtotime($o['created_at'])) ?></small></div>
                <span class="status-badge status-<?= htmlspecialchars($o['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($o['status'], ENT_QUOTES, 'UTF-8') ?></span>
              </a>
<?php endforeach; ?>
            </div>
<?php endif; ?>
          </div>
        </div>
        <div class="col-lg-8" data-aos="fade-up" data-aos-delay="100">
<?php if (!$order): ?>
          <div class="empty-state">
            <i class="bi bi-truck"></i>
            <h4>No order to show yet</h4>
            <p class="text-muted-2">Enter an order number on the left to track it, or place your first order.</p>
            <a href="shop.php" class="btn-brand mt-2">Start Shopping</a>
          </div>
<?php else:
            $statusIdx = $statusIndex[$order['status']] ?? 2;
?>
          <div class="summary-card mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div>
                <h4 class="fw-700 mb-0"><?= htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8') ?></h4>
                <small class="text-muted-2">Placed on <?= date('d M Y, g:i A', strtotime($order['created_at'])) ?></small>
              </div>
              <span class="status-badge status-<?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?> fs-6"><?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          </div>
          <div class="summary-card">
            <h5 class="fw-700 mb-4">Order Timeline</h5>
            <div class="timeline">
<?php foreach ($orderStatuses as $i => $s): ?>
              <div class="timeline-item <?= $i < $statusIdx ? 'completed' : '' ?> <?= $i === $statusIdx ? 'active' : '' ?>">
                <div class="timeline-dot"><i class="bi <?= $i < $statusIdx ? 'bi-check' : ($i === $statusIdx ? 'bi-clock' : 'bi-circle') ?>"></i></div>
                <div>
                  <h6 class="fw-600 mb-0 <?= $i <= $statusIdx ? '' : 'text-muted-2' ?>"><?= $s ?></h6>
                  <small class="text-muted-2"><?= $i < $statusIdx ? 'Completed' : ($i === $statusIdx ? 'In progress' : 'Pending') ?></small>
                </div>
              </div>
<?php endforeach; ?>
            </div>
          </div>
          <div class="summary-card mt-3">
            <h5 class="fw-700 mb-3">Order Items</h5>
<?php foreach ($orderItems as $item): ?>
            <div class="d-flex gap-2 align-items-center py-2 border-soft border-bottom">
              <img src="<?= htmlspecialchars($item['image'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="width:50px;height:50px;border-radius:8px;object-fit:cover;">
              <div class="flex-grow-1"><div class="fs-7 fw-600"><?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?></div><small class="text-muted-2">Qty: <?= (int) $item['qty'] ?><?= $item['color'] ? ' · ' . htmlspecialchars($item['color'], ENT_QUOTES, 'UTF-8') : '' ?><?= $item['size'] ? ' · ' . htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8') : '' ?></small></div>
              <div class="fs-7 fw-600 text-brand"><?= number_format((float) $item['price'] * (int) $item['qty']) ?> Rs.</div>
            </div>
<?php endforeach; ?>
            <div class="d-flex justify-content-between mt-3"><span class="fw-600">Total</span><span class="fw-700 text-brand fs-5">Rs. <?= number_format((float) $order['total']) ?></span></div>
          </div>
<?php endif; ?>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/common.js"></script>
    <script>
      initCommonPhp();
    </script>
  </body>
</html>
