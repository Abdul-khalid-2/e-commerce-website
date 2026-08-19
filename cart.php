<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/product-card.php';

use App\Models\Cart;

$pageTitle = 'Shopping Cart - ShopMate Pakistan';
$activePage = 'cart';
$basePath = '';

$userId = $_SESSION['user_id'] ?? null;
$cart = Cart::peekForSession(session_id(), $userId);
$cartId = $cart ? (int) $cart['id'] : null;
$items = $cartId ? Cart::getItemsWithProduct($cartId) : [];
$subtotal = $cartId ? Cart::getSubtotal($cartId) : 0.0;
$shipping = $subtotal > 2000 ? 0.0 : ($subtotal > 0 ? 200.0 : 0.0);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

    <div class="container section-pad pb-3">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-custom">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Shopping Cart</li>
        </ol>
      </nav>
      <h1 class="section-title mb-0">Shopping Cart</h1>
    </div>

    <div class="container pb-5">
<?php if (empty($items)): ?>
      <div class="empty-state" data-aos="fade-up">
        <i class="bi bi-cart-x"></i>
        <h3>Your cart is empty</h3>
        <p class="text-muted-2 mb-4">Looks like you haven't added anything yet. Let's fix that!</p>
        <a href="shop.php" class="btn-brand">Start Shopping <i class="bi bi-arrow-right ms-1"></i></a>
      </div>
<?php else: ?>
      <div class="row g-4">
        <div class="col-lg-8" data-aos="fade-up">
          <div id="cartItems">
<?php foreach ($items as $item): ?>
            <div class="cart-item mb-3 d-flex align-items-center gap-3 flex-wrap" data-item-id="<?= (int) $item['item_id'] ?>" data-price="<?= (float) $item['price'] ?>">
              <img src="<?= htmlspecialchars($item['image'] ?? '', ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
              <div class="flex-grow-1">
                <h6 class="fw-700 mb-1"><a href="product.php?id=<?= (int) $item['product_id'] ?>" class="text-decoration-none" style="color:var(--text)"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></a></h6>
                <small class="text-muted-2"><?= htmlspecialchars(trim(($item['color'] ?? '') . ' ' . ($item['size'] ?? '')), ENT_QUOTES, 'UTF-8') ?></small>
                <div class="text-brand fw-700 mt-1"><?= format_pkr((float) $item['price']) ?></div>
              </div>
              <div class="qty-selector">
                <button class="qty-btn" onclick="changeQty(<?= (int) $item['item_id'] ?>, -1)"><i class="bi bi-dash"></i></button>
                <input type="text" class="qty-val" value="<?= (int) $item['qty'] ?>" readonly>
                <button class="qty-btn" onclick="changeQty(<?= (int) $item['item_id'] ?>, 1)"><i class="bi bi-plus"></i></button>
              </div>
              <div class="fw-700 text-end line-total" style="min-width:100px;"><?= format_pkr((float) $item['price'] * (int) $item['qty']) ?></div>
              <button class="icon-btn text-danger" onclick="removeItem(<?= (int) $item['item_id'] ?>)" title="Remove"><i class="bi bi-trash3"></i></button>
            </div>
