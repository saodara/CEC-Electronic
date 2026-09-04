# CEC Electronic — How It Works

A guided tour of this codebase: what it is, how it's built, and how data actually flows through every part of it — storefront, checkout, customer accounts, and the admin back office. For database schema details see `README.md`; for hosting/infra see `DEPLOYMENT.md`. Everything below is traced from the actual controllers/models/services, not just route names — including a couple of things that look like features but aren't fully wired up (flagged explicitly).

## What this is

A Laravel e-commerce app for a computer/electronics retailer (CEC Electronic), covering:
- A public storefront (browse, search, cart, checkout, guest or logged-in)
- Customer accounts (register/login, order history)
- An internal admin panel (products, orders, customers, suppliers, delivery)

## Tech stack

- **Backend:** Laravel 13, PHP 8.3
- **Database:** Postgres (Neon, serverless) via `pdo_pgsql`
- **Frontend:** Blade templates + Vite (no SPA framework — server-rendered pages)
- **Auth:** Laravel's built-in session auth (`Auth::attempt`/`Auth::login`), one `users` table shared by customers and admins, distinguished by an `is_admin` flag
- **Hosting:** Docker on Render, see `DEPLOYMENT.md`
- **Tests:** thin — `tests/Feature/CartQuantityTest.php` is the only real coverage beyond the framework's default example test. Most business logic here is unverified by automated tests.

## Directory map

```
app/
  Http/Controllers/
    Storefront/   — public browsing, cart, checkout (guest-accessible)
    Customer/     — customer auth + account area
    Admin/        — admin back office (all behind EnsureAdminSession)
  Http/Middleware/
    EnsureAdminSession.php  — the one custom middleware; gates /admin/*
  Models/          — one Eloquent model per table (see README.md for columns)
  Services/
    CartService.php      — cart read/write, guest-cart merge on login
    CheckoutService.php  — turns a cart into an Order (DB transaction)
    BakongService.php    — Bakong/KHQR payment relay client (currently unused, see below)
    KhqrGenerator.php    — KHQR string generation, done locally (no API call)
database/
  migrations/  — full schema history, this is the source of truth for structure
  seeders/     — DatabaseSeeder creates the admin user + sample catalog data
resources/views/
  shop/        — storefront pages (home, category, product, cart)
  checkout/    — checkout form + order success/payment-status pages
  account/     — customer auth + account dashboard
  admin/       — admin panel pages, one folder per resource
routes/web.php — every route in the app, single file
config/brands.php — static brand list (see Catalog section — not a DB table)
docker/        — nginx.conf + entrypoint.sh, see DEPLOYMENT.md
```

## Request flow, end to end

### 1. Catalog & storefront browsing (guest or logged-in, no auth required)

`HomeController` and `CatalogController` (`routes/web.php:19-24`) serve the home page, search, category pages, brand pages, and product detail pages.

**Caching layer — worth understanding before touching these controllers.** Every catalog read (`home`, `category`, `product`, `search`, `brands`, `brand`) is wrapped in a 300-second file cache (`HomeController.php`, `CatalogController::cacheRemember()` at `app/Http/Controllers/Storefront/CatalogController.php:29-51`), because the DB is geographically remote from the app (Neon vs. Render). This isn't a plain `Cache::remember()` — it manually validates the *shape* of whatever comes back from the cache (`is_array($v) && ... instanceof Collection`, etc.) before trusting it, and falls back to recomputing on any `Throwable` from the cache store. The reason, per the code comments: the file cache driver (`CACHE_STORE=file`) has no atomic lock between concurrent PHP-FPM workers, so two requests racing to populate the same cold key can corrupt the write — and `unserialize()` doesn't always throw on that corruption, it can silently return the wrong PHP shape. **If you add a new cached catalog read, follow this same defensive pattern** rather than a bare `Cache::remember()`.

**"Brands" are not a database table.** `config/brands.php` is a static, hand-maintained list of `{name, slug, logo}`. `CatalogController::brandCollection()` (`app/Http/Controllers/Storefront/CatalogController.php:130-150`) fetches all active products once and, in memory, string-matches each brand's `name` against every product's `name`/`description` (`Str::contains`, case-insensitive) to compute a per-brand product count. `CatalogController::brand()` filters products the same way. So a product only "belongs" to a brand if the brand name literally appears in its name or description text — there's no `brand_id` column or relation. To add a brand, edit `config/brands.php`; to make a product show up under it, its name/description needs to contain that brand's name.

**Category fallback:** `CatalogController::category()` looks up a `categories` row by slug; if none exists, it falls back to filtering `products.category` (the legacy free-text column mentioned in `README.md`) by the slug directly. So category pages work even for products that were never assigned a real `category_id`.

### 2. Cart

