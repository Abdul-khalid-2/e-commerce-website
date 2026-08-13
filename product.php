<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/product-card.php';

use App\Models\Category;
use App\Models\Product;

$activePage = 'shop';
$basePath = '';

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = $productId > 0 ? Product::find($productId) : null;
if (!$product) {
    $product = Product::first();
}

if (!$product) {
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/navbar.php';
    echo '<div class="container section-pad text-center"><h3>No products available.</h3></div>';
    require __DIR__ . '/includes/footer.php';
    echo '</body></html>';
    exit;
}

$category = $product['category_id'] ? Category::find((int) $product['category_id']) : null;
$categoryName = $category['name'] ?? '';

$pageTitle = $product['name'] . ' - ShopMate Pakistan';

$price = (float) $product['price'];
$oldPrice = $product['old_price'] !== null ? (float) $product['old_price'] : null;
$discount = $oldPrice ? (int) round((1 - $price / $oldPrice) * 100) : 0;

$categoryNameById = array_column(Category::active(), 'name', 'id');

$allProducts = Product::allActiveWithRelations();
foreach ($allProducts as &$p) {
    $p['category_name'] = $categoryNameById[$p['category_id']] ?? '';
}
unset($p);

$sameCategory = array_values(array_filter(
    $allProducts,
    fn($p) => $p['category_id'] == $product['category_id'] && (int) $p['id'] !== (int) $product['id']
));
$related = array_slice($sameCategory, 0, 4);
if (count($related) < 4) {
    $relatedIds = array_column($related, 'id');
    $others = array_values(array_filter(
        $allProducts,
        fn($p) => (int) $p['id'] !== (int) $product['id'] && !in_array($p['id'], $relatedIds, true)
    ));
    $related = array_slice(array_merge($related, $others), 0, 4);
}

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
          <li class="breadcrumb-item"><a href="shop.php?category=<?= urlencode($categoryName) ?>"><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></a></li>
          <li class="breadcrumb-item active"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></li>
        </ol>
      </nav>
      <div id="productDetail">
        <div class="row g-4">
          <div class="col-lg-5" data-aos="fade-right">
            <div class="gallery-main" id="galleryMain" onmousemove="handleZoom(event)" onmouseleave="resetZoom()">
              <img src="<?= htmlspecialchars($product['images'][0] ?? '', ENT_QUOTES, 'UTF-8') ?>" id="mainImage" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="d-flex gap-2 mt-3" id="thumbnails">
<?php foreach ($product['images'] as $i => $img): ?>
              <div class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>" style="width:80px;" onclick="switchImage(<?= $i ?>)">
                <img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> <?= $i + 1 ?>">
              </div>
<?php endforeach; ?>
            </div>
          </div>
          <div class="col-lg-4" data-aos="fade-up">
            <span class="product-cat-tag"><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></span>
            <h2 class="fw-700 mt-1 mb-2"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="d-flex align-items-center gap-2 mb-3">
              <?= render_stars((float) $product['rating']) ?>
              <span class="rating-text"><?= $product['rating'] ?> (<?= (int) $product['reviews_count'] ?> reviews)</span>
            </div>
            <div class="mb-3">
              <span class="product-price fs-3"><?= format_pkr($price) ?></span>
<?php if ($oldPrice): ?>
              <span class="product-old-price fs-5"><?= format_pkr($oldPrice) ?></span>
<?php endif; ?>
<?php if ($discount > 0): ?>
              <span class="badge bg-success ms-2">Save <?= $discount ?>%</span>
<?php endif; ?>
            </div>
            <p class="text-muted-2 mb-3"><?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p class="fs-7 mb-3"><i class="bi bi-<?= $product['stock'] > 0 ? 'check-circle text-success' : 'x-circle text-danger' ?>"></i> <?= $product['stock'] > 0 ? 'In Stock (' . (int) $product['stock'] . ' available)' : 'Out of Stock' ?></p>

<?php if (count($product['colors']) > 1): ?>
            <div class="mb-3">
              <span class="fw-600 d-block mb-2">Color: <span id="selectedColor"><?= htmlspecialchars($product['colors'][0], ENT_QUOTES, 'UTF-8') ?></span></span>
<?php foreach ($product['colors'] as $i => $c): ?>
              <span class="option-chip <?= $i === 0 ? 'active' : '' ?>" data-color="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>" onclick="selectColor(this,'<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>')"><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></span>
<?php endforeach; ?>
            </div>
<?php endif; ?>

