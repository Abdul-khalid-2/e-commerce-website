<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;

Auth::requireAdmin();

$pageTitle = 'Orders - Admin';
$activeSection = 'orders';

$statusFilter = $_GET['status'] ?? '';
$validStatuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled'];

$db = Database::getConnection();
if (in_array($statusFilter, $validStatuses, true)) {
    $stmt = $db->prepare('SELECT * FROM orders WHERE status = :status ORDER BY created_at DESC');
    $stmt->execute(['status' => $statusFilter]);
} else {
    $stmt = $db->query('SELECT * FROM orders ORDER BY created_at DESC');
}
$orders = $stmt->fetchAll();

require __DIR__ . '/../includes/admin-header.php';
?>
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <p class="text-muted-2 mb-0"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?></p>
        <div class="d-flex gap-2">
          <a href="orders.php" class="btn btn-sm <?= $statusFilter === '' ? 'btn-brand' : 'btn-light' ?>">All</a>
<?php foreach ($validStatuses as $s): ?>
          <a href="orders.php?status=<?= urlencode($s) ?>" class="btn btn-sm <?= $statusFilter === $s ? 'btn-brand' : 'btn-light' ?>"><?= $s ?></a>
<?php endforeach; ?>
        </div>
      </div>

      <div class="stat-card p-0">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead><tr><th>Order #</th><th>Customer</th><th>City</th><th>Payment</th><th>Total</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
<?php if (empty($orders)): ?>
              <tr><td colspan="7" class="text-center text-muted-2 py-4">No orders found.</td></tr>
<?php endif; ?>
<?php foreach ($orders as $o): ?>
              <tr>
                <td class="fw-600"><?= htmlspecialchars($o['order_number'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($o['customer_name'], ENT_QUOTES, 'UTF-8') ?><br><small class="text-muted-2"><?= htmlspecialchars($o['customer_phone'], ENT_QUOTES, 'UTF-8') ?></small></td>
                <td><?= htmlspecialchars($o['city'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="fs-7 text-muted-2"><?= htmlspecialchars(str_replace('_', ' ', $o['payment_method']), ENT_QUOTES, 'UTF-8') ?></td>
                <td>Rs. <?= number_format((float) $o['total']) ?></td>
                <td class="fs-7 text-muted-2"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                <td>
                  <select class="form-select form-select-sm status-select" data-order="<?= htmlspecialchars($o['order_number'], ENT_QUOTES, 'UTF-8') ?>" onchange="updateStatus(this)">
<?php foreach ($validStatuses as $s): ?>
                    <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
<?php endforeach; ?>
                  </select>
                </td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
    <script>
      async function updateStatus(select) {
        const orderNumber = select.dataset.order;
        const status = select.value;
        const res = await fetch('../api/admin/orders.php', {
          method: 'POST',
          body: new URLSearchParams({ action: 'update-status', order_number: orderNumber, status, csrf_token: window.CSRF_TOKEN || '' }),
        });
        const data = await res.json();
        if (data.success) {
          showToast('Order status updated', 'success');
        } else {
          showToast(data.message || 'Could not update status');
        }
      }
    </script>
  </body>
</html>
