<?php
/**
 * database/seeds/products.php
 *
 * Returns the product catalog as a plain PHP array. This is a direct
 * conversion of the PRODUCTS array that used to live in
 * assets/js/data.js — same 16 products, same images/specs — just in
 * PHP so database/seed.php can insert it.
 */

declare(strict_types=1);

return [
    // Electronics
    [
        'name' => 'Samsung Galaxy A55 5G', 'category' => 'Electronics', 'brand' => 'Samsung',
        'price' => 89999, 'oldPrice' => 109999, 'rating' => 4.5, 'reviews' => 128, 'stock' => 15, 'badge' => 'Best Seller',
        'images' => [
            'https://images.pexels.com/photos/7068406/pexels-photo-7068406.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/20360361/pexels-photo-20360361.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/7438754/pexels-photo-7438754.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Black', 'Blue', 'Silver'], 'sizes' => ['128GB', '256GB'],
        'description' => 'The Samsung Galaxy A55 5G features a stunning Super AMOLED display, triple camera system, and long-lasting battery life — perfect for everyday use.',
        'specs' => ['Display' => '6.6" Super AMOLED', 'Processor' => 'Exynos 1480', 'RAM' => '8GB', 'Storage' => '256GB', 'Battery' => '5000mAh', 'Camera' => '50MP Triple'],
    ],
    [
        'name' => 'HP Pavilion Laptop 15', 'category' => 'Electronics', 'brand' => 'HP',
        'price' => 145000, 'oldPrice' => 165000, 'rating' => 4.3, 'reviews' => 86, 'stock' => 8, 'badge' => 'Sale',
        'images' => [
            'https://images.pexels.com/photos/8533587/pexels-photo-8533587.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/6968164/pexels-photo-6968164.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/11129922/pexels-photo-11129922.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Silver', 'Grey'], 'sizes' => ['i5', 'i7'],
        'description' => 'HP Pavilion 15 offers powerful performance with Intel Core processors, a sleek design, and all-day battery life for work and entertainment.',
        'specs' => ['Display' => '15.6" FHD', 'Processor' => 'Intel Core i5-1335U', 'RAM' => '16GB', 'Storage' => '512GB SSD', 'Battery' => '8 hours', 'Weight' => '1.74 kg'],
    ],
    [
        'name' => 'Sony WH-1000XM5 Headphones', 'category' => 'Electronics', 'brand' => 'Sony',
        'price' => 65000, 'oldPrice' => 78000, 'rating' => 4.8, 'reviews' => 215, 'stock' => 22, 'badge' => 'Top Rated',
        'images' => [
            'https://images.pexels.com/photos/7772548/pexels-photo-7772548.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/3394656/pexels-photo-3394656.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/33481395/pexels-photo-33481395.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Black', 'White'], 'sizes' => ['Standard'],
        'description' => 'Industry-leading noise cancellation with premium sound quality, 30-hour battery life, and comfortable over-ear design.',
        'specs' => ['Type' => 'Over-Ear', 'Noise Cancelling' => 'Yes', 'Battery' => '30 hours', 'Connectivity' => 'Bluetooth 5.2', 'Weight' => '250g'],
    ],
    [
        'name' => 'Apple iPhone 15 Pro', 'category' => 'Electronics', 'brand' => 'Apple',
        'price' => 399999, 'oldPrice' => 429999, 'rating' => 4.9, 'reviews' => 340, 'stock' => 5, 'badge' => 'Premium',
        'images' => [
            'https://images.pexels.com/photos/20360361/pexels-photo-20360361.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/7438754/pexels-photo-7438754.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/7068406/pexels-photo-7068406.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Titanium', 'Blue', 'Black'], 'sizes' => ['128GB', '256GB', '512GB'],
        'description' => 'The iPhone 15 Pro features a titanium design, A17 Pro chip, and pro camera system with 48MP main camera.',
        'specs' => ['Display' => '6.1" Super Retina XDR', 'Chip' => 'A17 Pro', 'Camera' => '48MP+12MP+12MP', 'Battery' => 'Up to 23 hours', 'Build' => 'Titanium'],
    ],

    // Fashion
    [
        'name' => 'Classic Denim Jacket', 'category' => 'Fashion', 'brand' => "Levi's",
        'price' => 7499, 'oldPrice' => 9999, 'rating' => 4.4, 'reviews' => 67, 'stock' => 30, 'badge' => 'Sale',
        'images' => [
            'https://images.pexels.com/photos/8386663/pexels-photo-8386663.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/8386651/pexels-photo-8386651.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/16891088/pexels-photo-16891088.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Blue', 'Black', 'Light Blue'], 'sizes' => ['S', 'M', 'L', 'XL'],
        'description' => 'A timeless denim jacket made from premium cotton, perfect for layering in any season.',
        'specs' => ['Material' => '100% Cotton', 'Fit' => 'Regular', 'Care' => 'Machine Wash', 'Origin' => 'Imported'],
    ],
    [
        'name' => 'Summer Floral Dress', 'category' => 'Fashion', 'brand' => 'Sana Safinaz',
        'price' => 5999, 'oldPrice' => 8500, 'rating' => 4.6, 'reviews' => 92, 'stock' => 18, 'badge' => 'New',
        'images' => [
            'https://images.pexels.com/photos/8386651/pexels-photo-8386651.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/16891088/pexels-photo-16891088.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/8386663/pexels-photo-8386663.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Pink', 'Yellow', 'Green'], 'sizes' => ['S', 'M', 'L'],
        'description' => 'A breezy floral dress perfect for summer days, made from soft breathable fabric.',
        'specs' => ['Material' => 'Viscose', 'Fit' => 'Relaxed', 'Length' => 'Knee', 'Care' => 'Hand Wash'],
    ],
    [
        'name' => 'Premium Leather Watch', 'category' => 'Fashion', 'brand' => 'Timex',
        'price' => 12500, 'oldPrice' => 15999, 'rating' => 4.5, 'reviews' => 54, 'stock' => 12, 'badge' => 'Best Seller',
        'images' => [
            'https://images.pexels.com/photos/28157826/pexels-photo-28157826.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/8968349/pexels-photo-8968349.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/22032446/pexels-photo-22032446.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Brown', 'Black'], 'sizes' => ['Standard'],
        'description' => 'An elegant leather-strap watch with a classic analog display and durable build.',
        'specs' => ['Movement' => 'Quartz', 'Strap' => 'Genuine Leather', 'Water Resistant' => '30m', 'Warranty' => '1 Year'],
    ],
    [
        'name' => 'Athletic Running Shoes', 'category' => 'Fashion', 'brand' => 'Nike',
        'price' => 13999, 'oldPrice' => 17999, 'rating' => 4.7, 'reviews' => 178, 'stock' => 25, 'badge' => 'Top Rated',
        'images' => [
            'https://images.pexels.com/photos/1456733/pexels-photo-1456733.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/12628400/pexels-photo-12628400.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/19271383/pexels-photo-19271383.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['White', 'Black', 'Red'], 'sizes' => ['39', '40', '41', '42', '43', '44'],
        'description' => 'Lightweight running shoes with cushioned soles and breathable mesh upper for maximum comfort.',
        'specs' => ['Material' => 'Mesh/Synthetic', 'Sole' => 'Rubber', 'Closure' => 'Lace-Up', 'Usage' => 'Running/Sports'],
    ],

    // Home & Living
    [
        'name' => 'Modern Living Room Set', 'category' => 'Home & Living', 'brand' => 'HomeCenter',
        'price' => 89000, 'oldPrice' => 120000, 'rating' => 4.3, 'reviews' => 41, 'stock' => 4, 'badge' => 'Sale',
        'images' => [
            'https://images.pexels.com/photos/7573934/pexels-photo-7573934.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/1648776/pexels-photo-1648776.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/12277129/pexels-photo-12277129.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Grey', 'Beige'], 'sizes' => ['3-Seater', '4-Seater'],
        'description' => 'A complete living room set with sofa, armchair, and coffee table in modern design.',
        'specs' => ['Material' => 'Fabric/Wood', 'Includes' => 'Sofa + Armchair + Table', 'Assembly' => 'Required', 'Warranty' => '2 Years'],
    ],
    [
        'name' => 'Wooden Sideboard Cabinet', 'category' => 'Home & Living', 'brand' => 'HomeCenter',
        'price' => 35000, 'oldPrice' => 45000, 'rating' => 4.2, 'reviews' => 28, 'stock' => 7, 'badge' => 'New',
        'images' => [
            'https://images.pexels.com/photos/12277129/pexels-photo-12277129.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/7573934/pexels-photo-7573934.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/1648776/pexels-photo-1648776.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Walnut', 'Oak'], 'sizes' => ['Standard'],
        'description' => 'A sleek wooden sideboard with ample storage space and a minimalist Scandinavian design.',
        'specs' => ['Material' => 'MDF/Wood', 'Storage' => '3 Drawers', 'Dimensions' => '160x45x75 cm', 'Assembly' => 'Required'],
    ],

    // Beauty
    [
        'name' => 'Luxury Makeup Set', 'category' => 'Beauty', 'brand' => 'MAC',
        'price' => 9500, 'oldPrice' => 13000, 'rating' => 4.6, 'reviews' => 134, 'stock' => 20, 'badge' => 'Best Seller',
        'images' => [
            'https://images.pexels.com/photos/12969358/pexels-photo-12969358.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/30408335/pexels-photo-30408335.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/30836145/pexels-photo-30836145.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Multi'], 'sizes' => ['Standard'],
        'description' => 'A complete luxury makeup set with lipsticks, eyeshadows, and brushes for a flawless look.',
        'specs' => ['Includes' => '12 Lipsticks + Palette + Brushes', 'Skin' => 'All Types', 'Shelf Life' => '24 months'],
    ],
    [
        'name' => 'Matte Lipstick Collection', 'category' => 'Beauty', 'brand' => 'Maybelline',
        'price' => 2200, 'oldPrice' => 3200, 'rating' => 4.4, 'reviews' => 256, 'stock' => 50, 'badge' => 'Sale',
        'images' => [
            'https://images.pexels.com/photos/30408335/pexels-photo-30408335.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/30836145/pexels-photo-30836145.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/12969358/pexels-photo-12969358.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Red', 'Nude', 'Pink', 'Maroon'], 'sizes' => ['Set of 4'],
        'description' => 'Long-lasting matte lipsticks with rich color payoff and a comfortable non-drying formula.',
        'specs' => ['Finish' => 'Matte', 'Long-Lasting' => '12 hours', 'Set Size' => '4 Shades', 'Formula' => 'Non-drying'],
    ],

    // Grocery
    [
        'name' => 'Organic Honey 500g', 'category' => 'Grocery', 'brand' => 'Marhaba',
        'price' => 850, 'oldPrice' => 1100, 'rating' => 4.5, 'reviews' => 89, 'stock' => 100, 'badge' => 'Sale',
        'images' => [
            'https://images.pexels.com/photos/4177709/pexels-photo-4177709.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/4177735/pexels-photo-4177735.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/26956442/pexels-photo-26956442.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Natural'], 'sizes' => ['250g', '500g', '1kg'],
        'description' => 'Pure organic honey sourced from local farms, rich in natural antioxidants.',
        'specs' => ['Weight' => '500g', 'Type' => 'Organic', 'Origin' => 'Pakistan', 'Storage' => 'Room Temperature'],
    ],
    [
        'name' => 'Premium Green Tea Pack', 'category' => 'Grocery', 'brand' => 'Lipton',
        'price' => 650, 'oldPrice' => 850, 'rating' => 4.3, 'reviews' => 145, 'stock' => 80, 'badge' => 'Popular',
        'images' => [
            'https://images.pexels.com/photos/4177735/pexels-photo-4177735.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/26956442/pexels-photo-26956442.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/4177709/pexels-photo-4177709.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Green'], 'sizes' => ['50 bags', '100 bags'],
        'description' => 'Aromatic green tea bags packed with antioxidants for a refreshing daily brew.',
        'specs' => ['Quantity' => '100 bags', 'Type' => 'Green Tea', 'Caffeine' => 'Low', 'Origin' => 'Blend'],
    ],

    // Kids & Toys
    [
        'name' => 'Educational Wooden Blocks', 'category' => 'Kids & Toys', 'brand' => 'FunSkool',
        'price' => 1800, 'oldPrice' => 2500, 'rating' => 4.5, 'reviews' => 67, 'stock' => 40, 'badge' => 'Sale',
        'images' => [
            'https://images.pexels.com/photos/311268/pexels-photo-311268.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/12955867/pexels-photo-12955867.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/14007163/pexels-photo-14007163.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Multi'], 'sizes' => ['50 pcs', '100 pcs'],
        'description' => 'Colorful wooden building blocks that help develop creativity and motor skills in children.',
        'specs' => ['Material' => 'Wood', 'Pieces' => '100', 'Age Group' => '3+ years', 'Safety' => 'Non-toxic'],
    ],
    [
        'name' => 'Construction Truck Set', 'category' => 'Kids & Toys', 'brand' => 'FunSkool',
        'price' => 3200, 'oldPrice' => 4500, 'rating' => 4.4, 'reviews' => 52, 'stock' => 28, 'badge' => 'New',
        'images' => [
            'https://images.pexels.com/photos/14007163/pexels-photo-14007163.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/311268/pexels-photo-311268.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
            'https://images.pexels.com/photos/12955867/pexels-photo-12955867.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        ],
        'colors' => ['Yellow', 'Red'], 'sizes' => ['Set of 3'],
        'description' => 'A set of die-cast construction trucks including excavator, dump truck, and loader.',
        'specs' => ['Material' => 'Die-cast Metal', 'Pieces' => '3', 'Age Group' => '4+ years', 'Scale' => '1:64'],
    ],
];
