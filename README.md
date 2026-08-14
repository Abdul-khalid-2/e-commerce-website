# ShopMate Pakistan — E-commerce Website

A full-featured e-commerce site for the Pakistani market: PHP 8 (plain
OOP, no framework) + MySQL/MariaDB on the backend, Bootstrap 5 + vanilla
JS on the frontend. Real storefront, cart, checkout, customer accounts,
and an admin panel — all backed by a real database.

## Project Structure

```
e-commerce-website/
├── index.php, shop.php, product.php, about.php, contact.php   Storefront
├── cart.php, checkout.php, orders.php                          Cart / checkout / order tracking
├── login.php, logout.php                                       Customer auth
├── wishlist.php                                                 Wishlist (localStorage, see Roadmap)
│
├── admin/                        Admin panel (own login/session)
│   ├── login.php, logout.php
│   ├── index.php                   Dashboard (sales chart, stats, recent orders)
│   ├── products.php                Product CRUD
│   ├── categories.php              Category CRUD
│   ├── orders.php                  Order list + status updates
│   ├── customers.php               Customer list
│   └── settings.php                Store settings
│
├── api/                          JSON endpoints for AJAX actions
│   ├── cart.php                    add/update/remove/clear/count
│   ├── place-order.php             creates a real order from the cart
│   └── admin/
│       ├── products.php, categories.php, orders.php
│
├── app/
│   ├── Core/
│   │   ├── Autoloader.php            PSR-4-style autoloader (no Composer)
│   │   ├── Database.php               Secure PDO singleton
│   │   ├── Migrator.php                Applies database/migrations/*.sql in order
│   │   ├── Model.php                   Base class: find/all/where/create/update/delete
│   │   ├── Auth.php                     Admin session guard
│   │   └── Str.php                      slug() helper
│   └── Models/
│       ├── Product.php, Category.php, User.php, Cart.php, Order.php
│       └── Setting.php, HeroSlide.php, Testimonial.php, TrustBadge.php
│
├── includes/                      Shared partials
│   ├── header.php, navbar.php, footer.php     Storefront shell
│   ├── admin-header.php, admin-footer.php     Admin shell
│   └── product-card.php                        render_product_card()
│
├── config/
│   ├── config.php                  Loads .env, defines constants
│   └── bootstrap.php                 Required first by every PHP entry point
│
├── database/
│   ├── migrate.php                 CLI: creates the DB + runs pending migrations
│   ├── migrations/                  001...014, one table (or closely related
│   │                                 tables) per file
│   ├── seed.php                      CLI: loads demo data + a default admin user
│   └── seeds/products.php             The 16-product demo catalog
│
├── assets/
│   ├── css/style.css                All styling (light + dark theme)
│   ├── js/common.js                  Cart/wishlist/search/theme/toast JS
│   ├── js/data.js                     Unused now — kept for reference, safe to delete
│   └── img/                           Empty — for locally-hosted images later
│
└── storage/logs/                  PHP error log destination (git-ignored)
```

## Local setup

Requires PHP 8.1+ with `pdo_mysql` and `mbstring`, and MySQL or MariaDB.
XAMPP or Laragon bundle everything needed on Windows.

```bash
cp .env.example .env
# edit .env: set DB_USER / DB_PASS to match your local MySQL/MariaDB

php database/migrate.php          # creates the database + all tables
php database/seed.php             # loads demo products/categories/settings
                                   # + prints a default admin login
php database/migrate.php status   # see which migrations have run
```

Re-running `migrate.php` is always safe — it only applies files it
hasn't seen before. To change the schema later, add a new file to
`database/migrations/` with a higher numeric prefix (e.g.
`015_add_sku_to_products.sql`) — never edit an already-applied one.

Re-running `seed.php` is a safe no-op if data already exists; pass
`--fresh` to wipe every seedable table and reseed from scratch.

---

## Roadmap

### Phase 1 — Static frontend
- [x] HTML/CSS/JS storefront (Bootstrap 5, AOS animations, dark mode)
- [x] Landing page, shop/filter page, product page, cart, checkout,
      login, orders, wishlist, admin dashboard (all client-side/mocked)
- [x] Restructured into `assets/`, `admin/`, `includes/`, `config/` for
      the PHP conversion to come

### Phase 2 — PHP + MySQL foundation
- [x] Secure PDO database layer (`app/Core/Database.php`) — real
      prepared statements, no leaked errors
