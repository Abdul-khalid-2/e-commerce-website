<?php
/**
 * database/seed.php
 *
 * Loads the same product/category/testimonial data that used to live in
 * assets/js/data.js into the database, via the Models — so the seed
 * script itself doubles as a smoke test that the models work.
 *
 * Usage:
 *   php database/seed.php            Seed only if categories table is empty
 *   php database/seed.php --fresh    Wipe all seedable tables first, then seed
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

use App\Core\Database;
use App\Core\Str;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

$db = Database::getConnection();
$fresh = in_array('--fresh', $argv, true);

if ($fresh) {
    echo 'Clearing existing data (--fresh)...' . PHP_EOL;
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ([
        'product_specs', 'product_sizes', 'product_colors', 'product_images',
        'wishlists', 'cart_items', 'carts', 'order_items', 'orders', 'addresses',
        'products', 'categories', 'hero_slides', 'testimonials', 'trust_badges',
        'settings', 'users',
    ] as $table) {
        $db->exec("TRUNCATE TABLE {$table}");
    }
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
}

$existing = Category::count();
if ($existing > 0 && !$fresh) {
    echo "Categories table already has {$existing} row(s) — nothing to do." . PHP_EOL;
    echo 'Run `php database/seed.php --fresh` to wipe and reseed.' . PHP_EOL;
    exit(0);
}

// ---------------------------------------------------------------------
// Categories
// ---------------------------------------------------------------------
echo 'Seeding categories...' . PHP_EOL;

$categoriesData = [
    ['name' => 'Electronics', 'icon' => 'bi-phone', 'image' => 'https://images.pexels.com/photos/7068406/pexels-photo-7068406.jpeg?auto=compress&cs=tinysrgb&h=400&w=400'],
    ['name' => 'Fashion', 'icon' => 'bi-bag-heart', 'image' => 'https://images.pexels.com/photos/8386663/pexels-photo-8386663.jpeg?auto=compress&cs=tinysrgb&h=400&w=400'],
    ['name' => 'Home & Living', 'icon' => 'bi-house', 'image' => 'https://images.pexels.com/photos/7573934/pexels-photo-7573934.jpeg?auto=compress&cs=tinysrgb&h=400&w=400'],
    ['name' => 'Beauty', 'icon' => 'bi-palette', 'image' => 'https://images.pexels.com/photos/12969358/pexels-photo-12969358.jpeg?auto=compress&cs=tinysrgb&h=400&w=400'],
    ['name' => 'Grocery', 'icon' => 'bi-basket', 'image' => 'https://images.pexels.com/photos/4177709/pexels-photo-4177709.jpeg?auto=compress&cs=tinysrgb&h=400&w=400'],
    ['name' => 'Kids & Toys', 'icon' => 'bi-emoji-smile', 'image' => 'https://images.pexels.com/photos/311268/pexels-photo-311268.jpeg?auto=compress&cs=tinysrgb&h=400&w=400'],
];

$categoryIds = []; // name => id
foreach ($categoriesData as $i => $cat) {
    $id = Category::create([
        'name' => $cat['name'],
        'slug' => Str::slug($cat['name']),
        'icon' => $cat['icon'],
        'image' => $cat['image'],
        'is_active' => 1,
        'sort_order' => $i,
    ]);
    $categoryIds[$cat['name']] = $id;
}
echo '  ' . count($categoriesData) . ' categories created.' . PHP_EOL;

// ---------------------------------------------------------------------
// Products
// ---------------------------------------------------------------------
echo 'Seeding products...' . PHP_EOL;

$productsData = require __DIR__ . '/seeds/products.php';

$createdSlugs = [];
foreach ($productsData as $p) {
    $slug = Str::slug($p['name']);
    // Guard against duplicate slugs if two products share a name.
    $unique = $slug;
    $n = 2;
    while (in_array($unique, $createdSlugs, true)) {
        $unique = $slug . '-' . $n;
        $n++;
    }
    $createdSlugs[] = $unique;

    Product::createFull(
        [
            'category_id' => $categoryIds[$p['category']] ?? null,
            'name' => $p['name'],
            'slug' => $unique,
            'brand' => $p['brand'],
            'price' => $p['price'],
            'old_price' => $p['oldPrice'] ?? null,
            'rating' => $p['rating'],
            'reviews_count' => $p['reviews'],
            'stock' => $p['stock'],
            'badge' => $p['badge'] ?? null,
            'description' => $p['description'],
            'is_active' => 1,
        ],
        $p['images'],
        $p['colors'],
        $p['sizes'],
        $p['specs']
    );
}
echo '  ' . count($productsData) . ' products created.' . PHP_EOL;

// ---------------------------------------------------------------------
// Hero slides
// ---------------------------------------------------------------------
echo 'Seeding hero slides...' . PHP_EOL;

$heroSlides = [
    ['title' => 'Mega Tech Sale', 'subtitle' => 'Up to 40% off on smartphones, laptops & accessories', 'image' => 'https://images.pexels.com/photos/7987759/pexels-photo-7987759.jpeg?auto=compress&cs=tinysrgb&h=800&w=1600', 'cta_text' => 'Shop Electronics', 'cta_link' => 'shop.php?category=Electronics'],
    ['title' => 'Fashion Week Deals', 'subtitle' => 'New season styles starting at Rs. 2,999', 'image' => 'https://images.pexels.com/photos/8386663/pexels-photo-8386663.jpeg?auto=compress&cs=tinysrgb&h=800&w=1600', 'cta_text' => 'Shop Fashion', 'cta_link' => 'shop.php?category=Fashion'],
    ['title' => 'Home Makeover Sale', 'subtitle' => 'Transform your space with up to 30% off', 'image' => 'https://images.pexels.com/photos/7573934/pexels-photo-7573934.jpeg?auto=compress&cs=tinysrgb&h=800&w=1600', 'cta_text' => 'Shop Home', 'cta_link' => 'shop.php?category=Home+%26+Living'],
];
$stmt = $db->prepare(
    'INSERT INTO hero_slides (title, subtitle, image, cta_text, cta_link, sort_order) VALUES (:title, :subtitle, :image, :cta_text, :cta_link, :sort)'
);
foreach ($heroSlides as $i => $slide) {
    $stmt->execute([...$slide, 'sort' => $i]);
}
echo '  ' . count($heroSlides) . ' hero slides created.' . PHP_EOL;

// ---------------------------------------------------------------------
// Testimonials
// ---------------------------------------------------------------------
echo 'Seeding testimonials...' . PHP_EOL;

$testimonials = [
    ['customer_name' => 'Ayesha Khan', 'city' => 'Karachi', 'rating' => 5, 'review_text' => 'Amazing shopping experience! The delivery was super fast and the product quality exceeded my expectations. Will definitely shop again.', 'avatar' => 'https://images.pexels.com/photos/29810657/pexels-photo-29810657.jpeg?auto=compress&cs=tinysrgb&h=200&w=200'],
    ['customer_name' => 'Bilal Ahmed', 'city' => 'Lahore', 'rating' => 5, 'review_text' => 'Best online store in Pakistan. Cash on delivery option made it so convenient. Highly recommended!', 'avatar' => 'https://images.pexels.com/photos/32843813/pexels-photo-32843813.jpeg?auto=compress&cs=tinysrgb&h=200&w=200'],
    ['customer_name' => 'Fatima Malik', 'city' => 'Islamabad', 'rating' => 4, 'review_text' => 'Great prices and excellent customer service. The return process was hassle-free when I needed to exchange a size.', 'avatar' => 'https://images.pexels.com/photos/5869609/pexels-photo-5869609.jpeg?auto=compress&cs=tinysrgb&h=200&w=200'],
];
$stmt = $db->prepare(
    'INSERT INTO testimonials (customer_name, city, rating, review_text, avatar, sort_order) VALUES (:customer_name, :city, :rating, :review_text, :avatar, :sort)'
);
foreach ($testimonials as $i => $t) {
    $stmt->execute([...$t, 'sort' => $i]);
}
echo '  ' . count($testimonials) . ' testimonials created.' . PHP_EOL;

// ---------------------------------------------------------------------
// Trust badges
// ---------------------------------------------------------------------
echo 'Seeding trust badges...' . PHP_EOL;

$trustBadges = [
    ['icon' => 'bi-truck', 'title' => 'Free Delivery', 'text' => 'On orders above Rs. 2,000'],
    ['icon' => 'bi-cash-coin', 'title' => 'Cash on Delivery', 'text' => 'Pay when you receive'],
    ['icon' => 'bi-arrow-repeat', 'title' => 'Easy Returns', 'text' => '7-day return policy'],
    ['icon' => 'bi-shield-check', 'title' => 'Secure Payment', 'text' => '100% protected payments'],
];
$stmt = $db->prepare(
    'INSERT INTO trust_badges (icon, title, text, sort_order) VALUES (:icon, :title, :text, :sort)'
);
foreach ($trustBadges as $i => $b) {
    $stmt->execute([...$b, 'sort' => $i]);
}
echo '  ' . count($trustBadges) . ' trust badges created.' . PHP_EOL;

// ---------------------------------------------------------------------
// Settings
// ---------------------------------------------------------------------
echo 'Seeding settings...' . PHP_EOL;

Setting::set('store_name', 'ShopMate Pakistan');
Setting::set('contact_email', 'support@shopmate.pk');
Setting::set('contact_phone', '0300-1234567');
Setting::set('currency', 'PKR');
Setting::set('free_shipping_threshold', '2000');
echo '  5 settings created.' . PHP_EOL;

// ---------------------------------------------------------------------
// Default admin user
// ---------------------------------------------------------------------
echo 'Seeding admin user...' . PHP_EOL;

$adminEmail = 'admin@shopmate.pk';
$adminPassword = 'Admin@12345';

if (!User::findByEmail($adminEmail)) {
    User::register('Store Admin', $adminEmail, $adminPassword, 'admin');
    echo "  Admin user created — email: {$adminEmail}  password: {$adminPassword}" . PHP_EOL;
    echo '  Change this password once the login page is built.' . PHP_EOL;
} else {
    echo '  Admin user already exists, skipped.' . PHP_EOL;
}

echo PHP_EOL . 'Seeding complete.' . PHP_EOL;
