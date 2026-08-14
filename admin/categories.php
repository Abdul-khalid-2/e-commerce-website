<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Auth;
use App\Models\Category;

Auth::requireAdmin();

$pageTitle = 'Categories - Admin';
$activeSection = 'categories';

$categories = Category::all('sort_order ASC');
$productCounts = Category::productCounts();

require __DIR__ . '/../includes/admin-header.php';
?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted-2 mb-0"><?= count($categories) ?> categories</p>
        <button class="btn-brand" onclick="openCategoryModal()"><i class="bi bi-plus-lg me-1"></i> Add New Category</button>
      </div>

      <div class="row g-3">
<?php foreach ($categories as $c): ?>
        <div class="col-md-4 col-6">
          <div class="stat-card text-center position-relative">
            <span class="status-badge status-<?= (int) $c['is_active'] ? 'Delivered' : 'Cancelled' ?> position-absolute" style="top:12px;right:12px;"><?= (int) $c['is_active'] ? 'Active' : 'Inactive' ?></span>
            <div style="width:80px;height:80px;border-radius:50%;overflow:hidden;margin:0 auto 10px;"><img src="<?= htmlspecialchars($c['image'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="width:100%;height:100%;object-fit:cover;"></div>
            <h6 class="fw-700"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></h6>
            <p class="text-muted-2 fs-7 mb-2"><?= $productCounts[$c['id']] ?? 0 ?> products</p>
            <button class="btn btn-sm btn-light" onclick='openCategoryModal(<?= (int) $c["id"] ?>)'><i class="bi bi-pencil"></i> Edit</button>
          </div>
        </div>
<?php endforeach; ?>
      </div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
    <script>
      const CATEGORIES_DATA = <?= json_encode(array_map(fn($c) => [
          'id' => (int) $c['id'], 'name' => $c['name'], 'icon' => $c['icon'],
          'image' => $c['image'], 'active' => (bool) $c['is_active'],
      ], $categories)) ?>;

      function openCategoryModal(id) {
        const c = id ? CATEGORIES_DATA.find(x => x.id === id) : { name: '', icon: 'bi-grid', image: '', active: true };
        const modalHtml = `
          <div class="modal fade" id="categoryModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content bg-surface" style="border:1px solid var(--border);border-radius:var(--radius);">
                <div class="modal-header border-soft"><h5 class="modal-title fw-700">${id ? 'Edit' : 'Add New'} Category</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                  <div class="row g-3">
                    <div class="col-12"><label class="form-label">Category Name</label><input type="text" class="form-control" id="cName" value="${c.name.replace(/"/g,'&quot;')}" placeholder="e.g. Electronics"></div>
                    <div class="col-md-6"><label class="form-label">Icon (Bootstrap Icon class)</label><input type="text" class="form-control" id="cIcon" value="${c.icon}" placeholder="bi-grid"></div>
                    <div class="col-md-6"><label class="form-label">Image URL</label><input type="text" class="form-control" id="cImage" value="${c.image || ''}" placeholder="https://..."></div>
                    <div class="col-12">
                      <label class="form-label d-block">Status</label>
                      <div class="btn-group w-100" role="group">
                        <button type="button" class="btn ${c.active ? 'btn-brand' : 'btn-outline-secondary'}" id="cStatusActive" onclick="setCategoryStatus(true)"><i class="bi bi-check-circle me-1"></i> Active</button>
                        <button type="button" class="btn ${!c.active ? 'btn-danger' : 'btn-outline-secondary'}" id="cStatusInactive" onclick="setCategoryStatus(false)"><i class="bi bi-slash-circle me-1"></i> Inactive</button>
                      </div>
                      <input type="hidden" id="cActive" value="${c.active}">
                    </div>
                  </div>
                </div>
                <div class="modal-footer border-soft">
                  <button class="btn-outline-brand" data-bs-dismiss="modal">Cancel</button>
                  <button class="btn-brand" onclick="saveCategory(${id || 'null'})">${id ? 'Update' : 'Add'} Category</button>
                </div>
              </div>
            </div>
          </div>`;
        document.getElementById('categoryModal')?.remove();
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        new bootstrap.Modal(document.getElementById('categoryModal')).show();
      }

      function setCategoryStatus(active) {
        document.getElementById('cActive').value = active;
        document.getElementById('cStatusActive').className = 'btn ' + (active ? 'btn-brand' : 'btn-outline-secondary');
        document.getElementById('cStatusInactive').className = 'btn ' + (!active ? 'btn-danger' : 'btn-outline-secondary');
      }

      async function saveCategory(id) {
        const name = document.getElementById('cName').value.trim();
        if (!name) { showToast('Please enter a category name'); return; }

        const body = new URLSearchParams({
          action: 'save',
          id: id || '',
          name,
          icon: document.getElementById('cIcon').value.trim() || 'bi-grid',
          image: document.getElementById('cImage').value.trim(),
          active: document.getElementById('cActive').value === 'true' ? '1' : '0',
        });
        const res = await fetch('../api/admin/categories.php', { method: 'POST', body });
        const data = await res.json();
        if (data.success) {
          showToast(id ? 'Category updated' : 'Category added', 'success');
          bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
          setTimeout(() => window.location.reload(), 400);
        } else {
          showToast(data.message || 'Could not save category');
        }
      }
    </script>
  </body>
</html>