- [x] Custom migration runner (`app/Core/Migrator.php` +
      `database/migrate.php`) — plain `.sql` files, tracked in a
      `migrations` table, safe to re-run
- [x] Full schema: users, categories, products (+ images/colors/sizes/
      specs), orders/order_items, carts/cart_items, wishlists,
      addresses, hero_slides, testimonials, trust_badges, settings
- [x] Model layer (`app/Models/*`) with relation loading, search, CRUD
- [x] Seed script + demo catalog (`database/seed.php`)

### Phase 3 — Storefront pages made dynamic
- [x] `index.php` — hero, categories, featured/best-seller products,
      testimonials, all from the database
- [x] `shop.php` — filtering/sorting stays client-side, data source is
      now the database
- [x] `product.php` — server-rendered gallery/specs/related products
- [x] `about.php`, `contact.php` — new pages (never existed before)
- [x] Shared `includes/header.php` + `navbar.php` + `footer.php` +
      `product-card.php`

### Phase 4 — Cart, checkout, accounts
- [x] Real server-side cart (`carts`/`cart_items`, session-based, works
      for guests, merges into account on login)
- [x] `api/cart.php`, `api/place-order.php` — AJAX cart actions + real
      order creation with server-computed totals
- [x] `login.php`/`logout.php` — real auth (`password_hash`/
      `password_verify`), signup, session regeneration on login
- [x] `orders.php` — real order lookup + status timeline, works for
      guests via session-remembered order numbers

### Phase 5 — Admin panel
- [x] Admin authentication (`app/Core/Auth.php`, separate session from
      customers, checked against `users.role = 'admin'`)
- [x] Dashboard with real stats, 7-day sales chart, revenue by category
- [x] Product CRUD (`admin/products.php` + `api/admin/products.php`)
- [x] Category CRUD with Active/Inactive toggle that actually affects
      the live storefront
- [x] Order list + status updates that reflect live on the customer's
      tracking page
- [x] Customer list (orders + total spent per customer)
- [x] Settings page (store name, contact info, free shipping threshold)

### Phase 6 — In progress / not done yet
- [x] **Wishlist → database for logged-in users.** Guests still use
      `localStorage` (unchanged). Logged-in users get a real
      database-backed wishlist (`wishlists` table, `app/Models/
      Wishlist.php`, `api/wishlist.php`). A guest's localStorage
      wishlist automatically merges into their account the moment
      they're logged in (`loadWishlistFromServer()` in
      `assets/js/common.js`), the same pattern already used for cart
      merging on login.
- [x] **CSRF protection** on every state-changing form and AJAX
      endpoint. `app/Core/Csrf.php` issues one token per session
      (`window.CSRF_TOKEN`, emitted via `includes/header.php` and
      `includes/admin-header.php`). Enforced on: customer login/signup,
      admin login, admin settings, `api/cart.php` (add/update/remove/
      clear), `api/place-order.php`, `api/wishlist.php` (add/remove/
      sync), and all three `api/admin/*.php` endpoints. Regenerated on
      every successful login (alongside `session_regenerate_id()`) so a
      token issued before authenticating can't be replayed after.
      Read-only actions (cart count/items, wishlist list) don't require
      a token since they don't change anything.
- [ ] **Real product image uploads** in the admin panel. New/edited
      products currently only support a single placeholder image or
      manual DB edits — no upload UI yet.
- [ ] **Product reviews table.** `product.php`'s Reviews tab still shows
      3 static sample reviews; no `product_reviews` table or submission
      form exists yet.
- [ ] **Contact form persistence.** `contact.php`'s form only shows a
      client-side success toast — no `contact_messages` table or email/
      DB save yet.
- [ ] **Order confirmation email/SMS.** Orders are created successfully
      but nothing is sent to the customer beyond the on-page success
      screen.
- [ ] **Password reset flow** for customer accounts ("Forgot password?"
      is currently a dead link).
- [ ] **Pagination for admin tables** (products/orders/customers) —
      fine for a small catalog, will need it once the catalog grows.
- [ ] **Real payment gateway integration** (JazzCash/EasyPaisa/card) —
      currently payment method is just recorded, not actually processed.
- [ ] Delete or repurpose `assets/js/data.js` — unused now that every
      page reads from the database.
