<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Auth;
use App\Models\Category;
use App\Models\Product;

Auth::requireAdmin();

$pageTitle = 'Products - Admin';
$activeSection = 'products';

$categories = Category::active();
$products = Product::allActive();
$categoryNameById = array_column($categories, 'name', 'id');

require __DIR__ . '/../includes/admin-header.php';
?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted-2 mb-0"><?= count($products) ?> products</p>
        <button class="btn-brand" onclick="openProductModal()"><i class="bi bi-plus-lg me-1"></i> Add New Product</button>
      </div>

      <div class="stat-card p-0">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
            <tbody>
<?php foreach ($products as $p): ?>
              <tr>
                <td class="fw-600"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?><br><small class="text-muted-2"><?= htmlspecialchars($p['brand'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                <td><?= htmlspecialchars($categoryNameById[$p['category_id']] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td>Rs. <?= number_format((float) $p['price']) ?></td>
                <td><?= (int) $p['stock'] > 0 ? (int) $p['stock'] : '<span class="text-danger">Out of stock</span>' ?></td>
                <td><span class="status-badge status-<?= (int) $p['stock'] > 0 ? 'Delivered' : 'Cancelled' ?>"><?= (int) $p['stock'] > 0 ? 'Active' : 'Out of Stock' ?></span></td>
                <td class="text-end">
                  <button class="btn btn-sm btn-light" onclick='openProductModal(<?= (int) $p["id"] ?>)'><i class="bi bi-pencil"></i></button>
                  <button class="btn btn-sm btn-light text-danger" onclick="deleteProduct(<?= (int) $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name']), ENT_QUOTES, 'UTF-8') ?>')"><i class="bi bi-trash3"></i></button>
                </td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
    <script>
      const CATEGORIES_ADMIN = <?= json_encode(array_map(fn($c) => ['id' => (int) $c['id'], 'name' => $c['name']], $categories)) ?>;
      const PRODUCTS_ADMIN = <?= json_encode(array_map(fn($p) => [
          'id' => (int) $p['id'], 'name' => $p['name'], 'brand' => $p['brand'],
          'category_id' => $p['category_id'] !== null ? (int) $p['category_id'] : null,
          'price' => (float) $p['price'], 'old_price' => $p['old_price'] !== null ? (float) $p['old_price'] : null,
          'stock' => (int) $p['stock'], 'description' => $p['description'],
      ], $products)) ?>;

      function openProductModal(id) {
        const p = id ? PRODUCTS_ADMIN.find(x => x.id === id) : null;
        const modalHtml = `
          <div class="modal fade" id="productModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content bg-surface" style="border:1px solid var(--border);border-radius:var(--radius);">
                <div class="modal-header border-soft"><h5 class="modal-title fw-700">${id ? 'Edit' : 'Add New'} Product</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                  <div class="row g-3">
                    <div class="col-md-8"><label class="form-label">Product Name</label><input type="text" class="form-control" id="pName" value="${p ? p.name.replace(/"/g,'&quot;') : ''}"></div>
                    <div class="col-md-4"><label class="form-label">Brand</label><input type="text" class="form-control" id="pBrand" value="${p && p.brand ? p.brand.replace(/"/g,'&quot;') : ''}"></div>
                    <div class="col-md-4"><label class="form-label">Category</label>
                      <select class="form-select" id="pCategory">
                        <option value="">— None —</option>
                        ${CATEGORIES_ADMIN.map(c => `<option value="${c.id}" ${p && p.category_id === c.id ? 'selected' : ''}>${c.name}</option>`).join('')}
                      </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Price (Rs.)</label><input type="number" class="form-control" id="pPrice" value="${p ? p.price : ''}" min="1"></div>
                    <div class="col-md-4"><label class="form-label">Old Price (optional)</label><input type="number" class="form-control" id="pOldPrice" value="${p && p.old_price ? p.old_price : ''}" min="0"></div>
                    <div class="col-md-6"><label class="form-label">Stock</label><input type="number" class="form-control" id="pStock" value="${p ? p.stock : 0}" min="0"></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" id="pDescription" rows="3">${p && p.description ? p.description : ''}</textarea></div>
                  </div>
                </div>
                <div class="modal-footer border-soft">
                  <button class="btn-outline-brand" data-bs-dismiss="modal">Cancel</button>
                  <button class="btn-brand" onclick="saveProduct(${id || 'null'})">${id ? 'Update' : 'Add'} Product</button>
                </div>
              </div>
            </div>
          </div>`;
        document.getElementById('productModal')?.remove();
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        new bootstrap.Modal(document.getElementById('productModal')).show();
      }

      async function saveProduct(id) {
        const name = document.getElementById('pName').value.trim();
        const price = parseFloat(document.getElementById('pPrice').value);
        if (!name || !price || price <= 0) { showToast('Please enter a name and a valid price'); return; }

        const body = new URLSearchParams({
          action: 'save',
          id: id || '',
          name,
          brand: document.getElementById('pBrand').value.trim(),
          category_id: document.getElementById('pCategory').value,
          price,
          old_price: document.getElementById('pOldPrice').value,
          stock: document.getElementById('pStock').value,
          description: document.getElementById('pDescription').value.trim(),
          csrf_token: window.CSRF_TOKEN || '',
        });
        const res = await fetch('../api/admin/products.php', { method: 'POST', body });
        const data = await res.json();
        if (data.success) {
          showToast(id ? 'Product updated' : 'Product added', 'success');
          bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
          setTimeout(() => window.location.reload(), 400);
        } else {
          showToast(data.message || 'Could not save product');
        }
      }

      async function deleteProduct(id, name) {
        if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
        const res = await fetch('../api/admin/products.php', { method: 'POST', body: new URLSearchParams({ action: 'delete', id, csrf_token: window.CSRF_TOKEN || '' }) });
        const data = await res.json();
        if (data.success) {
          showToast('Product deleted', 'success');
          setTimeout(() => window.location.reload(), 400);
        } else {
          showToast(data.message || 'Could not delete product');
        }
      }
    </script>
  </body>
</html>
