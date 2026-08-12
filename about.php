<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

use App\Models\Category;
use App\Models\Setting;
use App\Models\TrustBadge;

$pageTitle = 'About Us - ShopMate Pakistan';
$activePage = 'about';
$basePath = '';

$storeName = Setting::get('store_name', 'ShopMate Pakistan');
$trustBadges = TrustBadge::active();
$categoryCount = count(Category::active());

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

    <div class="container section-pad pb-3">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-custom">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">About Us</li>
        </ol>
      </nav>
    </div>

    <section class="container pb-5">
      <div class="row align-items-center g-4 mb-5" data-aos="fade-up">
        <div class="col-lg-7">
          <h1 class="section-title mb-3">About <?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?></h1>
          <p class="text-muted-2 fs-6 mb-3">
            <?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?> started with a simple goal: make online
            shopping in Pakistan easy, affordable, and trustworthy — for shoppers in Karachi, Lahore,
            Islamabad, and every city in between.
          </p>
          <p class="text-muted-2 mb-3">
            We work directly with local sellers and trusted brands to bring you a wide range of
            electronics, fashion, home essentials, beauty products, groceries, and toys — all in one
            place, with the payment options Pakistani shoppers actually want: Cash on Delivery,
            JazzCash, EasyPaisa, and bank transfer.
          </p>
          <p class="text-muted-2 mb-0">
            Every order is packed with care and shipped fast, because we know your time matters as
            much as your money.
          </p>
        </div>
        <div class="col-lg-5" data-aos="fade-left">
          <img src="https://images.pexels.com/photos/4483610/pexels-photo-4483610.jpeg?auto=compress&cs=tinysrgb&h=600&w=800" class="img-fluid rounded-4 shadow-soft" alt="ShopMate Pakistan warehouse team">
        </div>
      </div>

      <div class="row g-3 mb-5" data-aos="fade-up">
        <div class="col-md-3 col-6">
          <div class="stat-card text-center">
            <h2 class="fw-700 text-brand mb-0">50k+</h2>
            <p class="text-muted-2 fs-7 mb-0">Happy Customers</p>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-card text-center">
            <h2 class="fw-700 text-brand mb-0"><?= $categoryCount ?>+</h2>
            <p class="text-muted-2 fs-7 mb-0">Product Categories</p>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-card text-center">
            <h2 class="fw-700 text-brand mb-0">30+</h2>
            <p class="text-muted-2 fs-7 mb-0">Cities Delivered</p>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-card text-center">
            <h2 class="fw-700 text-brand mb-0">4.7<i class="bi bi-star-fill fs-6 ms-1"></i></h2>
            <p class="text-muted-2 fs-7 mb-0">Average Rating</p>
          </div>
        </div>
      </div>

      <div class="text-center mb-4" data-aos="fade-up">
        <h2 class="section-title">Why Shop With Us</h2>
        <p class="section-sub">The promises we make to every customer, every order</p>
      </div>
      <div class="row g-2">
<?php foreach ($trustBadges as $i => $t): ?>
        <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
          <div class="trust-badge"><i class="bi <?= htmlspecialchars($t['icon'], ENT_QUOTES, 'UTF-8') ?>"></i><h6 class="fw-700"><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></h6><p class="text-muted-2 fs-7 mb-0"><?= htmlspecialchars($t['text'], ENT_QUOTES, 'UTF-8') ?></p></div>
        </div>
<?php endforeach; ?>
      </div>
    </section>

    <section class="section-pad pt-0">
      <div class="container">
        <div class="newsletter-section text-center" data-aos="zoom-in">
          <div class="position-relative" style="z-index:2;">
            <h3 class="fw-700 mb-2">Have a Question?</h3>
            <p class="mb-4">Our team is here to help — reach out anytime</p>
            <a href="contact.php" class="btn btn-light fw-600 rounded-pill px-4">Contact Us</a>
          </div>
        </div>
      </div>
    </section>

<?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/common.js"></script>
    <script>
      initCommonPhp();
    </script>
  </body>
</html>
