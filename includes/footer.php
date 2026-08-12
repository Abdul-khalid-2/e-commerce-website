<?php
/**
 * includes/footer.php
 *
 * Replaces the old JS buildFooter() from assets/js/common.js. Contact
 * info comes from the `settings` table, so it can be changed from the
 * admin panel later without touching this file.
 *
 * The including page should set, before requiring this file:
 *   $basePath  (optional) - see includes/header.php
 */

declare(strict_types=1);

use App\Models\Setting;

$basePath ??= '';
$contactPhone = Setting::get('contact_phone', '+92 300 1234567');
$contactEmail = Setting::get('contact_email', 'support@shopmate.pk');
?>
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
            <li class="mb-2"><a href="<?= $basePath ?>index.php">Home</a></li>
            <li class="mb-2"><a href="<?= $basePath ?>shop.php">Shop</a></li>
            <li class="mb-2"><a href="<?= $basePath ?>about.html">About Us</a></li>
            <li class="mb-2"><a href="<?= $basePath ?>contact.html">Contact</a></li>
            <li class="mb-2"><a href="<?= $basePath ?>orders.html">Track Order</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-3 col-6">
          <h5>Customer</h5>
          <ul class="list-unstyled">
            <li class="mb-2"><a href="<?= $basePath ?>login.html">My Account</a></li>
            <li class="mb-2"><a href="<?= $basePath ?>wishlist.html">Wishlist</a></li>
            <li class="mb-2"><a href="<?= $basePath ?>cart.html">Cart</a></li>
            <li class="mb-2"><a href="#">Return Policy</a></li>
            <li class="mb-2"><a href="#">FAQs</a></li>
          </ul>
        </div>
        <div class="col-lg-4 col-md-6">
          <h5>Contact Info</h5>
          <ul class="list-unstyled text-muted-2">
            <li class="mb-2"><i class="bi bi-geo-alt me-2"></i> Main Boulevard, Gulberg III, Lahore, Pakistan</li>
            <li class="mb-2"><i class="bi bi-telephone me-2"></i> <?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?></li>
            <li class="mb-2"><i class="bi bi-envelope me-2"></i> <?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></li>
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
        <p class="text-muted-2 fs-7 mb-0">&copy; <?= date('Y') ?> ShopMate Pakistan. All rights reserved.</p>
        <p class="text-muted-2 fs-7 mb-0"><a href="#">Privacy Policy</a> &middot; <a href="#">Terms of Service</a></p>
      </div>
    </div>
  </footer>
  <button class="scroll-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Scroll to top"><i class="bi bi-arrow-up"></i></button>