<?php if (count($product['sizes']) > 1): ?>
            <div class="mb-3">
              <span class="fw-600 d-block mb-2">Size/Variant: <span id="selectedSize"><?= htmlspecialchars($product['sizes'][0], ENT_QUOTES, 'UTF-8') ?></span></span>
<?php foreach ($product['sizes'] as $i => $s): ?>
              <span class="option-chip <?= $i === 0 ? 'active' : '' ?>" data-size="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" onclick="selectSize(this,'<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>')"><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></span>
<?php endforeach; ?>
            </div>
<?php endif; ?>

            <div class="mb-3">
              <span class="fw-600 d-block mb-2">Quantity:</span>
              <div class="qty-selector">
                <button class="qty-btn" onclick="changeQty(-1)"><i class="bi bi-dash"></i></button>
                <input type="text" class="qty-val" id="qtyVal" value="1" readonly>
                <button class="qty-btn" onclick="changeQty(1)"><i class="bi bi-plus"></i></button>
              </div>
            </div>

            <div class="d-flex gap-2 flex-wrap mb-3">
              <button class="btn-brand flex-grow-1" onclick="addProductToCart()"><i class="bi bi-cart-plus me-1"></i> Add to Cart</button>
              <button class="btn-accent flex-grow-1" onclick="buyNow()"><i class="bi bi-lightning-charge me-1"></i> Buy Now</button>
            </div>
            <button class="btn-outline-brand w-100" onclick="toggleWishlist(<?= (int) $product['id'] ?>); updateWishBtn()"><i class="bi bi-heart" id="wishIcon"></i> <span id="wishText">Add to Wishlist</span></button>

            <div class="mt-4 pt-3 border-soft border-top">
              <div class="row g-2 text-center">
                <div class="col-4"><i class="bi bi-truck text-brand fs-4"></i><p class="fs-8 text-muted-2 mt-1 mb-0">Free Delivery</p></div>
                <div class="col-4"><i class="bi bi-arrow-repeat text-brand fs-4"></i><p class="fs-8 text-muted-2 mt-1 mb-0">7-Day Returns</p></div>
                <div class="col-4"><i class="bi bi-shield-check text-brand fs-4"></i><p class="fs-8 text-muted-2 mt-1 mb-0">Warranty</p></div>
              </div>
            </div>
          </div>

          <div class="col-lg-3" data-aos="fade-left">
            <div class="summary-card">
              <h6 class="fw-700 mb-3">Order Summary</h6>
              <div class="summary-row"><span>Price</span><span><?= format_pkr($price) ?></span></div>
              <div class="summary-row"><span>Quantity</span><span id="summaryQty">1</span></div>
              <div class="summary-row"><span>Delivery</span><span class="text-success">FREE</span></div>
              <div class="summary-total d-flex justify-content-between"><span>Total</span><span class="text-brand" id="summaryTotal"><?= format_pkr($price) ?></span></div>
              <button class="btn-brand w-100 mt-3" onclick="buyNow()">Buy Now</button>
            </div>
          </div>
        </div>

        <div class="row mt-5" data-aos="fade-up">
          <div class="col-12">
            <ul class="nav nav-tabs tab-custom" id="prodTabs" role="tablist">
              <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#desc">Description</button></li>
              <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#specs">Specifications</button></li>
              <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#reviews">Reviews (<?= (int) $product['reviews_count'] ?>)</button></li>
            </ul>
            <div class="tab-content p-4 bg-surface rounded-bottom shadow-soft" style="border:1px solid var(--border);border-top:none;">
              <div class="tab-pane fade show active" id="desc">
                <p class="text-muted-2 mb-0"><?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-muted-2 mt-3">This product is carefully selected and quality-checked to ensure you get the best value for your money. Shop with confidence at ShopMate Pakistan.</p>
              </div>
              <div class="tab-pane fade" id="specs">
                <table class="table">
                  <tbody>
<?php foreach ($product['specs'] as $k => $v): ?>
                    <tr><td class="fw-600" style="width:40%"><?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?></td></tr>
<?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div class="tab-pane fade" id="reviews">
                <div class="d-flex align-items-center gap-4 mb-4 flex-wrap">
                  <div class="text-center">
                    <div class="display-5 fw-700 text-brand"><?= $product['rating'] ?></div>
                    <?= render_stars((float) $product['rating']) ?>
                    <p class="text-muted-2 fs-7 mt-1"><?= (int) $product['reviews_count'] ?> reviews</p>
                  </div>
                  <div class="flex-grow-1" style="min-width:200px;">
