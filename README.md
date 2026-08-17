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

## Email setup

Order confirmation emails (to the customer) and new-order notifications
(to the store's contact email) are sent via a small built-in SMTP
client (`app/Core/Mailer.php` — no Composer/PHPMailer dependency).

**`.env.example` ships with dummy placeholder values** so the site
runs out of the box without crashing — replace them with real
credentials before emails will actually send:

```
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=youremail@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_FROM_ADDRESS=youremail@gmail.com
MAIL_FROM_NAME="ShopMate Pakistan"
```

**To use Gmail:** you can't use your normal Gmail password over SMTP —
Google requires an **App Password** instead:
1. Turn on 2-Step Verification on the Google account:
   <https://myaccount.google.com/security>
2. Generate an App Password: <https://myaccount.google.com/apppasswords>
3. Put that 16-character password in `MAIL_PASSWORD` (keep the spaces
   out), and the Gmail address itself in `MAIL_USERNAME` and
   `MAIL_FROM_ADDRESS`.

**To test locally without sending real email**, use
[Mailtrap](https://mailtrap.io) (free tier, catches emails in a fake
inbox instead of delivering them) — sign up, grab its SMTP credentials,
and use those instead of Gmail's.

**Email sending failure never blocks checkout.** If the SMTP server is
unreachable or credentials are wrong, the order still saves
successfully — the failure is only logged to `storage/logs/php-error.log`.
This was deliberately verified: placing an order with the dummy
placeholder credentials above still returns a successful order every
time, it just can't actually send the email.

**A note on latency:** the built-in SMTP client is a direct, synchronous
connection — no background queue. On PHP-FPM (most real hosting), the
customer's response is sent before the email attempt starts
(`fastcgi_finish_request()`), so a slow/broken mail server adds no
wait time. On other setups (e.g. plain `mod_php`, or PHP's built-in
dev server used for local testing), that early-flush trick isn't
available, so a broken/unreachable SMTP server can add a few seconds
to checkout — bounded to a low, fixed per-connection timeout, never
unbounded. If this becomes noticeable at real scale, the fix is moving
email sending to a background job/cron queue rather than sending it
inline during the request.

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
- [x] **Real product image uploads** in the admin panel
      (`api/admin/product-images.php`). Files are validated by their
      actual content (`finfo` reads the real magic bytes, not the
      filename extension or claimed Content-Type — both spoofable),
      renamed to a random filename before being written to
      `assets/img/products/`, and capped at 5MB / 10 images per
      product. A new product's auto-generated placeholder image is
      automatically replaced the moment a real image is uploaded. Open
      the "Images" button on any product row in `admin/products.php`
      to manage a product's gallery.
- [x] **Product reviews table.** Real reviews (`product_reviews`,
      `app/Models/Review.php`, `api/reviews.php`), one per logged-in
      user per product. `products.rating`/`reviews_count` are kept as a
      small denormalized cache — recalculated from real reviews the
      moment a new one is submitted, but left at their seeded baseline
      for products nobody's reviewed yet (rather than zeroing every
      product's rating out). The 3 previously-hardcoded sample reviews
      are gone; `product.php`'s Reviews tab now shows real reviews, an
      inline star-rating write-a-review form for logged-in users who
      haven't already reviewed that product, and a login prompt for
      guests.
- [x] **Contact form persistence.** Submissions are saved for real now
      (`contact_messages` table, `app/Models/ContactMessage.php`,
      `api/contact.php`, CSRF-protected). New in this pass:
      `admin/messages.php` + `api/admin/messages.php` — an inbox view
      with status filters (New/Read/Replied), a "Reply by Email"
      `mailto:` shortcut, and a delete option. The admin sidebar shows
      an unread-count badge next to "Messages", computed live from the
      database on every page load.
- [x] **Order confirmation email.** Sent via a small built-in SMTP
      client (`app/Core/Mailer.php`) — both a confirmation to the
      customer (if they gave an email) and a new-order notification to
      the store's contact email. Ships with dummy placeholder SMTP
      credentials in `.env.example` — see the "Email setup" section
      above for how to swap in real ones (Gmail App Password or
      Mailtrap for local testing). Sending never blocks or fails an
      order — verified end-to-end with unreachable dummy credentials.
      **SMS is still not implemented** — would need a paid SMS gateway
      (e.g. Twilio, or a local Pakistani provider) and an account/API
      key, which wasn't set up as part of this pass.
- [x] **Password reset flow.** `forgot-password.php` (request a reset
      link by email) and `reset-password.php` (set a new password) —
      the `login.php` "Forgot password?" link is no longer dead. Tokens
      are one-time-use, expire after 1 hour, and only their SHA-256
      hash is stored (never the raw token — same principle as password
      storage). Requesting a reset for an email that doesn't have an
      account shows the exact same "check your email" message as one
      that does, so the form can't be used to discover which emails are
      registered. Uses the same `app/Core/Mailer.php` built for order
      confirmations.
- [ ] **Pagination for admin tables** (products/orders/customers) —
      fine for a small catalog, will need it once the catalog grows.
- [x] **Payment method: Cash on Delivery only, for now — by design.**
      Checkout still shows JazzCash/EasyPaisa/Card/Bank Transfer as
      options (so the UI and `orders.payment_method` schema are ready
      for them later), but none of those are wired to a real payment
      gateway — no money actually moves for any option except COD.
      This is a deliberate scope decision for the current stage of the
      business, not an oversight: integrating a real gateway (JazzCash/
      EasyPaisa's merchant APIs, or a card processor) means signing up
      for a merchant account with that provider, getting API
      credentials, and handling their specific callback/webhook flow —
      real integration work for a later phase once it's actually
      needed. Selecting a non-COD payment method at checkout today
      creates a normal order with that method recorded, but the
      customer isn't actually charged.
- [ ] Delete or repurpose `assets/js/data.js` — unused now that every
      page reads from the database.
