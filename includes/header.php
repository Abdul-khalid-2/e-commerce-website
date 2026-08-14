<?php
/**
 * includes/header.php
 *
 * Opens the HTML document and <body>. The including page should set,
 * before requiring this file:
 *   $pageTitle  (optional) - browser tab title
 *   $basePath   (optional) - '' for root-level pages, '../' for pages
 *               one directory deep (e.g. admin/). Defaults to ''.
 *
 * The including page is responsible for closing </body></html> itself
 * (usually via includes/footer.php) and for its own <script> tags,
 * since those differ per page.
 */

declare(strict_types=1);

use App\Core\Csrf;

$pageTitle ??= 'ShopMate Pakistan - Online Shopping Store';
$basePath ??= '';
?><!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta property="og:image" content="https://bolt.new/static/og_default.png" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:image" content="https://bolt.new/static/og_default.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/style.css">
  </head>
  <body>
    <script>window.CSRF_TOKEN = <?= json_encode(Csrf::token()) ?>;</script>

