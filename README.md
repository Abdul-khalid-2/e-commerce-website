# ShopMate Pakistan — E-commerce Website

Frontend: HTML5, CSS3, vanilla JavaScript, Bootstrap 5 (CDN), AOS.js, Chart.js.
No build step — open any page directly or serve with any static/PHP server.

## Project Structure

```
e-commerce-website/
├── index.html            Home page
├── shop.html              Product listing / category / filters
├── product.html            Single product page
├── cart.html                Shopping cart
├── checkout.html            Checkout flow
├── login.html                Login / signup
├── orders.html                Order tracking / history
├── wishlist.html               Wishlist
│
├── admin/                       Admin dashboard (isolated area)
│   └── index.html                 Dashboard, Products, Orders, Customers,
│                                   Categories, Settings (single-page, JS-driven)
│
├── assets/
│   ├── css/
│   │   └── style.css              All site styling (light + dark theme)
│   ├── js/
│   │   ├── data.js                 Static product/category/testimonial data
│   │   └── common.js                Shared logic: cart, wishlist, navbar,
│   │                                 footer, search, theme toggle, toasts
│   └── img/                         (empty — for locally-hosted images later;
│                                     currently all images are remote Pexels URLs)
│
├── includes/                        Empty — reserved for PHP partials in the
│                                     next phase (header.php, footer.php,
│                                     navbar.php, db.php, auth.php, etc.)
│
├── config/                          Empty — reserved for PHP config in the
│                                     next phase (database.php, constants.php)
│
└── README.md
```

## Path conventions

- Root-level pages reference assets as `assets/css/style.css`,
  `assets/js/data.js`, `assets/js/common.js`.
- `admin/index.html` is one level deeper, so it references them as
  `../assets/css/style.css`, `../assets/js/data.js`, `../assets/js/common.js`,
  and links back to the storefront as `../index.html`.
- `assets/js/common.js` builds the shared navbar/footer for storefront pages
  and links to the admin panel as `admin/index.html`.

## Current state (frontend-only)

- Cart and wishlist persist via `localStorage` (see `assets/js/common.js`).
- Product/category/testimonial data is static, defined in `assets/js/data.js`.
- Admin dashboard (products, categories, orders, customers) edits an
  in-memory copy of that data — changes reset on page refresh since there's
  no backend yet.

## PHP + MySQL backend (in progress)

The backend foundation has been set up. Nothing in the frontend pages uses
it yet — that's the next step.

```
app/Core/
├── Autoloader.php     PSR-4-style autoloader (no Composer required)
├── Database.php        Secure PDO singleton (real prepared statements,
│                        errors never leaked to the browser in production)
└── Migrator.php         Applies database/migrations/*.sql files in order,
                          tracks what's been run in a `migrations` table

config/
├── config.php          Loads .env, defines constants (DB_*, APP_*, paths)
└── bootstrap.php         The single file every PHP entry point should
                           require — wires up config + autoloader + session

database/
├── migrate.php          CLI tool: creates the DB if missing, runs pending
│                          migrations
└── migrations/           001_create_users_table.sql ... 014_create_settings_table.sql
                           14 tables: users, categories, products,
                           product_images, product_colors, product_sizes,
                           product_specs, hero_slides, testimonials,
                           trust_badges, addresses, orders, order_items,
                           wishlists, carts, cart_items, settings

includes/                Empty — will hold header.php, footer.php,
                          navbar.php once pages are converted to .php

storage/logs/            PHP error log destination (git-ignored)
```

### Local setup

Requires PHP 8.1+ with the `pdo_mysql` extension, and MySQL or MariaDB.
On Windows, XAMPP or Laragon bundle both — just make sure Apache and
MySQL are running.

```bash
cp .env.example .env
# edit .env: set DB_USER / DB_PASS to match your local MySQL/MariaDB

php database/migrate.php          # creates the `shopmate` database and
                                   # all 14 tables
php database/migrate.php status   # see which migrations have run
```

Re-running `php database/migrate.php` is always safe — it only applies
files it hasn't seen before. To change the schema later (add a column,
create a new table, etc.), add a new file to `database/migrations/` with
a higher numeric prefix — never edit an already-applied migration file.
For example:

```
database/migrations/015_add_sku_to_products.sql
```
```sql
ALTER TABLE products ADD COLUMN sku VARCHAR(50) NULL AFTER slug;
```

Then just run `php database/migrate.php` again.

## Next phase: PHP conversion (planned)

Goal: turn this into a dynamic PHP + MySQL app without changing the visual
design. Remaining steps:

1. `App\Models` classes (Product, Category, Order, User, ...) for CRUD
   against the tables above
2. `includes/header.php`, `includes/footer.php`, `includes/navbar.php` —
   shared markup, replacing the `common.js`-generated navbar/footer
3. Seed script to load the current `data.js` content into the database
4. Convert `.html` pages to `.php`, pulling data from the DB instead of
   the static JS arrays
5. Real cart/session handling (PHP sessions + `carts`/`cart_items` tables
   instead of `localStorage`)
6. Admin CRUD (`admin/products.php`, `admin/categories.php`,
   `admin/orders.php`, etc.) with real persistence + authentication
7. Checkout → orders table, order status updates reflected on the
   customer-facing Orders/Track Order page
