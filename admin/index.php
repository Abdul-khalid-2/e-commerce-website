<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;

Auth::requireAdmin();

$pageTitle = 'Dashboard - Admin';
$activeSection = 'dashboard';

$db = Database::getConnection();

$totalSales = (float) $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'Cancelled'")->fetchColumn();
$totalOrders = (int) $db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalCustomers = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalProducts = (int) $db->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();

$recentOrders = $db->query(
    'SELECT order_number, customer_name, total, status, created_at FROM orders ORDER BY created_at DESC LIMIT 6'
)->fetchAll();

$topCategories = $db->query(
    "SELECT c.name, COALESCE(SUM(oi.qty * oi.price), 0) AS revenue
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     LEFT JOIN order_items oi ON oi.product_id = p.id
     GROUP BY c.id, c.name
     ORDER BY revenue DESC
     LIMIT 5"
)->fetchAll();
$revenues = array_map(fn($c) => (float) $c['revenue'], $topCategories);
$revenues[] = 0.0;
$maxRevenue = max(1.0, ...$revenues);

$salesByDay = $db->query(
    "SELECT DATE(created_at) AS day, SUM(total) AS total
     FROM orders
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status != 'Cancelled'
     GROUP BY DATE(created_at)
     ORDER BY day ASC"
)->fetchAll();
$salesByDayMap = array_column($salesByDay, 'total', 'day');
$chartLabels = [];
$chartValues = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $chartLabels[] = date('D', strtotime($day));
    $chartValues[] = (float) ($salesByDayMap[$day] ?? 0);
}

require __DIR__ . '/../includes/admin-header.php';
?>
      <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
          <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:var(--brand-light);color:var(--brand);"><i class="bi bi-currency-rupee"></i></div>
            <div><div class="stat-value">Rs. <?= number_format($totalSales) ?></div><div class="stat-label">Total Sales</div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#DBEAFE;color:#2563EB;"><i class="bi bi-bag-check"></i></div>
            <div><div class="stat-value"><?= $totalOrders ?></div><div class="stat-label">Total Orders</div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#D1FAE5;color:#059669;"><i class="bi bi-people"></i></div>
            <div><div class="stat-value"><?= $totalCustomers ?></div><div class="stat-label">Customers</div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#FEF3C7;color:#D97706;"><i class="bi bi-box-seam"></i></div>
            <div><div class="stat-value"><?= $totalProducts ?></div><div class="stat-label">Products</div></div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-8">
          <div class="stat-card">
            <h6 class="fw-700 mb-3">Sales - Last 7 Days</h6>
            <canvas id="salesChart" height="90"></canvas>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="stat-card">
            <h6 class="fw-700 mb-3">Revenue by Category</h6>
<?php if (empty(array_filter($topCategories, fn($c) => (float) $c['revenue'] > 0))): ?>
            <p class="text-muted-2 fs-7 mb-0">No sales yet.</p>
<?php else: foreach ($topCategories as $c): ?>
            <div class="mb-2">
              <div class="d-flex justify-content-between fs-7 mb-1"><span><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></span><span class="fw-600">Rs. <?= number_format((float) $c['revenue']) ?></span></div>
              <div style="height:6px;background:var(--bg);border-radius:3px;"><div style="width:<?= round(((float) $c['revenue'] / $maxRevenue) * 100) ?>%;height:100%;background:var(--brand);border-radius:3px;"></div></div>
            </div>
<?php endforeach; endif; ?>
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-700 mb-0">Recent Orders</h6>
          <a href="orders.php" class="btn btn-sm text-brand">View All</a>
        </div>
<?php if (empty($recentOrders)): ?>
        <p class="text-muted-2 fs-7 mb-0">No orders yet.</p>
<?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>Order #</th><th>Customer</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
<?php foreach ($recentOrders as $o): ?>
              <tr>
                <td class="fw-600"><a href="orders.php?number=<?= urlencode($o['order_number']) ?>"><?= htmlspecialchars($o['order_number'], ENT_QUOTES, 'UTF-8') ?></a></td>
                <td><?= htmlspecialchars($o['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="fs-7 text-muted-2"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                <td>Rs. <?= number_format((float) $o['total']) ?></td>
                <td><span class="status-badge status-<?= htmlspecialchars($o['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($o['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
<?php endif; ?>
      </div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
          labels: <?= json_encode($chartLabels) ?>,
          datasets: [{
            label: 'Sales (Rs.)',
            data: <?= json_encode($chartValues) ?>,
            borderColor: '#16A34A',
            backgroundColor: 'rgba(22,163,74,0.1)',
            tension: 0.35,
            fill: true,
          }],
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
      });
    </script>
  </body>
</html>
