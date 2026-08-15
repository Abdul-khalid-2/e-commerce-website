<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Auth;
use App\Models\ContactMessage;

Auth::requireAdmin();

$pageTitle = 'Messages - Admin';
$activeSection = 'messages';

$statusFilter = $_GET['status'] ?? '';
$validStatuses = ['New', 'Read', 'Replied'];
$messages = in_array($statusFilter, $validStatuses, true)
    ? ContactMessage::allOrdered($statusFilter)
    : ContactMessage::allOrdered();

require __DIR__ . '/../includes/admin-header.php';
?>
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <p class="text-muted-2 mb-0"><?= count($messages) ?> message<?= count($messages) !== 1 ? 's' : '' ?></p>
        <div class="d-flex gap-2">
          <a href="messages.php" class="btn btn-sm <?= $statusFilter === '' ? 'btn-brand' : 'btn-light' ?>">All</a>
<?php foreach ($validStatuses as $s): ?>
          <a href="messages.php?status=<?= urlencode($s) ?>" class="btn btn-sm <?= $statusFilter === $s ? 'btn-brand' : 'btn-light' ?>"><?= $s ?></a>
<?php endforeach; ?>
        </div>
      </div>

<?php if (empty($messages)): ?>
      <div class="stat-card text-center py-5">
        <i class="bi bi-envelope-open fs-1 text-muted-2"></i>
        <p class="text-muted-2 mb-0 mt-2">No messages<?= $statusFilter ? " with status \"{$statusFilter}\"" : '' ?>.</p>
      </div>
<?php else: ?>
      <div class="row g-3">
<?php foreach ($messages as $m): ?>
        <div class="col-12">
          <div class="stat-card" id="msg-<?= (int) $m['id'] ?>">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
              <div>
                <h6 class="fw-700 mb-0"><?= htmlspecialchars($m['subject'], ENT_QUOTES, 'UTF-8') ?></h6>
                <small class="text-muted-2"><?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8') ?> &lt;<?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8') ?>&gt; &middot; <?= date('d M Y, g:i A', strtotime($m['created_at'])) ?></small>
              </div>
              <span class="status-badge status-<?= $m['status'] === 'New' ? 'Pending' : ($m['status'] === 'Replied' ? 'Delivered' : 'Shipped') ?>"><?= htmlspecialchars($m['status'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <p class="text-muted-2 mb-3"><?= nl2br(htmlspecialchars($m['message'], ENT_QUOTES, 'UTF-8')) ?></p>
            <div class="d-flex gap-2 flex-wrap">
              <a href="mailto:<?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8') ?>?subject=<?= urlencode('Re: ' . $m['subject']) ?>" class="btn btn-sm btn-light"><i class="bi bi-reply me-1"></i> Reply by Email</a>
<?php if ($m['status'] === 'New'): ?>
              <button class="btn btn-sm btn-light" onclick="updateMessageStatus(<?= (int) $m['id'] ?>, 'mark-read')"><i class="bi bi-envelope-open me-1"></i> Mark as Read</button>
<?php endif; ?>
<?php if ($m['status'] !== 'Replied'): ?>
              <button class="btn btn-sm btn-light" onclick="updateMessageStatus(<?= (int) $m['id'] ?>, 'mark-replied')"><i class="bi bi-check-circle me-1"></i> Mark as Replied</button>
<?php endif; ?>
              <button class="btn btn-sm btn-light text-danger" onclick="deleteMessage(<?= (int) $m['id'] ?>)"><i class="bi bi-trash3 me-1"></i> Delete</button>
            </div>
          </div>
        </div>
<?php endforeach; ?>
      </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
    <script>
      async function updateMessageStatus(id, action) {
        const res = await fetch('../api/admin/messages.php', {
          method: 'POST',
          body: new URLSearchParams({ action, id, csrf_token: window.CSRF_TOKEN || '' }),
        });
        const data = await res.json();
        if (data.success) {
          showToast('Message updated', 'success');
          setTimeout(() => window.location.reload(), 400);
        } else {
          showToast(data.message || 'Could not update message');
        }
      }

      async function deleteMessage(id) {
        if (!confirm('Delete this message? This cannot be undone.')) return;
        const res = await fetch('../api/admin/messages.php', {
          method: 'POST',
          body: new URLSearchParams({ action: 'delete', id, csrf_token: window.CSRF_TOKEN || '' }),
        });
        const data = await res.json();
        if (data.success) {
          showToast('Message deleted', 'success');
          document.getElementById(`msg-${id}`)?.closest('.col-12')?.remove();
        } else {
          showToast(data.message || 'Could not delete message');
        }
      }
    </script>
  </body>
</html>
