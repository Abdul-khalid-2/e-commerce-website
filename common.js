// ===== Shared utilities & state management =====

const formatPKR = (n) => 'Rs. ' + Math.round(n).toLocaleString('en-PK');

function getCart() {
  try { return JSON.parse(localStorage.getItem('cart') || '[]'); } catch { return []; }
}
function setCart(c) { localStorage.setItem('cart', JSON.stringify(c)); updateCartCount(); }
function getWishlist() {
  try { return JSON.parse(localStorage.getItem('wishlist') || '[]'); } catch { return []; }
}
function setWishlist(w) { localStorage.setItem('wishlist', JSON.stringify(w)); updateWishlistCount(); }

function addToCart(productId, qty = 1, options = {}) {
  const cart = getCart();
  const existing = cart.find(i => i.id === productId && JSON.stringify(i.options) === JSON.stringify(options));
  if (existing) { existing.qty += qty; }
  else { cart.push({ id: productId, qty, options }); }
  setCart(cart);
  showToast('Added to cart!', 'success');
}
function removeFromCart(index) {
  const cart = getCart();
  cart.splice(index, 1);
  setCart(cart);
}
function updateCartQty(index, qty) {
  const cart = getCart();
  if (cart[index]) { cart[index].qty = Math.max(1, qty); setCart(cart); }
}
function getCartTotal() {
  return getCart().reduce((sum, item) => {
    const p = PRODUCTS.find(x => x.id === item.id);
    return sum + (p ? p.price * item.qty : 0);
  }, 0);
}
function getCartCount() {
  return getCart().reduce((n, i) => n + i.qty, 0);
}

function toggleWishlist(productId) {
  const w = getWishlist();
  const idx = w.indexOf(productId);
  if (idx > -1) { w.splice(idx, 1); showToast('Removed from wishlist'); }
  else { w.push(productId); showToast('Added to wishlist!', 'success'); }
  setWishlist(w);
  return w.includes(productId);
}
function isWishlisted(productId) { return getWishlist().includes(productId); }

function updateCartCount() {
  document.querySelectorAll('.cart-badge').forEach(el => {
    const count = getCartCount();
    el.textContent = count;
    el.style.display = count > 0 ? 'flex' : 'none';
  });
}
function updateWishlistCount() {
  document.querySelectorAll('.wishlist-badge').forEach(el => {
    const count = getWishlist().length;
    el.textContent = count;
    el.style.display = count > 0 ? 'flex' : 'none';
  });
}

