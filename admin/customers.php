<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;

Auth::requireAdmin();

$pageTitle = 'Customers - Admin';
$activeSection = 'customers';

$db = Database::getConnection();
$customers = $db->query(
    "SELECT u.id, u.name, u.email, u.phone, u.created_at,
            COUNT(o.id) AS order_count,
            COALESCE(SUM(CASE WHEN o.status != 'Cancelled' THEN o.total ELSE 0 END), 0) AS total_spent
     FROM users u
     LEFT JOIN orders o ON o.user_id = u.id
     WHERE u.role = 'customer'
     GROUP BY u.id, u.name, u.email, u.phone, u.created_at
     ORDER BY u.created_at DESC"
)->fetchAll();

require __DIR__ . '/../includes/admin-header.php';
?>
      <p class="text-muted-2 mb-3"><?= count($customers) ?> customer<?= count($customers) !== 1 ? 's' : '' ?></p>

      <div class="stat-card p-0">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead><tr><th>Customer</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Joined</th></tr></thead>
            <tbody>
<?php if (empty($customers)): ?>
              <tr><td colspan="6" class="text-center text-muted-2 py-4">No customers yet.</td></tr>
<?php endif; ?>
<?php foreach ($customers as $c): ?>
              <tr>
                <td class="fw-600"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($c['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int) $c['order_count'] ?></td>
                <td>Rs. <?= number_format((float) $c['total_spent']) ?></td>
                <td class="fs-7 text-muted-2"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
  </body>
</html>
