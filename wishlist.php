<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

use App\Models\Category;
use App\Models\Product;

$pageTitle = 'Wishlist - ShopMate Pakistan';
$activePage = 'wishlist';
$basePath = '';

// Wishlist itself stays client-side (localStorage) for now — it doesn't
// require login, matching the original design. See README for the plan
// to move this to the wishlists table for logged-in users later.
$categoryNameById = array_column(Category::active(), 'name', 'id');
$allProducts = Product::allActiveWithRelations();

$productsForJs = array_map(static function (array $p) use ($categoryNameById): array {
    return [
        'id' => (int) $p['id'],
        'name' => $p['name'],
        'category' => $categoryNameById[$p['category_id']] ?? '',
        'brand' => $p['brand'],
        'price' => (float) $p['price'],
        'oldPrice' => $p['old_price'] !== null ? (float) $p['old_price'] : null,
        'rating' => (float) $p['rating'],
        'reviews' => (int) $p['reviews_count'],
        'stock' => (int) $p['stock'],
        'badge' => $p['badge'],
        'images' => $p['images'],
        'colors' => $p['colors'],
        'sizes' => $p['sizes'],
        'description' => $p['description'],
    ];
}, $allProducts);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

    <div class="container section-pad pb-3">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-custom">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Wishlist</li>
        </ol>
      </nav>
      <h1 class="section-title mb-0">My Wishlist</h1>
    </div>

    <div class="container pb-5">
      <div id="wishlistContent"></div>
    </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
      const PRODUCTS = <?= json_encode($productsForJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="assets/js/common.js"></script>
    <script>
      function renderWishlist() {
        const w = getWishlist();
        const items = PRODUCTS.filter(p => w.includes(p.id));
        const container = document.getElementById('wishlistContent');
        if (items.length === 0) {
          container.innerHTML = `
            <div class="empty-state" data-aos="fade-up">
              <i class="bi bi-heart"></i>
              <h3>Your wishlist is empty</h3>
              <p class="text-muted-2 mb-4">Save your favorite items here for later.</p>
              <a href="shop.php" class="btn-brand">Browse Products</a>
            </div>`;
          return;
        }
        container.innerHTML = `
          <p class="text-muted-2 mb-3">${items.length} item${items.length!==1?'s':''} in your wishlist</p>
          <div class="row g-3">
            ${items.map((p, i) => `<div class="col-lg-3 col-md-4 col-6" data-aos="fade-up" data-aos-delay="${(i%4)*60}">
              <div class="position-relative">${renderProductCard(p)}
                <button class="btn btn-sm btn-outline-danger position-absolute" style="top:8px;right:8px;z-index:3;background:var(--surface);" onclick="removeWish(${p.id})"><i class="bi bi-x-lg"></i></button>
              </div>
            </div>`).join('')}
          </div>`;
        if (window.AOS) AOS.refresh();
        syncWishlistUI();
      }
      function removeWish(id) {
        // toggleWishlist() already knows how to remove an id, persist the
        // change (server call for logged-in users, localStorage for
        // guests) and show a toast - reuse it instead of only touching
        // local state, which is what caused the removal not to stick:
        // the old code called setWishlist() directly, which never told
        // the server, so the very next wishlist reload brought the item
        // back.
        if (!isWishlisted(id)) return;
        toggleWishlist(id);
        renderWishlist();
      }
      function onWishlistLoaded() { renderWishlist(); }

      initCommonPhp();
      // Render immediately from whatever local state we have (instant,
      // no flash of blank content). For logged-in users, initCommonPhp()
      // also kicks off an async fetch of the authoritative server-side
      // wishlist in the background; once that resolves it calls
      // onWishlistLoaded() -> renderWishlist() again to correct anything
      // that was stale. Now that removeWish() actually persists removals
      // to the server (see above), that second render agrees with this
      // one instead of undoing it.
      renderWishlist();
    </script>
  </body>
</html>