`CartController` (`routes/web.php:26-29`) → `CartService`. There's no separate "cart" entity — each `cart_items` row belongs either to a `user_id` (logged in) or a `session_id` (guest), decided by `CartService::ownerColumn()` (`app/Services/CartService.php:93-101`). Adding a product does an upsert-by-owner-and-product (`firstOrNew` + increment quantity), so re-adding the same item just bumps the quantity rather than creating duplicate rows. `unit_price` is captured from the product **at the time it's added/re-added** to the cart — if you change a product's price later, items already sitting in someone's cart keep the old price until they touch that cart row again.

**Guest → logged-in cart merge:** when a guest with cart items registers or logs in, `CartService::mergeGuestCartIntoUser()` (`app/Services/CartService.php:17-35`) reassigns their session's cart rows to the new `user_id`, combining quantities if they already had the same product in their account cart. This runs automatically inside both `AuthController::authenticate()` and `AuthController::store()` (`app/Http/Controllers/Customer/AuthController.php:44-46, 67-69`) — nothing is lost by registering mid-shopping.

**Ownership is enforced, not just assumed:** `CartService::guardOwner()` (`app/Services/CartService.php:103-109`) aborts with 403 if you try to update/remove a cart item that doesn't belong to your current session/user — so a guest can't manipulate another guest's cart by guessing a `cart_items` ID.

### 3. Checkout

`CheckoutController::create()` shows the form — **checkout requires an account**; if you're not logged in it redirects straight to `/login` (`app/Http/Controllers/Storefront/CheckoutController.php:25-29`), so there's no true guest checkout despite `orders.user_id` being nullable in the schema. `CheckoutController::store()` validates the shipping/payment form, then hands off to `CheckoutService::createOrder()`.

`CheckoutService::createOrder()` (`app/Services/CheckoutService.php:16-68`) does the real work, inside a single DB transaction:
1. Reads the current cart (aborts `422` if empty).
2. Creates the `Order` row: generates a unique `order_number` (`EH-YYYYMMDD-####`, retried against a uniqueness check — not globally unique across time by construction, just checked), snapshots the shipping address as JSON, starts `status: pending` / `payment_status: unpaid`. **`shipping_total` is hardcoded to `0`** — despite `delivery_zone_id`/`delivery_fee` existing on `delivery_zones`, checkout doesn't currently apply a zone-based shipping fee; that's set later by an admin, if at all (see order fulfillment below).
3. Creates one `order_items` row per cart item, **snapshotting** `product_name` and `sku` at time of purchase — so later edits/deletes of the product don't retroactively change historical orders. If a product is later deleted, its historical order line still shows correctly (falls back to `'Deleted product'` as the name, `OrderItem::product()` relation just returns null).
4. Clears the cart.

**Payment methods:** the checkout form currently offers **Cash on delivery** and **Bank transfer** only. Bakong/KHQR support exists in the codebase (`BakongService`, `KhqrGenerator`, the `bakong_*` columns on `orders`, the `/webhooks/bakong` route) but the option was **removed from the checkout dropdown** because the Bakong relay wasn't working in this environment — see `resources/views/checkout/create.blade.php`. The backend code path (`CheckoutController::store()` checking `payment_method === 'bakong'`) is dead code today; re-add the `<option value="bakong">` to bring it back if the relay gets fixed. Worth knowing if you do: `KhqrGenerator` builds the actual KHQR payload **locally**, following the NBC KHQR spec directly — no API call needed for QR generation itself. Only two things need the external relay (`BAKONG_RELAY_URL`): converting a QR string to a PNG image, and the optional hosted "web checkout" flow (`createWebCheckout`/`getCheckoutDetails`) for a nicer mobile experience. Both fail soft (return `null`) rather than throwing if the relay is unreachable.

### 4. Customer accounts

`Customer\AuthController` (`app/Http/Controllers/Customer/AuthController.php`) handles register/login/logout at `/register`, `/login`, `/logout`. Registration also **claims any guest orders** placed with the same email before the account existed (`Order::whereNull('user_id')->where('customer_email', ...)`, lines 61-64) — so a guest-flavored order (placed before this account existed, even though checkout itself requires login — this mainly matters if someone changes their email or an order was created some other way) placed with the same email gets linked to the new account automatically on registration.

`Customer\AccountController` (`account.*` prefix, `routes/web.php:45-49`) shows the account dashboard and order history/detail — scoped implicitly to the logged-in user (check the controller for the exact `where('user_id', ...)` scoping if modifying).

### 5. Admin panel

**Auth is separate from the `is_admin` flag alone.** `EnsureAdminSession` middleware (`app/Http/Middleware/EnsureAdminSession.php`) requires *three* things together: logged in, `is_admin = true` on the user, **and** an `admin_authenticated` session flag set specifically by `Admin\AuthController::store()` (`app/Http/Controllers/Admin/AuthController.php:22-25`) on successful `/admin/login`. So a customer session that happens to have `is_admin` true still can't reach `/admin/*` without having gone through the actual admin login form — the flag is what the customer login form never sets.

Everything under `/admin/*` (except the login routes themselves) sits behind this middleware (`routes/web.php:55-65`):

