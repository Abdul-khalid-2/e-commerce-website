<?php
/**
 * includes/navbar.php
 *
 * Replaces the old JS buildNavbar() from assets/js/common.js. Category
 * links are pulled from the database, so adding/deactivating a category
 * in the admin panel changes this menu automatically.
 *
 * The including page should set, before requiring this file:
 *   $activePage  (optional) - 'home' | 'shop' | 'about' | 'contact' |
 *                'orders' | 'wishlist' | 'admin' - highlights the
 *                matching nav link
 *   $basePath    (optional) - see includes/header.php
 */

declare(strict_types=1);

use App\Models\Category;

$activePage ??= '';
$basePath ??= '';
$navCategories = Category::active();

/** Small helper: echo 'active' if $key matches the current page. */
function nav_active(string $key, string $activePage): string
{
    return $activePage === $key ? 'active' : '';
}
?>
  <nav class="navbar navbar-expand-lg navbar-custom sticky-top py-2">
    <div class="container">
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav">
        <i class="bi bi-list fs-4"></i>
      </button>
      <a class="navbar-brand-text d-flex align-items-center gap-2" href="<?= $basePath ?>index.php">
        <i class="bi bi-bag-check-fill"></i> ShopMate
      </a>
      <div class="search-wrap d-none d-lg-block mx-3">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search products..." id="navSearch" oninput="handleSearch(this.value)">
        <div class="search-results" id="searchResults"></div>
      </div>
      <div class="d-flex align-items-center gap-1 ms-auto">
        <button class="theme-toggle d-none d-sm-inline-flex" onclick="toggleTheme()" title="Toggle theme"><i class="bi bi-moon-fill" id="themeIcon"></i></button>
        <a href="<?= $basePath ?>wishlist.html" class="icon-btn" title="Wishlist"><i class="bi bi-heart"></i><span class="wishlist-badge" style="display:none">0</span></a>
        <a href="<?= $basePath ?>cart.html" class="icon-btn" title="Cart"><i class="bi bi-cart3"></i><span class="cart-badge" style="display:none">0</span></a>
        <a href="<?= $basePath ?>login.html" class="btn-brand d-none d-sm-inline-flex ms-2">Login</a>
      </div>
    </div>
    <div class="container d-none d-lg-block">
      <ul class="navbar-nav flex-row gap-1 mt-2 pb-1">
        <li class="nav-item"><a class="nav-link-custom <?= nav_active('home', $activePage) ?>" href="<?= $basePath ?>index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link-custom <?= nav_active('shop', $activePage) ?>" href="<?= $basePath ?>shop.html">Shop</a></li>
<?php foreach ($navCategories as $cat): ?>
        <li class="nav-item"><a class="nav-link-custom" href="<?= $basePath ?>shop.html?category=<?= urlencode($cat['name']) ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></a></li>
<?php endforeach; ?>
        <li class="nav-item"><a class="nav-link-custom <?= nav_active('about', $activePage) ?>" href="<?= $basePath ?>about.html">About</a></li>
        <li class="nav-item"><a class="nav-link-custom <?= nav_active('contact', $activePage) ?>" href="<?= $basePath ?>contact.html">Contact</a></li>
        <li class="nav-item"><a class="nav-link-custom <?= nav_active('orders', $activePage) ?>" href="<?= $basePath ?>orders.html">Track Order</a></li>
        <li class="nav-item"><a class="nav-link-custom <?= nav_active('admin', $activePage) ?>" href="<?= $basePath ?>admin/index.html">Admin</a></li>
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
        <li class="nav-item"><a class="nav-link-custom <?= nav_active('home', $activePage) ?>" href="<?= $basePath ?>index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link-custom <?= nav_active('shop', $activePage) ?>" href="<?= $basePath ?>shop.html">Shop</a></li>
<?php foreach ($navCategories as $cat): ?>
        <li class="nav-item"><a class="nav-link-custom" href="<?= $basePath ?>shop.html?category=<?= urlencode($cat['name']) ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></a></li>
<?php endforeach; ?>
        <li class="nav-item"><a class="nav-link-custom <?= nav_active('about', $activePage) ?>" href="<?= $basePath ?>about.html">About</a></li>
        <li class="nav-item"><a class="nav-link-custom <?= nav_active('contact', $activePage) ?>" href="<?= $basePath ?>contact.html">Contact</a></li>
        <li class="nav-item"><a class="nav-link-custom <?= nav_active('orders', $activePage) ?>" href="<?= $basePath ?>orders.html">Track Order</a></li>
        <li class="nav-item"><a class="nav-link-custom <?= nav_active('wishlist', $activePage) ?>" href="<?= $basePath ?>wishlist.html">Wishlist</a></li>
        <li class="nav-item"><a class="nav-link-custom <?= nav_active('admin', $activePage) ?>" href="<?= $basePath ?>admin/index.html">Admin</a></li>
        <li class="nav-item mt-3 d-flex gap-2">
          <button class="theme-toggle" onclick="toggleTheme()"><i class="bi bi-moon-fill" id="themeIconMobile"></i></button>
          <a href="<?= $basePath ?>login.html" class="btn-brand w-100">Login / Signup</a>
        </li>
      </ul>
    </div>
  </div>