<?php endforeach; ?>
          </div>
          <div class="d-flex justify-content-between mt-3">
            <a href="shop.php" class="btn-outline-brand"><i class="bi bi-arrow-left me-1"></i> Continue Shopping</a>
            <button class="btn btn-sm text-brand" onclick="clearCart()">Clear Cart</button>
          </div>
        </div>
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
          <div class="summary-card">
            <h5 class="fw-700 mb-3">Order Summary</h5>
            <div class="summary-row"><span>Subtotal</span><span id="summarySubtotal"><?= format_pkr($subtotal) ?></span></div>
            <div class="summary-row"><span>Shipping</span><span id="summaryShipping"><?= $shipping === 0.0 ? 'FREE' : format_pkr($shipping) ?></span></div>
            <div id="discountRow" class="summary-row text-success" style="display:none;"><span>Discount</span><span id="summaryDiscount"></span></div>
            <div class="mb-3 mt-2">
              <label class="form-label">Discount Code</label>
              <div class="d-flex gap-2">
                <input type="text" class="form-control" id="discountInput" placeholder="Enter code">
                <button class="btn-brand" onclick="applyDiscount()">Apply</button>
              </div>
              <small class="text-muted-2">Try "SHOP10" for 10% off</small>
            </div>
            <div class="summary-total d-flex justify-content-between"><span>Total</span><span class="text-brand" id="summaryTotal"><?= format_pkr($subtotal + $shipping) ?></span></div>
            <a href="checkout.php" class="btn-brand w-100 mt-3"><i class="bi bi-credit-card me-1"></i> Proceed to Checkout</a>
            <div class="text-center mt-3">
              <small class="text-muted-2"><i class="bi bi-shield-check text-success"></i> Secure checkout with SSL encryption</small>
            </div>
          </div>
        </div>
      </div>
<?php endif; ?>
    </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/common.js"></script>
    <script>
      let discountApplied = 0;

      function recalcTotals() {
        let subtotal = 0;
        document.querySelectorAll('.cart-item').forEach(row => {
          const price = parseFloat(row.dataset.price);
          const qty = parseInt(row.querySelector('.qty-val').value, 10);
          const lineTotal = price * qty;
          row.querySelector('.line-total').textContent = formatPKR(lineTotal);
          subtotal += lineTotal;
        });
        const shipping = subtotal > 2000 ? 0 : (subtotal > 0 ? 200 : 0);
        const total = subtotal + shipping - discountApplied;
        document.getElementById('summarySubtotal').textContent = formatPKR(subtotal);
        document.getElementById('summaryShipping').textContent = shipping === 0 ? 'FREE' : formatPKR(shipping);
        document.getElementById('summaryTotal').textContent = formatPKR(total);
        if (discountApplied > 0) {
          document.getElementById('discountRow').style.display = 'flex';
          document.getElementById('summaryDiscount').textContent = '-' + formatPKR(discountApplied);
        }
        return subtotal;
      }

      async function changeQty(itemId, delta) {
        const row = document.querySelector(`.cart-item[data-item-id="${itemId}"]`);
        const input = row.querySelector('.qty-val');
        const newQty = Math.max(1, parseInt(input.value, 10) + delta);
        input.value = newQty;
        recalcTotals();
        const data = await updateCartItemQty(itemId, newQty);
        if (data.count !== undefined) updateCartCount(data.count);
      }

      async function removeItem(itemId) {
        await removeFromCartItem(itemId);
        document.querySelector(`.cart-item[data-item-id="${itemId}"]`)?.remove();
        showToast('Item removed from cart');
        if (document.querySelectorAll('.cart-item').length === 0) {
          window.location.reload();
        } else {
          recalcTotals();
          updateCartCount();
        }
      }

      async function clearCart() {
        await clearCartServer();
        try { sessionStorage.removeItem('discountCode'); } catch (err) { /* storage unavailable */ }
        showToast('Cart cleared');
        window.location.reload();
      }

      function applyDiscount() {
        const code = document.getElementById('discountInput').value.trim().toUpperCase();
        if (code === 'SHOP10') {
          const subtotal = recalcTotals();
          discountApplied = Math.round(subtotal * 0.1);
          // Remember the code so checkout.php can re-apply the same
          // discount there - previously it only lived in this page's
          // local variable and was lost the moment you clicked
          // "Proceed to Checkout". Guarded: some browsers/extensions
          // block storage access and throw instead of failing quietly.
          try { sessionStorage.setItem('discountCode', code); } catch (err) { /* storage unavailable - discount just won't carry over */ }
          showToast('10% discount applied!', 'success');
          recalcTotals();
        } else {
          showToast('Invalid discount code');
        }
      }

      initCommonPhp();
    </script>
  </body>
</html>
