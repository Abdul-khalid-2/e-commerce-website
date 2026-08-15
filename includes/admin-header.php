<?php
/**
 * includes/admin-header.php
 *
 * Opens the HTML document and renders the sidebar. The including page
 * (already inside admin/, so paths are one level up) should set:
 *   $pageTitle      browser tab title
 *   $activeSection  'dashboard' | 'products' | 'orders' | 'customers' |
 *                   'categories' | 'settings' - highlights the matching
 *                   sidebar link
 */

declare(strict_types=1);

use App\Core\Csrf;
use App\Models\ContactMessage;

$pageTitle ??= 'Admin - ShopMate Pakistan';
$activeSection ??= 'dashboard';

$sectionTitles = [
    'dashboard' => 'Dashboard Overview',
    'products' => 'Manage Products',
    'orders' => 'Orders',
    'customers' => 'Customers',
    'categories' => 'Categories',
    'messages' => 'Contact Messages',
    'settings' => 'Settings',
];
$sectionTitle = $sectionTitles[$activeSection] ?? 'Admin';
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$unreadMessageCount = ContactMessage::countByStatus('New');
?><!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
      body { background: var(--bg); }
      .admin-content-area { padding: 0; }
    </style>
  </head>
  <body>
    <script>window.CSRF_TOKEN = <?= json_encode(Csrf::token()) ?>;</script>
    <div class="admin-overlay" id="adminOverlay" onclick="toggleSidebar()"></div>

    <aside class="admin-sidebar" id="adminSidebar">
      <div class="px-3 mb-4 d-flex align-items-center gap-2">
        <i class="bi bi-bag-check-fill text-brand fs-3"></i>
        <span class="navbar-brand-text">ShopMate</span>
      </div>
      <ul class="nav flex-column">
        <li><a class="nav-link <?= $activeSection === 'dashboard' ? 'active' : '' ?>" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li><a class="nav-link <?= $activeSection === 'products' ? 'active' : '' ?>" href="products.php"><i class="bi bi-box-seam"></i> Products</a></li>
        <li><a class="nav-link <?= $activeSection === 'orders' ? 'active' : '' ?>" href="orders.php"><i class="bi bi-receipt"></i> Orders</a></li>
        <li><a class="nav-link <?= $activeSection === 'customers' ? 'active' : '' ?>" href="customers.php"><i class="bi bi-people"></i> Customers</a></li>
        <li><a class="nav-link <?= $activeSection === 'categories' ? 'active' : '' ?>" href="categories.php"><i class="bi bi-grid"></i> Categories</a></li>
        <li><a class="nav-link <?= $activeSection === 'messages' ? 'active' : '' ?> d-flex align-items-center justify-content-between" href="messages.php"><span><i class="bi bi-envelope"></i> Messages</span><?php if ($unreadMessageCount > 0): ?><span class="badge bg-danger rounded-pill"><?= $unreadMessageCount ?></span><?php endif; ?></a></li>
        <li><a class="nav-link <?= $activeSection === 'settings' ? 'active' : '' ?>" href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>
        <li class="mt-3"><a class="nav-link" href="../index.php"><i class="bi bi-box-arrow-left"></i> Back to Store</a></li>
        <li><a class="nav-link" href="logout.php"><i class="bi bi-power"></i> Logout</a></li>
      </ul>
    </aside>

    <main class="admin-main">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-light d-lg-none" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
          <h3 class="fw-700 mb-0"><?= htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button class="theme-toggle" onclick="toggleTheme()"><i class="bi bi-moon-fill" id="themeIcon"></i></button>
          <div class="d-flex align-items-center gap-2">
            <img src="https://images.pexels.com/photos/5869609/pexels-photo-5869609.jpeg?auto=compress&cs=tinysrgb&h=80&w=80" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
            <div class="d-none d-sm-block"><div class="fw-600 fs-7"><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></div><small class="text-muted-2">Administrator</small></div>
          </div>
        </div>
      </div>
