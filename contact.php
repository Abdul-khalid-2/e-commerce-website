<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

use App\Models\Setting;

$pageTitle = 'Contact Us - ShopMate Pakistan';
$activePage = 'contact';
$basePath = '';

$storeName = Setting::get('store_name', 'ShopMate Pakistan');
$contactPhone = Setting::get('contact_phone', '+92 300 1234567');
$contactEmail = Setting::get('contact_email', 'support@shopmate.pk');

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

    <div class="container section-pad pb-3">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-custom">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Contact Us</li>
        </ol>
      </nav>
      <div class="text-center mb-4" data-aos="fade-up">
        <h1 class="section-title">Get in Touch</h1>
        <p class="section-sub">Questions about an order, a product, or anything else? We'd love to hear from you.</p>
      </div>
    </div>

    <section class="container pb-5">
      <div class="row g-4">
        <div class="col-lg-4" data-aos="fade-right">
          <div class="filter-sidebar h-100">
            <h5 class="fw-700 mb-3">Contact Information</h5>
            <div class="d-flex gap-3 mb-3">
              <i class="bi bi-geo-alt text-brand fs-5"></i>
              <div><strong>Address</strong><p class="text-muted-2 fs-7 mb-0">Main Boulevard, Gulberg III, Lahore, Pakistan</p></div>
            </div>
            <div class="d-flex gap-3 mb-3">
              <i class="bi bi-telephone text-brand fs-5"></i>
              <div><strong>Phone</strong><p class="text-muted-2 fs-7 mb-0"><?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?></p></div>
            </div>
            <div class="d-flex gap-3 mb-3">
              <i class="bi bi-envelope text-brand fs-5"></i>
              <div><strong>Email</strong><p class="text-muted-2 fs-7 mb-0"><?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></p></div>
            </div>
            <div class="d-flex gap-3 mb-4">
              <i class="bi bi-clock text-brand fs-5"></i>
              <div><strong>Hours</strong><p class="text-muted-2 fs-7 mb-0">Mon - Sat, 9:00 AM - 8:00 PM</p></div>
            </div>
            <h6 class="fw-700 mb-2">Follow Us</h6>
            <div class="d-flex gap-2">
              <a href="#" class="footer-social"><i class="bi bi-facebook"></i></a>
              <a href="#" class="footer-social"><i class="bi bi-instagram"></i></a>
              <a href="#" class="footer-social"><i class="bi bi-twitter-x"></i></a>
              <a href="#" class="footer-social"><i class="bi bi-whatsapp"></i></a>
            </div>
          </div>
        </div>

        <div class="col-lg-8" data-aos="fade-left">
          <div class="filter-sidebar mb-4">
            <h5 class="fw-700 mb-3">Send Us a Message</h5>
            <form id="contactForm" onsubmit="return submitContactForm(event)">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Your Name</label>
                  <input type="text" class="form-control" name="name" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email Address</label>
                  <input type="email" class="form-control" name="email" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Subject</label>
                  <input type="text" class="form-control" name="subject" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Message</label>
                  <textarea class="form-control" name="message" rows="5" required></textarea>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn-brand"><i class="bi bi-send me-1"></i> Send Message</button>
                </div>
              </div>
            </form>
          </div>

          <div class="filter-sidebar p-0" style="overflow:hidden;">
            <iframe
              title="ShopMate Pakistan location"
              src="https://www.google.com/maps?q=Gulberg+III,+Lahore,+Pakistan&output=embed"
              width="100%" height="320" style="border:0;display:block;" loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
        </div>
      </div>
    </section>

<?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/common.js"></script>
    <script>
      // No backend endpoint for contact messages yet (see README roadmap) -
      // this just confirms receipt to the visitor and resets the form.
      function submitContactForm(e) {
        e.preventDefault();
        showToast("Thanks! We'll get back to you soon.", 'success');
        document.getElementById('contactForm').reset();
        return false;
      }

      initCommonPhp();
    </script>
  </body>
</html>
