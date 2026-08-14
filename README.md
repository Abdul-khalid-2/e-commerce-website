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
├── migrations/           001_create_users_table.sql ... 014_create_settings_table.sql
│                          14 tables: users, categories, products,
│                          product_images, product_colors, product_sizes,
│                          product_specs, hero_slides, testimonials,
│                          trust_badges, addresses, orders, order_items,
│                          wishlists, carts, cart_items, settings
├── seed.php               CLI tool: loads the catalog + hero slides +
│                            testimonials + trust badges + settings +
│                            a default admin user into the database
└── seeds/
    └── products.php        The 16-product catalog as a plain PHP array
                             (same data that used to live in data.js)

app/Models/
├── Category.php         active(), findBySlug(), productCounts()
├── Product.php            find()/findBySlug() (auto-attach images/
│                           colors/sizes/specs), byCategory(), search()
│                           (FULLTEXT with a LIKE fallback), createFull(),
│                           replaceRelations()
├── Setting.php             get()/set()/all() for the settings table
└── User.php                register() (hashes the password),
                             findByEmail(), verifyPassword()

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
                                   # all 17 tables
php database/seed.php             # loads products, categories, hero
                                   # slides, testimonials, trust badges,
                                   # settings, and a default admin user
php database/migrate.php status   # see which migrations have run
```

The seed script prints the default admin login when it creates it
(`admin@shopmate.pk` / a generated password) — change that password once
the login page exists. Re-running `php database/seed.php` is a safe no-op
if data already exists; pass `--fresh` to wipe every seedable table and
reseed from scratch.

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

## Next phase: PHP conversion (in progress)

Goal: turn this into a dynamic PHP + MySQL app without changing the visual
design.

**Done:**
- `index.php` — homepage converted, pulls hero slides, trust badges,
  categories, featured products, best sellers, and testimonials from the
  database via the Models
- `shop.php` — listing page converted. Filtering/sorting/pagination stay
  client-side (unchanged design), but the product/category data feeding
  that JS now comes from the database on every request instead of the
  static `data.js` file
- `product.php` — product detail page converted. Gallery, price, specs,
  stock, and the "You May Also Like" related-products grid are now
  server-rendered from the database (better for SEO than the old
  fully-client-rendered version). Falls back to the lowest-id active
  product if `?id=` is missing or invalid, matching the original
  behaviour. Color/size selection, quantity, add-to-cart, buy-now, and
  wishlist toggle stay client-side (unchanged JS) since those are pure
  UI interactions on an already-rendered page.
- `includes/header.php`, `includes/navbar.php`, `includes/footer.php` —
  shared markup, replacing the old `common.js`-generated navbar/footer.
  The navbar's category links are now live from the database.
- `includes/product-card.php` — shared `render_product_card()`, the PHP
  equivalent of the old JS `renderProductCard()`
- Every `index.html`, `shop.html`, and `product.html` link across the
  site now points to `index.php` / `shop.php` / `product.php`
- `about.php`, `contact.php` — new pages (didn't exist as `.html`
  before; the nav/footer links were dead until now). About page shows
  live stats pulled from the database (category count, trust badges);
  Contact page pulls the store's phone/email from the `settings` table
  and has a contact form (client-side only for now — see note below)
- **Real cart + sessions.** `cart.php`, `checkout.php`, `login.php`,
  `orders.php`, `wishlist.php` (shell only — see note below) are fully
  converted. The cart no longer lives in `localStorage` — it's backed
  by the `carts`/`cart_items` tables, identified by the PHP session, so
  it works for guests immediately and survives login (a guest's cart
  merges into their account cart on login/signup, see
  `Cart::mergeSessionIntoUser()`). Two new JSON endpoints power this:
  `api/cart.php` (add/update/remove/clear/count) and
  `api/place-order.php` (creates a real order from the current cart,
  server-computed totals — never trusts anything from the client).
- `login.php` — real authentication against the `users` table
  (`password_hash`/`password_verify`, never plain text), with a proper
  signup flow and session regeneration on login (mitigates session
  fixation). `logout.php` added to go with it. The navbar now shows
  "Hi, {name}" with an account dropdown when logged in, or a Login
  button otherwise, and the cart badge count is computed server-side on
  every page load (`includes/navbar.php`) instead of via a client-side
  fetch.
- `orders.php` — real order lookup by order number (`?number=...`),
  with a real status timeline. Works for guests too: the session
  remembers order numbers placed during that visit
  (`$_SESSION['recent_orders']`), and if logged in, the account's full
  order history is also shown.

**Wishlist stays client-side (localStorage) for now, by design.** The
`wishlists` table requires a logged-in `user_id`, but the original site
let anyone use the wishlist without logging in. Converting it properly
would mean either requiring login for wishlist (a UX change) or adding
guest/session support to the `wishlists` table (a schema change) —
deferred rather than done as a rushed side effect of this pass.
`wishlist.php`'s page shell (header/navbar/footer, product data source)
is converted; the wishlist mechanism itself is unchanged.

- **Admin panel — fully converted.** `admin/index.html` is gone;
  everything is real PHP now, gated by a separate admin session
  (`app/Core/Auth::requireAdmin()`, checked against `users.role =
  'admin'` — the seeded account from `database/seed.php` works as-is):
  - `admin/login.php` / `admin/logout.php` — separate from the
    customer-facing login, own session key (`$_SESSION['admin_id']`)
  - `admin/index.php` — dashboard with real numbers: total sales,
    orders, customers, and products from the database; a 7-day sales
    chart (Chart.js) and revenue-by-category breakdown computed from
    real `order_items`; recent orders table
  - `admin/products.php` + `api/admin/products.php` — full CRUD. New
    products get a placeholder image and default color/size until
    there's a real image-upload flow. **Editing an existing product
    only touches its core fields** — it no longer wipes out that
    product's real images/colors/sizes the way the original in-memory
    JS version did (that was a real bug in the old version; fixed here)
  - `admin/categories.php` + `api/admin/categories.php` — full CRUD,
    same Active/Inactive toggle as before, now persisted — flip a
    category off here and it disappears from the storefront nav/shop
    immediately (verified end-to-end)
  - `admin/orders.php` + `api/admin/orders.php` — lists real orders,
    status dropdown updates persist to the database and show up
    immediately on the customer's `orders.php` tracking page (also
    verified end-to-end)
  - `admin/customers.php` — real customer list with order count and
    total spent per customer, computed via SQL
  - `admin/settings.php` — edits the same `settings` table the
    storefront reads from (store name, contact info, free shipping
    threshold)

  `assets/js/data.js` (the original static catalog file) is no longer
  loaded anywhere on the site — every page now reads from the database.
  It's still in the repo for reference but can be deleted whenever.

**How the cart/wishlist bridge works right now:** cart and wishlist still
live in `localStorage` (real server-side sessions are a later step).
Converted pages still emit a `PRODUCTS` JS array — built from the
database on every request — so `assets/js/common.js`'s existing cart/
wishlist/search/quick-view functions keep working unchanged. Wishlist
"heart" icons can't be rendered correctly server-side (the state is in
the browser), so cards render as "not wishlisted" and
`assets/js/common.js`'s `syncWishlistUI()` corrects them right after the
page loads (and again after every filter/sort re-render on `shop.php`);
`product.php`'s own wishlist button is corrected the same way via
`updateWishBtn()`.

**Product reviews note:** the Reviews tab on `product.php` still shows 3
static sample reviews (same as the original design) — there's no
`product_reviews` table yet. A real reviews system is a good candidate
for a future migration + model if wanted.

**Contact form note:** `contact.php`'s form currently only shows a
success toast client-side — there's no `contact_messages` table or POST
handler yet. Worth adding as a small future migration + model if you
want submissions actually saved/emailed.

**Remaining steps:**
1. Move the wishlist to the database for logged-in users (see note
   above) — likely as an optional upgrade path: keep localStorage for
   guests, sync to the `wishlists` table once logged in
2. Admin CRUD (`admin/products.php`, `admin/categories.php`,
   `admin/orders.php`, etc.) with real persistence + authentication —
   the `users.role = 'admin'` check is already there (see the seeded
   admin account), just needs the admin pages converted to use it
3. Order status updates from the admin panel, reflected live on
   `orders.php`
4. `api/cart.php` and `api/place-order.php` currently trust the PHP
   session alone; consider adding CSRF tokens to the checkout form once
   the site has a real deployment target

Requires the `mbstring` PHP extension (bundled with XAMPP/Laragon by
default; on bare Linux install `php-mbstring`).
