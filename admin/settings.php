<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Auth;
use App\Models\Setting;

Auth::requireAdmin();

$pageTitle = 'Settings - Admin';
$activeSection = 'settings';

$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Setting::set('store_name', trim((string) ($_POST['store_name'] ?? '')));
    Setting::set('contact_email', trim((string) ($_POST['contact_email'] ?? '')));
    Setting::set('contact_phone', trim((string) ($_POST['contact_phone'] ?? '')));
    Setting::set('currency', trim((string) ($_POST['currency'] ?? 'PKR')));
    Setting::set('free_shipping_threshold', (string) max(0, (int) ($_POST['free_shipping_threshold'] ?? 0)));
    $saved = true;
}

$settings = Setting::all();

require __DIR__ . '/../includes/admin-header.php';
?>
<?php if ($saved): ?>
      <div class="alert alert-success py-2 fs-7"><i class="bi bi-check-circle me-1"></i> Settings saved.</div>
<?php endif; ?>
      <div class="stat-card">
        <form method="POST" action="settings.php">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Store Name</label><input type="text" class="form-control" name="store_name" value="<?= htmlspecialchars($settings['store_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-6"><label class="form-label">Currency</label><input type="text" class="form-control" name="currency" value="<?= htmlspecialchars($settings['currency'] ?? 'PKR', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-6"><label class="form-label">Contact Email</label><input type="email" class="form-control" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-6"><label class="form-label">Contact Phone</label><input type="text" class="form-control" name="contact_phone" value="<?= htmlspecialchars($settings['contact_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-6"><label class="form-label">Free Shipping Threshold (Rs.)</label><input type="number" class="form-control" name="free_shipping_threshold" value="<?= htmlspecialchars($settings['free_shipping_threshold'] ?? '2000', ENT_QUOTES, 'UTF-8') ?>" min="0"></div>
          </div>
          <button type="submit" class="btn-brand mt-4"><i class="bi bi-check-lg me-1"></i> Save Settings</button>
        </form>
      </div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
  </body>
</html>
