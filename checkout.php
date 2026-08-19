<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

use App\Models\Cart;

$pageTitle = 'Checkout - ShopMate Pakistan';
$activePage = 'cart';
$basePath = '';

$userId = $_SESSION['user_id'] ?? null;
$cart = Cart::peekForSession(session_id(), $userId);
$cartId = $cart ? (int) $cart['id'] : null;
$items = $cartId ? Cart::getItemsWithProduct($cartId) : [];
$subtotal = $cartId ? Cart::getSubtotal($cartId) : 0.0;

$cartItemsForJs = array_map(static function (array $i): array {
    return [
        'item_id' => (int) $i['item_id'],
        'product_id' => (int) $i['product_id'],
        'name' => $i['name'],
        'price' => (float) $i['price'],
        'qty' => (int) $i['qty'],
        'color' => $i['color'],
        'size' => $i['size'],
        'image' => $i['image'],
    ];
}, $items);

$prefillName = $_SESSION['user_name'] ?? '';
$prefillEmail = $_SESSION['user_email'] ?? '';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

    <div class="container section-pad pb-3">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-custom">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
          <li class="breadcrumb-item active">Checkout</li>
        </ol>
      </nav>
      <h1 class="section-title mb-0">Checkout</h1>
    </div>

    <div class="container pb-5">
      <div id="checkoutContent"></div>
    </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/common.js"></script>
    <script>
      const PAKISTAN_CITIES = ["Karachi","Lahore","Islamabad","Rawalpindi","Faisalabad","Multan","Peshawar","Quetta","Hyderabad","Sialkot","Gujranwala","Bahawalpur","Sukkur","Mardan","Sargodha"];
      const cart = <?= json_encode($cartItemsForJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
      const prefillName = <?= json_encode($prefillName, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
      const prefillEmail = <?= json_encode($prefillEmail, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

      let step = 1;
      let selectedPayment = 'cod';
      // Re-apply the discount code applied on cart.php, if any - it was
      // only ever kept in a local JS variable there, so it silently
      // vanished the moment you navigated to this page.
      //
      // Wrapped in try/catch: some browsers/extensions (privacy tools,
      // certain private-browsing modes, strict cookie/storage settings)
      // throw a SecurityError on sessionStorage access instead of just
      // returning null. Since this line runs before anything else,
      // an uncaught throw here would silently kill the whole script
      // and leave the page blank with no visible error at all.
      let discountCode = '';
      try {
        discountCode = sessionStorage.getItem('discountCode') || '';
      } catch (err) {
        console.warn('[checkout.php] sessionStorage unavailable, skipping discount carry-over:', err);
      }

      function cartSubtotal() {
        return cart.reduce((sum, item) => sum + item.price * item.qty, 0);
      }
      function discountAmount(subtotal) {
        return discountCode === 'SHOP10' ? Math.round(subtotal * 0.1) : 0;
      }

      function renderCheckout() {
        if (cart.length === 0) {
          document.getElementById('checkoutContent').innerHTML = `
            <div class="empty-state">
              <i class="bi bi-cart-x"></i>
              <h3>Your cart is empty</h3>
              <p class="text-muted-2 mb-4">Add some products before checking out.</p>
              <a href="shop.php" class="btn-brand">Shop Now</a>
            </div>`;
          return;
        }

        const subtotal = cartSubtotal();
        const shipping = subtotal > 2000 ? 0 : 200;
        const discount = discountAmount(subtotal);
        const total = subtotal + shipping - discount;

        document.getElementById('checkoutContent').innerHTML = `
          <div class="row g-4">
            <div class="col-lg-8">
              <div class="checkout-progress">
                <div class="progress-step ${step>=1?'active':''} ${step>1?'completed':''}">
                  <div class="progress-step-circle">${step>1?'<i class="bi bi-check"></i>':'1'}</div>
                  <span class="progress-step-label d-none d-sm-inline">Shipping</span>
                </div>
                <div class="progress-step-line ${step>1?'completed':''}"></div>
                <div class="progress-step ${step>=2?'active':''} ${step>2?'completed':''}">
                  <div class="progress-step-circle">${step>2?'<i class="bi bi-check"></i>':'2'}</div>
                  <span class="progress-step-label d-none d-sm-inline">Payment</span>
                </div>
                <div class="progress-step-line ${step>2?'completed':''}"></div>
                <div class="progress-step ${step>=3?'active':''}">
                  <div class="progress-step-circle">3</div>
                  <span class="progress-step-label d-none d-sm-inline">Review</span>
                </div>
              </div>

              <div class="bg-surface p-4 rounded shadow-soft" style="border:1px solid var(--border);">
                <div id="stepContent"></div>
              </div>
            </div>

            <div class="col-lg-4">
              <div class="summary-card">
                <h5 class="fw-700 mb-3">Order Summary</h5>
                ${cart.map(item => `<div class="d-flex gap-2 mb-2 align-items-center">
                    <img src="${item.image || ''}" style="width:50px;height:50px;border-radius:8px;object-fit:cover;">
                    <div class="flex-grow-1">
                      <div class="fs-7 fw-600">${item.name}</div>
                      <small class="text-muted-2">Qty: ${item.qty}</small>
                    </div>
                    <div class="fs-7 fw-600 text-brand">${formatPKR(item.price * item.qty)}</div>
                  </div>`).join('')}
                <hr class="border-soft">
                <div class="summary-row"><span>Subtotal</span><span>${formatPKR(subtotal)}</span></div>
                <div class="summary-row"><span>Shipping</span><span>${shipping === 0 ? 'FREE' : formatPKR(shipping)}</span></div>
                ${discount > 0 ? `<div class="summary-row text-success"><span>Discount (${discountCode})</span><span>-${formatPKR(discount)}</span></div>` : ''}
                <div class="summary-total d-flex justify-content-between"><span>Total</span><span class="text-brand">${formatPKR(total)}</span></div>
              </div>
            </div>
          </div>`;
        renderStep();
      }

      function renderStep() {
        const content = document.getElementById('stepContent');
        if (step === 1) {
          content.innerHTML = `
            <h5 class="fw-700 mb-3">Shipping Information</h5>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" class="form-control" id="fullName" value="${prefillName.replace(/"/g,'&quot;')}" required></div>
              <div class="col-md-6"><label class="form-label">Phone Number *</label><input type="tel" class="form-control" id="phone" placeholder="03XX-XXXXXXX" required></div>
              <div class="col-12"><label class="form-label">Email Address</label><input type="email" class="form-control" id="email" value="${prefillEmail.replace(/"/g,'&quot;')}" placeholder="you@example.com"></div>
              <div class="col-12"><label class="form-label">Street Address *</label><input type="text" class="form-control" id="address" required></div>
              <div class="col-md-6"><label class="form-label">City *</label>
                <select class="form-select" id="city" required>
                  <option value="">Select City</option>
                  ${PAKISTAN_CITIES.map(c => `<option value="${c}">${c}</option>`).join('')}
                </select>
              </div>
              <div class="col-md-6"><label class="form-label">Postal Code</label><input type="text" class="form-control" id="postal" placeholder="e.g. 54000"></div>
              <div class="col-12"><label class="form-label">Order Notes (optional)</label><textarea class="form-control" id="notes" rows="2" placeholder="Any special delivery instructions..."></textarea></div>
            </div>
            <div class="d-flex justify-content-end mt-4">
              <button class="btn-brand" onclick="nextStep()">Continue to Payment <i class="bi bi-arrow-right ms-1"></i></button>
            </div>`;
        } else if (step === 2) {
          content.innerHTML = `
            <h5 class="fw-700 mb-3">Payment Method</h5>
            <div class="d-flex flex-column gap-2">
              ${[
                { id: 'cod', icon: 'bi-cash-coin', name: 'Cash on Delivery', desc: 'Pay when you receive your order' },
                { id: 'jazzcash', icon: 'bi-wallet2', name: 'JazzCash', desc: 'Pay via JazzCash mobile wallet' },
                { id: 'easypaisa', icon: 'bi-wallet', name: 'EasyPaisa', desc: 'Pay via EasyPaisa mobile wallet' },
                { id: 'bank_transfer', icon: 'bi-bank', name: 'Bank Transfer', desc: 'Transfer to our bank account' },
                { id: 'card', icon: 'bi-credit-card', name: 'Credit/Debit Card', desc: 'Visa, Mastercard, UnionPay' }
              ].map(opt => `
                <div class="payment-option ${selectedPayment === opt.id ? 'selected' : ''}" onclick="selectPayment('${opt.id}')">
                  <i class="bi ${opt.icon}"></i>
                  <div class="flex-grow-1">
                    <div class="fw-600">${opt.name}</div>
                    <small class="text-muted-2">${opt.desc}</small>
                  </div>
                  <i class="bi bi-check-circle-fill ${selectedPayment === opt.id ? 'text-brand' : ''}" style="opacity:${selectedPayment === opt.id ? '1' : '0'};"></i>
                </div>`).join('')}
            </div>
            <div id="paymentDetails" class="mt-3"></div>
            <div class="d-flex justify-content-between mt-4">
              <button class="btn-outline-brand" onclick="step=1; renderCheckout();"><i class="bi bi-arrow-left me-1"></i> Back</button>
              <button class="btn-brand" onclick="nextStep()">Review Order <i class="bi bi-arrow-right ms-1"></i></button>
            </div>`;
          renderPaymentDetails();
        } else if (step === 3) {
          const name = document.getElementById('fullName').value;
          const phone = document.getElementById('phone').value;
          const address = document.getElementById('address').value;
          const city = document.getElementById('city').value;
          const paymentName = { cod: 'Cash on Delivery', jazzcash: 'JazzCash', easypaisa: 'EasyPaisa', bank_transfer: 'Bank Transfer', card: 'Credit/Debit Card' }[selectedPayment];
          content.innerHTML = `
            <h5 class="fw-700 mb-3">Review Your Order</h5>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="bg-soft p-3 rounded" style="border:1px solid var(--border);">
                  <h6 class="fw-700 mb-2"><i class="bi bi-geo-alt text-brand"></i> Shipping Address</h6>
                  <p class="mb-0 fs-7">${name}<br>${phone}<br>${address}<br>${city}</p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="bg-soft p-3 rounded" style="border:1px solid var(--border);">
                  <h6 class="fw-700 mb-2"><i class="bi bi-credit-card text-brand"></i> Payment Method</h6>
                  <p class="mb-0 fs-7">${paymentName}</p>
                </div>
              </div>
              <div class="col-12">
                <h6 class="fw-700 mb-2">Items (${cart.reduce((n,i)=>n+i.qty,0)})</h6>
                ${cart.map(item => `<div class="d-flex justify-content-between align-items-center py-2 border-soft border-bottom">
                    <div class="d-flex gap-2 align-items-center">
                      <img src="${item.image || ''}" style="width:40px;height:40px;border-radius:6px;object-fit:cover;">
                      <span class="fs-7">${item.name} x${item.qty}</span>
                    </div>
                    <span class="fs-7 fw-600">${formatPKR(item.price * item.qty)}</span>
                  </div>`).join('')}
              </div>
            </div>
            <div class="d-flex justify-content-between mt-4">
              <button class="btn-outline-brand" onclick="step=2; renderCheckout();"><i class="bi bi-arrow-left me-1"></i> Back</button>
              <button class="btn-accent btn-lg" id="placeOrderBtn" onclick="placeOrder()"><i class="bi bi-bag-check me-1"></i> Place Order</button>
            </div>`;
        }
      }

      function selectPayment(id) {
        selectedPayment = id;
        const opts = ['cod','jazzcash','easypaisa','bank_transfer','card'];
        document.querySelectorAll('.payment-option').forEach((el, i) => {
          el.classList.toggle('selected', opts[i] === id);
          el.querySelector('.bi-check-circle-fill').style.opacity = opts[i] === id ? '1' : '0';
          el.querySelector('.bi-check-circle-fill').className = 'bi bi-check-circle-fill ' + (opts[i] === id ? 'text-brand' : '');
        });
        renderPaymentDetails();
      }
      function renderPaymentDetails() {
        const detail = document.getElementById('paymentDetails');
        if (selectedPayment === 'card') {
          detail.innerHTML = `<div class="bg-soft p-3 rounded border-soft">
            <div class="row g-2">
              <div class="col-12"><label class="form-label">Card Number</label><input type="text" class="form-control" placeholder="0000 0000 0000 0000"></div>
              <div class="col-6"><label class="form-label">Expiry</label><input type="text" class="form-control" placeholder="MM/YY"></div>
              <div class="col-6"><label class="form-label">CVV</label><input type="text" class="form-control" placeholder="123"></div>
            </div></div>`;
        } else if (selectedPayment === 'jazzcash' || selectedPayment === 'easypaisa') {
          detail.innerHTML = `<div class="bg-soft p-3 rounded border-soft"><small class="text-muted-2">You will receive a payment request on your mobile number after placing the order.</small></div>`;
        } else if (selectedPayment === 'bank_transfer') {
          detail.innerHTML = `<div class="bg-soft p-3 rounded border-soft"><small class="text-muted-2">Bank: HBL, Account: 0011223344556, Title: ShopMate Pakistan. Upload receipt to confirm.</small></div>`;
        } else {
          detail.innerHTML = '';
        }
      }

      function nextStep() {
        if (step === 1) {
          const name = document.getElementById('fullName').value.trim();
          const phone = document.getElementById('phone').value.trim();
          const address = document.getElementById('address').value.trim();
          const city = document.getElementById('city').value;
          if (!name || !phone || !address || !city) { showToast('Please fill all required fields'); return; }
          if (!/^03\d{2}-?\d{7}$/.test(phone.replace(/\s/g, '')) && !/^\+92\d{10}$/.test(phone.replace(/\s/g, ''))) { showToast('Please enter a valid Pakistani phone number'); return; }
        }
        step++;
        renderCheckout();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }

      async function placeOrder() {
        const btn = document.getElementById('placeOrderBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Placing Order...';

        const payload = new URLSearchParams({
          customer_name: document.getElementById('fullName').value,
          customer_phone: document.getElementById('phone').value,
          customer_email: document.getElementById('email').value,
          shipping_address: document.getElementById('address').value,
          city: document.getElementById('city').value,
          postal_code: document.getElementById('postal').value,
          payment_method: selectedPayment,
          csrf_token: window.CSRF_TOKEN || '',
        });

        try {
          const res = await fetch('api/place-order.php', { method: 'POST', body: payload });
          const data = await res.json();

          if (!data.success) {
            showToast(data.message || 'Could not place order');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-bag-check me-1"></i> Place Order';
            return;
          }

          document.getElementById('checkoutContent').innerHTML = `
            <div class="text-center py-5">
              <div class="success-check"><i class="bi bi-check-lg"></i></div>
              <h2 class="fw-700 mb-2">Order Placed Successfully!</h2>
              <p class="text-muted-2 mb-3">Thank you for your purchase. Your order number is:</p>
              <h3 class="text-brand fw-700 mb-4">${data.order_number}</h3>
              <p class="text-muted-2 mb-4">We've sent a confirmation to your phone. Expected delivery: 3-5 business days.</p>
              <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="orders.php?number=${encodeURIComponent(data.order_number)}" class="btn-brand"><i class="bi bi-truck me-1"></i> Track Your Order</a>
                <a href="shop.php" class="btn-outline-brand">Continue Shopping</a>
              </div>
            </div>`;
          updateCartCount(0);
        } catch (err) {
          showToast('Network error - please try again');
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-bag-check me-1"></i> Place Order';
        }
      }

      try {
        if (typeof initCommonPhp !== 'function' || typeof renderCheckout !== 'function') {
          throw new Error('assets/js/common.js did not load - initCommonPhp/renderCheckout are undefined. Check the Network tab for a failed/404 request to assets/js/common.js.');
        }
        initCommonPhp();
        renderCheckout();
      } catch (err) {
        // Surface failures instead of leaving #checkoutContent silently
        // blank - makes the real error visible on-page (and in console
        // with a distinct, greppable prefix) instead of a mystery blank
        // page.
        console.error('[checkout.php] failed to render:', err);
        document.getElementById('checkoutContent').innerHTML = `
          <div class="empty-state">
            <i class="bi bi-exclamation-triangle text-danger"></i>
            <h3>Something went wrong loading checkout</h3>
            <p class="text-muted-2 mb-2">${(err && err.message) ? err.message.replace(/</g, '&lt;') : 'Unknown error'}</p>
            <p class="text-muted-2 mb-4">Open your browser's DevTools Console for the full error, or try reloading the page.</p>
            <button class="btn-brand" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise me-1"></i> Reload Page</button>
          </div>`;
      }
    </script>
  </body>
</html>