<?php
                    $roundedRating = (int) round((float) $product['rating']);
                    foreach ([5, 4, 3, 2, 1] as $s):
                        $pct = $s === $roundedRating ? 60 : ($s === $roundedRating - 1 ? 25 : 5);
?>
                    <div class="d-flex align-items-center gap-2 mb-1"><span class="fs-8"><?= $s ?> <i class="bi bi-star-fill text-warning fs-8"></i></span><div style="flex:1;height:8px;background:var(--bg);border-radius:4px;"><div style="width:<?= $pct ?>%;height:100%;background:var(--brand);border-radius:4px;"></div></div><span class="fs-8 text-muted-2"><?= $pct ?>%</span></div>
<?php endforeach; ?>
                  </div>
                </div>
                <hr class="border-soft">
<?php
                $sampleReviews = [
                    ['name' => 'Sana Khan', 'date' => '2 days ago', 'rating' => 5, 'text' => 'Excellent product! Exactly as described. Delivery was quick.'],
                    ['name' => 'Ali Raza', 'date' => '1 week ago', 'rating' => 4, 'text' => 'Good quality and value for money. Recommended.'],
                    ['name' => 'Hira Aslam', 'date' => '2 weeks ago', 'rating' => 5, 'text' => 'Very happy with my purchase. Will buy again from ShopMate.'],
                ];
                foreach ($sampleReviews as $r):
?>
                <div class="review-card">
                  <div class="d-flex justify-content-between mb-2">
                    <div><strong><?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?></strong><br><small class="text-muted-2"><?= htmlspecialchars($r['date'], ENT_QUOTES, 'UTF-8') ?></small></div>
                    <?= render_stars((float) $r['rating']) ?>
                  </div>
                  <p class="text-muted-2 mb-0"><?= htmlspecialchars($r['text'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
<?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <section class="container pb-5">
      <h3 class="fw-700 mb-4" data-aos="fade-up">You May Also Like</h3>
      <div class="row g-3" id="relatedProducts">
<?php foreach ($related as $i => $p): ?>
        <div class="col-lg-3 col-md-6 col-6" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>"><?= render_product_card($p) ?></div>
<?php endforeach; ?>
      </div>
    </section>

<?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
      const PRODUCTS = <?= json_encode($productsForJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
      const product = PRODUCTS.find(p => p.id === <?= (int) $product['id'] ?>);
    </script>
    <script src="assets/js/common.js"></script>
    <script>
      let selectedColor = product.colors[0];
      let selectedSize = product.sizes[0];
      let selectedQty = 1;
      let currentImage = 0;

      function switchImage(i) {
        currentImage = i;
        document.getElementById('mainImage').src = product.images[i];
        document.querySelectorAll('.gallery-thumb').forEach((t, idx) => t.classList.toggle('active', idx === i));
      }
      function handleZoom(e) {
        const main = document.getElementById('galleryMain');
        const img = document.getElementById('mainImage');
        const rect = main.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * 100;
        const y = ((e.clientY - rect.top) / rect.height) * 100;
        img.style.transformOrigin = `${x}% ${y}%`;
        main.classList.add('zoomed');
      }
      function resetZoom() {
        document.getElementById('galleryMain').classList.remove('zoomed');
      }
      function selectColor(el, c) {
        selectedColor = c;
        document.querySelectorAll('.option-chip[data-color]').forEach(chip => chip.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('selectedColor').textContent = c;
      }
      function selectSize(el, s) {
        selectedSize = s;
        document.querySelectorAll('.option-chip[data-size]').forEach(chip => chip.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('selectedSize').textContent = s;
      }
      function changeQty(d) {
        selectedQty = Math.max(1, selectedQty + d);
        document.getElementById('qtyVal').value = selectedQty;
        document.getElementById('summaryQty').textContent = selectedQty;
        document.getElementById('summaryTotal').textContent = formatPKR(product.price * selectedQty);
      }
      function addProductToCart() {
        addToCart(product.id, selectedQty, { color: selectedColor, size: selectedSize });
      }
      async function buyNow() {
        await addToCart(product.id, selectedQty, { color: selectedColor, size: selectedSize });
        window.location.href = 'checkout.php';
      }
      function updateWishBtn() {
        const wished = isWishlisted(product.id);
        document.getElementById('wishIcon').className = 'bi bi-heart' + (wished ? '-fill' : '');
        document.getElementById('wishText').textContent = (wished ? 'Remove from' : 'Add to') + ' Wishlist';
      }

      initCommonPhp();
      updateWishBtn();
    </script>
  </body>
</html>