// ===== Toast =====
function showToast(msg, type = 'info') {
  let container = document.querySelector('.toast-container-custom');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container-custom';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = 'toast-custom ' + type;
  const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-info-circle-fill';
  toast.innerHTML = `<i class="bi ${icon}"></i><span>${msg}</span>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    setTimeout(() => toast.remove(), 400);
  }, 2800);
}

// ===== Product card renderer =====
function renderProductCard(p, listView = false) {
  const discount = p.oldPrice ? Math.round((1 - p.price / p.oldPrice) * 100) : 0;
  const wished = isWishlisted(p.id);
  const stars = renderStars(p.rating);
  return `
    <div class="product-card ${listView ? 'd-flex' : ''}" data-id="${p.id}">
      <div class="product-img-wrap">
        ${p.badge ? `<span class="product-badge">${p.badge}</span>` : ''}
        ${discount > 0 ? `<span class="product-discount">-${discount}%</span>` : ''}
        <a href="product.html?id=${p.id}"><img src="${p.images[0]}" alt="${p.name}" loading="lazy"></a>
        <div class="product-actions">
          <button class="product-action-btn" onclick="openQuickView(${p.id})" title="Quick View"><i class="bi bi-eye"></i></button>
          <button class="product-action-btn ${wished ? 'active' : ''}" onclick="toggleWishlist(${p.id}); refreshWishBtn(this, ${p.id})" title="Wishlist"><i class="bi bi-heart${wished ? '-fill' : ''}"></i></button>
          <button class="product-action-btn" onclick="addToCart(${p.id})" title="Add to Cart"><i class="bi bi-cart-plus"></i></button>
        </div>
      </div>
      <div class="product-body">
        <span class="product-cat-tag">${p.category}</span>
        <h6 class="product-name"><a href="product.html?id=${p.id}">${p.name}</a></h6>
        <div class="mb-2">${stars}<span class="rating-text">(${p.reviews})</span></div>
        <div>
          <span class="product-price">${formatPKR(p.price)}</span>
          ${p.oldPrice ? `<span class="product-old-price">${formatPKR(p.oldPrice)}</span>` : ''}
        </div>
        ${listView ? `<p class="text-muted-2 fs-7 mt-2 mb-0">${p.description.slice(0, 100)}...</p>` : ''}
      </div>
    </div>
  `;
}
function refreshWishBtn(btn, id) {
  const wished = isWishlisted(id);
  btn.classList.toggle('active', wished);
  btn.querySelector('i').className = 'bi bi-heart' + (wished ? '-fill' : '');
}

function renderStars(rating) {
  let html = '<span class="rating-stars">';
  for (let i = 1; i <= 5; i++) {
    if (rating >= i) html += '<i class="bi bi-star-fill"></i>';
    else if (rating >= i - 0.5) html += '<i class="bi bi-star-half"></i>';
    else html += '<i class="bi bi-star"></i>';
  }
  html += '</span>';
  return html;
}

// ===== Quick View Modal =====
let quickViewModal;
function openQuickView(id) {
  const p = PRODUCTS.find(x => x.id === id);
  if (!p) return;
  const discount = p.oldPrice ? Math.round((1 - p.price / p.oldPrice) * 100) : 0;
  const modalHtml = `
    <div class="modal fade" id="quickViewModal" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-surface" style="border-radius:var(--radius);border:1px solid var(--border);">
          <div class="modal-body p-0">
            <div class="row g-0">
              <div class="col-md-5">
                <img src="${p.images[0]}" class="w-100" style="height:100%;object-fit:cover;border-radius:var(--radius) 0 0 var(--radius);" alt="${p.name}">
              </div>
              <div class="col-md-7 p-4">
                <button type="button" class="btn-close float-end" data-bs-dismiss="modal"></button>
                <span class="product-cat-tag">${p.category}</span>
                <h4 class="fw-700 mt-1 mb-2">${p.name}</h4>
                ${renderStars(p.rating)}<span class="rating-text">(${p.reviews} reviews)</span>
                <div class="my-3">
                  <span class="product-price fs-4">${formatPKR(p.price)}</span>
                  ${p.oldPrice ? `<span class="product-old-price fs-6">${formatPKR(p.oldPrice)}</span>` : ''}
                  ${discount > 0 ? `<span class="badge bg-success ms-2">-${discount}%</span>` : ''}
                </div>
                <p class="text-muted-2">${p.description.slice(0, 150)}...</p>
                <div class="mb-3">
                  <span class="fw-600 me-2">Colors:</span>
                  ${p.colors.map((c,i) => `<span class="option-chip ${i===0?'active':''}" data-color="${c}">${c}</span>`).join(' ')}
                </div>
                <p class="fs-7 text-muted-2 mb-3"><i class="bi bi-check-circle text-success"></i> ${p.stock > 0 ? `In Stock (${p.stock} available)` : 'Out of Stock'}</p>
                <div class="d-flex gap-2">
                  <button class="btn-brand flex-grow-1" onclick="addToCart(${p.id}); bootstrap.Modal.getInstance(document.getElementById('quickViewModal')).hide();"><i class="bi bi-cart-plus me-1"></i> Add to Cart</button>
                  <a href="product.html?id=${p.id}" class="btn-outline-brand">View Details</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>`;
  const old = document.getElementById('quickViewModal');
  if (old) old.remove();
  document.body.insertAdjacentHTML('beforeend', modalHtml);
  const el = document.getElementById('quickViewModal');
  quickViewModal = new bootstrap.Modal(el);
  quickViewModal.show();
  el.addEventListener('hidden.bs.modal', () => el.remove());
}

// ===== Navbar + Footer injection =====
function buildNavbar(activePage) {
  const nav = `
  <nav class="navbar navbar-expand-lg navbar-custom sticky-top py-2">
    <div class="container">
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav">
        <i class="bi bi-list fs-4"></i>
      </button>
      <a class="navbar-brand-text d-flex align-items-center gap-2" href="index.html">
        <i class="bi bi-bag-check-fill"></i> ShopMate
      </a>
      <div class="search-wrap d-none d-lg-block mx-3">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search products..." id="navSearch" oninput="handleSearch(this.value)">
        <div class="search-results" id="searchResults"></div>
      </div>
      <div class="d-flex align-items-center gap-1 ms-auto">
        <button class="theme-toggle d-none d-sm-inline-flex" onclick="toggleTheme()" title="Toggle theme"><i class="bi bi-moon-fill" id="themeIcon"></i></button>
        <a href="wishlist.html" class="icon-btn" title="Wishlist"><i class="bi bi-heart"></i><span class="wishlist-badge" style="display:none">0</span></a>
        <a href="cart.html" class="icon-btn" title="Cart"><i class="bi bi-cart3"></i><span class="cart-badge" style="display:none">0</span></a>
        <a href="login.html" class="btn-brand d-none d-sm-inline-flex ms-2">Login</a>
      </div>
    </div>
    <div class="container d-none d-lg-block">
      <ul class="navbar-nav flex-row gap-1 mt-2 pb-1">
        <li class="nav-item"><a class="nav-link-custom ${activePage==='home'?'active':''}" href="index.html">Home</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='shop'?'active':''}" href="shop.html">Shop</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='electronics'?'active':''}" href="shop.html?category=Electronics">Electronics</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='fashion'?'active':''}" href="shop.html?category=Fashion">Fashion</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='home-living'?'active':''}" href="shop.html?category=Home & Living">Home & Living</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='beauty'?'active':''}" href="shop.html?category=Beauty">Beauty</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='about'?'active':''}" href="about.html">About</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='contact'?'active':''}" href="contact.html">Contact</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='orders'?'active':''}" href="orders.html">Track Order</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='admin'?'active':''}" href="admin.html">Admin</a></li>
      </ul>
    </div>
  </nav>

  <div class="offcanvas offcanvas-start offcanvas-custom" tabindex="-1" id="mobileNav">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title navbar-brand-text"><i class="bi bi-bag-check-fill"></i> ShopMate</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <div class="search-wrap mb-3">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search products..." oninput="handleSearch(this.value)">
        <div class="search-results" id="searchResultsMobile"></div>
      </div>
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link-custom ${activePage==='home'?'active':''}" href="index.html">Home</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='shop'?'active':''}" href="shop.html">Shop</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="shop.html?category=Electronics">Electronics</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="shop.html?category=Fashion">Fashion</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="shop.html?category=Home & Living">Home & Living</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="shop.html?category=Beauty">Beauty</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='about'?'active':''}" href="about.html">About</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='contact'?'active':''}" href="contact.html">Contact</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='orders'?'active':''}" href="orders.html">Track Order</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='wishlist'?'active':''}" href="wishlist.html">Wishlist</a></li>
        <li class="nav-item"><a class="nav-link-custom ${activePage==='admin'?'active':''}" href="admin.html">Admin</a></li>
        <li class="nav-item mt-3 d-flex gap-2">
          <button class="theme-toggle" onclick="toggleTheme()"><i class="bi bi-moon-fill" id="themeIconMobile"></i></button>
          <a href="login.html" class="btn-brand w-100">Login / Signup</a>
        </li>
      </ul>
    </div>
  </div>`;

  const navPlaceholder = document.getElementById('navbar');
  if (navPlaceholder) { navPlaceholder.innerHTML = nav; }
}

function buildFooter() {
  const footer = `
  <footer class="footer">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-4 col-md-6">
          <h5 class="navbar-brand-text mb-3"><i class="bi bi-bag-check-fill"></i> ShopMate</h5>
          <p class="text-muted-2">Your trusted online shopping destination in Pakistan. Quality products, great prices, and fast delivery to your doorstep.</p>
          <div class="d-flex gap-2 mt-3">
            <a href="#" class="footer-social"><i class="bi bi-facebook"></i></a>
            <a href="#" class="footer-social"><i class="bi bi-instagram"></i></a>
            <a href="#" class="footer-social"><i class="bi bi-twitter-x"></i></a>
            <a href="#" class="footer-social"><i class="bi bi-youtube"></i></a>
            <a href="#" class="footer-social"><i class="bi bi-whatsapp"></i></a>
          </div>
        </div>
        <div class="col-lg-2 col-md-3 col-6">
          <h5>Quick Links</h5>
          <ul class="list-unstyled">
            <li class="mb-2"><a href="index.html">Home</a></li>
            <li class="mb-2"><a href="shop.html">Shop</a></li>
            <li class="mb-2"><a href="about.html">About Us</a></li>
            <li class="mb-2"><a href="contact.html">Contact</a></li>
            <li class="mb-2"><a href="orders.html">Track Order</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-3 col-6">
          <h5>Customer</h5>
          <ul class="list-unstyled">
            <li class="mb-2"><a href="login.html">My Account</a></li>
            <li class="mb-2"><a href="wishlist.html">Wishlist</a></li>
            <li class="mb-2"><a href="cart.html">Cart</a></li>
            <li class="mb-2"><a href="#">Return Policy</a></li>
            <li class="mb-2"><a href="#">FAQs</a></li>
          </ul>
        </div>
        <div class="col-lg-4 col-md-6">
          <h5>Contact Info</h5>
          <ul class="list-unstyled text-muted-2">
            <li class="mb-2"><i class="bi bi-geo-alt me-2"></i> Main Boulevard, Gulberg III, Lahore, Pakistan</li>
            <li class="mb-2"><i class="bi bi-telephone me-2"></i> +92 300 1234567</li>
            <li class="mb-2"><i class="bi bi-envelope me-2"></i> support@shopmate.pk</li>
          </ul>
          <h6 class="mt-3 mb-2">We Accept</h6>
          <div class="d-flex flex-wrap gap-2">
            <span class="footer-pay"><i class="bi bi-cash"></i> COD</span>
            <span class="footer-pay"><i class="bi bi-credit-card"></i> Card</span>
            <span class="footer-pay"><i class="bi bi-wallet2"></i> JazzCash</span>
            <span class="footer-pay"><i class="bi bi-wallet"></i> EasyPaisa</span>
            <span class="footer-pay"><i class="bi bi-bank"></i> Bank Transfer</span>
          </div>
        </div>
      </div>
      <hr class="border-soft mt-4 mb-3">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <p class="text-muted-2 fs-7 mb-0">&copy; 2026 ShopMate Pakistan. All rights reserved.</p>
        <p class="text-muted-2 fs-7 mb-0"><a href="#">Privacy Policy</a> &middot; <a href="#">Terms of Service</a></p>
      </div>
    </div>
  </footer>
  <button class="scroll-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Scroll to top"><i class="bi bi-arrow-up"></i></button>`;

  const footerPlaceholder = document.getElementById('footer');
  if (footerPlaceholder) { footerPlaceholder.innerHTML = footer; }
}

// ===== Search =====
function handleSearch(query) {
  const results = document.querySelectorAll('.search-results');
  if (!query || query.length < 2) {
    results.forEach(r => r.classList.remove('show'));
    return;
  }
  const matches = PRODUCTS.filter(p => p.name.toLowerCase().includes(query.toLowerCase()) || p.category.toLowerCase().includes(query.toLowerCase())).slice(0, 6);
  const html = matches.length > 0
    ? matches.map(p => `<div class="search-result-item" onclick="window.location.href='product.html?id=${p.id}'"><img src="${p.images[0]}" alt=""><div><div class="fw-600 fs-7">${p.name}</div><div class="text-brand fs-8">${formatPKR(p.price)}</div></div></div>`).join('')
    : '<div class="p-3 text-muted-2 text-center fs-7">No products found</div>';
  results.forEach(r => { r.innerHTML = html; r.classList.add('show'); });
}

document.addEventListener('click', (e) => {
  if (!e.target.closest('.search-wrap')) {
    document.querySelectorAll('.search-results').forEach(r => r.classList.remove('show'));
  }
});

// ===== Theme =====
function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme');
  const next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('theme', next);
  updateThemeIcon();
}
function updateThemeIcon() {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  document.querySelectorAll('#themeIcon, #themeIconMobile').forEach(el => {
    if (el) el.className = 'bi ' + (isDark ? 'bi-sun-fill' : 'bi-moon-fill');
  });
}
function initTheme() {
  const saved = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', saved);
  updateThemeIcon();
}

// ===== Scroll effects =====
function initScrollEffects() {
  const navbar = document.querySelector('.navbar-custom');
  const scrollTop = document.querySelector('.scroll-top');
  window.addEventListener('scroll', () => {
    if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 20);
    if (scrollTop) scrollTop.classList.toggle('show', window.scrollY > 400);
  });
}

// ===== Init =====
function initCommon(activePage) {
  initTheme();
  buildNavbar(activePage);
  buildFooter();
  updateCartCount();
  updateWishlistCount();
  initScrollEffects();
  if (window.AOS) AOS.init({ duration: 700, once: true, offset: 60 });
}