- **Dashboard** (`AdminProductController::dashboard()`, despite the name — it's the general admin home) — aggregate stats: product/category/order counts, **customer count via `DISTINCT customer_phone` on orders** (not a `users` count — this counts unique phone numbers across all orders, including guest-style repeat customers by phone, not registered accounts), total revenue (`SUM(grand_total)`), unread payment-notification count, low-stock count (`stock_quantity <= 5`), plus recent products/orders/payment-notifications lists.
- **Products** (`AdminProductController`) — standard CRUD. Slug is auto-generated from the name (`uniqueSlug()`, appends `-2`, `-3`, ... on collision) and **only regenerated if the name actually changed** on update, so editing other fields doesn't silently change a product's URL. Image upload: stored via `Storage::disk('public')` under `products/`, and `getImageUrlAttribute()` on the `Product` model (`app/Models/Product.php`) resolves three different cases depending on what's stored — a full external URL (`http(s)://` or leading `/`, used as-is), a bundled static asset path (starts with `images/`, resolved via `asset()`), or an uploaded file (resolved via `Storage::disk('public')->url()`). On replacing an image, the **old file is deleted from disk** — but only if it wasn't one of the first two cases (external/static), so seeded/static product images are never accidentally deleted.
- **Categories** (`AdminCategoryController`) — same slug/image pattern as products, plus `parent_id` for subcategories (self-referencing, see `Category::parent()`/`children()`).
- **Orders** (`AdminOrderController`) — `index` (paginated, with the same unread-payment-notification counter as the dashboard), `show` (**marks a payment notification as seen the moment the order is opened**, `AdminOrderController::show()` lines 32-35 — there's no separate "mark as read" action, viewing the order *is* the read receipt), `update` (changes `status`/`payment_status`; **transitioning to `payment_status: paid` stamps `payment_confirmed_at` automatically** — this is the auditable record of "admin confirmed this payment"). The same update form can also set `delivery_zone_id`/`delivery_provider_id`/`tracking_number`/`shipped_at`/`delivered_at` — if any delivery field is set, it `updateOrCreate`s a matching `shipments` row (keyed by `tracking_number`), with the shipment's own `status` derived from whether `delivered_at`/`shipped_at` are present.
- **Customers** (`AdminCustomerController`) — **grouped by `customer_phone`, not by user account or email.** `index()` runs a `GROUP BY customer_phone` aggregate query across all orders (order count, total spent, last order date) — so this view reflects buying phone numbers, not registered accounts; a customer who orders under two different phone numbers shows up as two separate "customers" here, and a registered account with zero orders doesn't show up at all.
- **Suppliers** (`AdminSupplierController`) — plain CRUD, contact/payment-terms info per supplier.
- **Delivery zones & providers** (`AdminDeliveryZoneController`, `AdminDeliveryProviderController`) — plain CRUD reference data, used by the order-fulfillment update form above (fee/coverage per zone, courier directory per provider). Note zone `delivery_fee` isn't currently applied automatically anywhere in checkout (see checkout section above) — it exists as reference data an admin can consult/assign manually.

**Not actually wired up despite existing in the schema:** `PurchaseOrder` and `PurchaseOrderItem` (`app/Models/PurchaseOrder.php`, `PurchaseOrderItem.php`) have migrations and models — a `Supplier` restocking concept — but **there is no controller, no routes, and no admin views for them.** `README.md`'s mention of a "supplier and purchase order management" flow is aspirational for the purchase-order half; only the supplier directory itself is a real, working admin feature today. If asked to build this out, the models/schema are already there — it needs an `Admin\PurchaseOrderController`, routes added to the `admin.` group in `routes/web.php`, and views under `resources/views/admin/purchase-orders/`.

### 6. Order fulfillment lifecycle (tying it together)

```
Customer checkout          Order created: status=pending, payment_status=unpaid
        │                  shipping_total=0 (not auto-calculated from delivery zone)
        ▼
Admin opens the order      Any pending payment notification is marked "seen" on open
        │
        ▼
Admin confirms payment     payment_status -> paid  =>  payment_confirmed_at stamped
        │                  (COD/bank transfer confirmed manually — there's no
        │                   automatic payment verification for these methods)
        ▼
Admin assigns delivery      delivery_provider_id / tracking_number set on the order
        │                  -> a `shipments` row is created/updated to match
        ▼
Admin marks shipped/        shipped_at / delivered_at set -> shipment status follows
delivered
```

## Where to look for more detail

- **Table-by-table schema:** `README.md`, "Database Schema" section.
- **Hosting, env vars, deploy troubleshooting:** `DEPLOYMENT.md`.
- **Payment integration (currently disabled in the UI, code intact):** `app/Services/BakongService.php`, `app/Services/KhqrGenerator.php`, `app/Http/Controllers/Storefront/CheckoutController.php`.
- **Test coverage (thin — read before assuming a change is "safe"):** `tests/Feature/CartQuantityTest.php` is the only meaningful test in the suite.
