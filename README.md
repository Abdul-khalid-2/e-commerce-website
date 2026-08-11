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

## Next phase: PHP conversion (planned)

Goal: turn this into a dynamic PHP + MySQL app without changing the visual
design. Rough plan for the next chat:

1. `config/database.php` — DB connection (PDO/MySQLi)
2. `includes/header.php`, `includes/footer.php`, `includes/navbar.php` —
   shared markup, replacing the `common.js`-generated navbar/footer
3. Convert `data.js` arrays (`PRODUCTS`, `CATEGORIES`, `TESTIMONIALS`, etc.)
   into MySQL tables + a schema/seed SQL file
4. Convert `.html` pages to `.php`, pulling data from the DB instead of
   the static JS arrays
5. Real cart/session handling (PHP sessions instead of `localStorage`)
6. Admin CRUD (`admin/products.php`, `admin/categories.php`,
   `admin/orders.php`, etc.) with real persistence + basic auth
7. Checkout → orders table, order status updates reflected on the
   customer-facing Orders/Track Order page
