<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/product-card.php';

use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Product;
use App\Models\Testimonial;
use App\Models\TrustBadge;

$pageTitle = 'ShopMate Pakistan - Online Shopping Store';
$activePage = 'home';
$basePath = '';

$categories = Category::active();
$categoryNameById = array_column($categories, 'name', 'id');

$heroSlides = HeroSlide::active();
$trustBadges = TrustBadge::active();
$testimonials = Testimonial::active();

$allProducts = Product::allActiveWithRelations();
foreach ($allProducts as &$p) {
    $p['category_name'] = $categoryNameById[$p['category_id']] ?? '';
}
unset($p);

$featuredProducts = array_slice($allProducts, 0, 8);
$bestSellers = array_values(array_filter(
    $allProducts,
    fn($p) => in_array($p['badge'], ['Best Seller', 'Top Rated'], true)
));
$bestSellers = array_slice($bestSellers, 0, 8);

// Bridge for the client-side cart/wishlist/search/quick-view JS, which
// still needs a full product list to look items up by id (see
// assets/js/common.js). Field names match what that JS expects.
$productsForJs = array_map(static function (array $p): array {
    return [
        'id' => (int) $p['id'],
        'name' => $p['name'],
        'category' => $p['category_name'],
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

    <!-- Hero Carousel -->
    <section class="hero-section">
      <div id="heroCarousel" class="position-relative">
<?php foreach ($heroSlides as $i => $slide): ?>
        <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>" style="display:<?= $i === 0 ? 'flex' : 'none' ?>;">
          <div class="hero-slide-bg" style="background-image:url('<?= htmlspecialchars($slide['image'], ENT_QUOTES, 'UTF-8') ?>');"></div>
          <div class="container">
            <div class="hero-content">
              <h1 class="hero-title"><?= htmlspecialchars($slide['title'], ENT_QUOTES, 'UTF-8') ?></h1>
              <p class="hero-sub"><?= htmlspecialchars($slide['subtitle'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
              <a href="<?= htmlspecialchars($slide['cta_link'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" class="btn-brand btn-lg"><?= htmlspecialchars($slide['cta_text'] ?? 'Shop Now', ENT_QUOTES, 'UTF-8') ?> <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
          </div>
        </div>
<?php endforeach; ?>
        <button class="hero-arrow prev" onclick="changeHero(-1)"><i class="bi bi-chevron-left"></i></button>
        <button class="hero-arrow next" onclick="changeHero(1)"><i class="bi bi-chevron-right"></i></button>
        <div class="hero-dots">
<?php foreach ($heroSlides as $i => $slide): ?>
          <button class="hero-dot <?= $i === 0 ? 'active' : '' ?>" onclick="goToHero(<?= $i ?>)"></button>
<?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Trust Badges -->
    <section class="container section-pad pb-0">
      <div class="row g-2" id="trustBadges">
<?php foreach ($trustBadges as $i => $t): ?>
        <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
          <div class="trust-badge"><i class="bi <?= htmlspecialchars($t['icon'], ENT_QUOTES, 'UTF-8') ?>"></i><h6 class="fw-700"><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></h6><p class="text-muted-2 fs-7 mb-0"><?= htmlspecialchars($t['text'], ENT_QUOTES, 'UTF-8') ?></p></div>
        </div>
<?php endforeach; ?>
      </div>
    </section>

    <!-- Shop by Category -->
    <section class="section-pad">
      <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
          <h2 class="section-title">Shop by Category</h2>
          <p class="section-sub">Explore our wide range of product categories</p>
        </div>
        <div class="row g-3" id="categoryGrid">
<?php foreach ($categories as $i => $c): ?>
          <div class="col-lg-2 col-md-4 col-6" data-aos="zoom-in" data-aos-delay="<?= $i * 60 ?>">
            <a href="shop.php?category=<?= urlencode($c['name']) ?>" class="cat-card d-block">
              <img src="<?= htmlspecialchars($c['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
              <div class="cat-card-body"><i class="bi <?= htmlspecialchars($c['icon'], ENT_QUOTES, 'UTF-8') ?>"></i><h5><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></h5></div>
            </a>
          </div>
<?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Featured Products -->
    <section class="section-pad pt-0">
      <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4" data-aos="fade-up">
          <div>
            <h2 class="section-title">Featured Products</h2>
            <p class="section-sub">Handpicked products just for you</p>
          </div>
          <a href="shop.php" class="btn-outline-brand d-none d-sm-inline-flex">View All <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-3" id="featuredGrid">
<?php foreach ($featuredProducts as $i => $p): ?>
          <div class="col-lg-3 col-md-4 col-6" data-aos="fade-up" data-aos-delay="<?= ($i % 4) * 80 ?>"><?= render_product_card($p) ?></div>
<?php endforeach; ?>
        </div>
        <div class="text-center mt-4 d-sm-none">
          <a href="shop.php" class="btn-outline-brand">View All Products</a>
        </div>
      </div>
    </section>

    <!-- Best Sellers Carousel -->
    <section class="section-pad pt-0">
      <div class="container">
        <div class="text-center mb-4" data-aos="fade-up">
          <h2 class="section-title">Best Sellers</h2>
          <p class="section-sub">Our most popular products this week</p>
        </div>
        <div class="position-relative">
          <button class="hero-arrow prev" onclick="scrollBestSellers(-1)" style="left:-10px;"><i class="bi bi-chevron-left"></i></button>
          <div class="d-flex overflow-auto gap-3 pb-3" id="bestSellersRow" style="scroll-snap-type:x mandatory;scrollbar-width:none;">
<?php foreach ($bestSellers as $p): ?>
            <div style="min-width:240px;max-width:240px;scroll-snap-align:start;"><?= render_product_card($p) ?></div>
<?php endforeach; ?>
          </div>
          <button class="hero-arrow next" onclick="scrollBestSellers(1)" style="right:-10px;"><i class="bi bi-chevron-right"></i></button>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="section-pad pt-0">
      <div class="container">
        <div class="text-center mb-4" data-aos="fade-up">
          <h2 class="section-title">What Our Customers Say</h2>
          <p class="section-sub">Trusted by thousands of happy shoppers across Pakistan</p>
        </div>
        <div class="row g-3" id="testimonialGrid">
<?php foreach ($testimonials as $i => $t): ?>
          <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
            <div class="testimonial-card position-relative">
              <i class="bi bi-quote quote-icon"></i>
              <div class="d-flex align-items-center gap-3 mb-3">
                <img src="<?= htmlspecialchars($t['avatar'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="testimonial-avatar" alt="<?= htmlspecialchars($t['customer_name'], ENT_QUOTES, 'UTF-8') ?>">
                <div><h6 class="fw-700 mb-0"><?= htmlspecialchars($t['customer_name'], ENT_QUOTES, 'UTF-8') ?></h6><small class="text-muted-2"><?= htmlspecialchars($t['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>, Pakistan</small></div>
              </div>
              <?= render_stars((float) $t['rating']) ?>
              <p class="mt-2 mb-0 text-muted-2"><?= htmlspecialchars($t['review_text'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          </div>
<?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Newsletter -->
    <section class="section-pad pt-0">
      <div class="container">
        <div class="newsletter-section text-center" data-aos="zoom-in">
          <div class="position-relative" style="z-index:2;">
            <h3 class="fw-700 mb-2">Subscribe to Our Newsletter</h3>
            <p class="mb-4">Get exclusive deals, offers and updates delivered straight to your inbox</p>
            <form class="d-flex justify-content-center gap-2 flex-wrap" onsubmit="event.preventDefault(); showToast('Subscribed successfully!','success'); this.reset();">
              <input type="email" class="newsletter-input" placeholder="Enter your email address" required>
              <button type="submit" class="btn btn-light fw-600 rounded-pill px-4">Subscribe</button>
            </form>
          </div>
        </div>
      </div>
    </section>

<?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
      const PRODUCTS = <?= json_encode($productsForJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="assets/js/common.js"></script>
    <script>
      let heroIndex = 0;
      let heroTimer;
      const heroSlideCount = <?= count($heroSlides) ?>;

      function showHero(idx) {
        const slides = document.querySelectorAll('.hero-slide');
        const dots = document.querySelectorAll('.hero-dot');
        slides.forEach((s, i) => { s.style.display = i === idx ? 'flex' : 'none'; s.classList.toggle('active', i === idx); });
        dots.forEach((d, i) => d.classList.toggle('active', i === idx));
        heroIndex = idx;
      }
      function changeHero(dir) {
        if (heroSlideCount === 0) return;
        showHero((heroIndex + dir + heroSlideCount) % heroSlideCount);
        resetHeroTimer();
      }
      function goToHero(idx) { showHero(idx); resetHeroTimer(); }
      function resetHeroTimer() {
        clearInterval(heroTimer);
        if (heroSlideCount > 1) heroTimer = setInterval(() => changeHero(1), 5000);
      }

      function scrollBestSellers(dir) {
        document.getElementById('bestSellersRow').scrollBy({ left: dir * 300, behavior: 'smooth' });
      }
      function autoScrollBestSellers() {
        const row = document.getElementById('bestSellersRow');
        if (!row) return;
        if (row.scrollLeft + row.clientWidth >= row.scrollWidth - 10) {
          row.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
          row.scrollBy({ left: 1, behavior: 'auto' });
        }
      }

      initCommonPhp();
      resetHeroTimer();
      setInterval(autoScrollBestSellers, 40);
    </script>
  </body>
</html>
