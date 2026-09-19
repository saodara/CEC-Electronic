# CEC Electronic — E-Commerce Platform
### Project Presentation Content (Full / Detailed Version)

---

## 1. Research Context

Technology plays an increasingly important role in growing businesses and delivering services in the digital era. The rapid growth of internet technology has made it easier, cheaper, and more efficient to run a business online than through physical premises alone.

E-Commerce (Electronic Commerce) has changed the way business is done, shifting it from **Traditional Commerce** to **Digital Commerce**:

- Customers can search for product information, prices, and specifications online without needing to visit a physical store.
- An E-Commerce system lets customers browse products, add them to a shopping cart, and place orders easily, at their own pace.
- Selling online helps a business expand its market and reach a much wider range of customers, well beyond people who happen to live or pass near the physical shop.
- An E-Commerce system gives customers the convenience of getting information and completing purchase transactions at any time, day or night — the store is effectively open 24/7.
- Integrating online payment methods such as **KHQR / Bakong** makes the customer's payment process faster, safer, and more convenient than cash-only transactions.
- Building an E-Commerce system gives a business clear, organized product and sales data that is easy to track, audit, and use for decision-making.
- Centralizing product, stock, and order data in one system reduces the risk of human error that comes from manual, paper-based, or spreadsheet-based record keeping.
- Digital storefronts make it possible to run promotions, feature products, and highlight stock in ways that are difficult to do consistently in a small physical shop.
- Customers increasingly *expect* to be able to research a product online before buying it, even for electronics they might eventually collect or have delivered — a business without an online presence risks losing that customer at the research stage.
- E-Commerce also creates a natural digital paper trail (orders, payments, timestamps) that supports better customer service, dispute resolution, and business reporting than word-of-mouth or handwritten receipts.

**CEC Electronic** applies this idea directly. It is a full e-commerce platform built for a real computer/electronics retail store in Cambodia, combining:
- A public **storefront** for customers to browse and buy, and
- An internal **admin back office** for the business to run day-to-day operations,

in a single Laravel web application, with a payment method (KHQR/Bakong) native to the Cambodian market.

---

## 2. Project Objectives

- Build a complete E-Commerce system for selling computer/electronics products online.
- Allow customers to search, view detailed product information, and place orders easily, without needing technical knowledge.
- Provide the business (Admin) with a system to manage products, categories, stock, orders, and customer information from one place.
- Support digital payment methods (KHQR / Bakong) to make the buying and selling process faster, safer, and more convenient for both sides.
- Reduce the business's dependence on traditional, in-person selling and help it move toward digital, online sales.
- Give the business visibility into its own operations: how many orders exist, how much revenue has been made, which products are low on stock, and who its customers are.
- Support the full order lifecycle digitally — from the moment a customer places an order to the moment it is delivered — rather than tracking fulfillment by memory or paper.
- Support nationwide reach by including delivery-zone and delivery-provider management, so the business is not limited to walk-in customers near the shop.
- Keep historical order data accurate over time (e.g., preserving what a product's name/price was *at the time of purchase*), so past orders remain correct even if products are later edited, renamed, or removed.
- Build the system on a modern, maintainable, and scalable technology stack that can keep evolving with the business (cloud hosting, serverless database, containerized deployment).

---

## 3. Problems the Project Solves

### 3.1 Customer-side problems
- Customers found it difficult to search for and compare products when sales were done the traditional (in-store) way.
- Customers had to spend time and money traveling to the physical store just to view and buy products.
- Without online access, customers could only shop during the store's physical opening hours.
- Comparing prices, specifications, and stock availability was slow and required either visiting in person or calling/messaging the shop directly.
- There was no way for a customer to review their own order history or track past purchases.

### 3.2 Business-side / operational problems
- Recording product data and orders by hand (or in loose spreadsheets) could easily lead to mistakes: wrong prices, wrong quantities, lost orders.
- Stock and order management was inefficient without a centralized management system — there was no single source of truth for "how much stock is really left."
- Traditional payment and ordering took time and required many manual steps (write down the order, calculate the total, confirm payment separately, arrange delivery separately).
- The business had no easy way to see aggregate numbers — total revenue, order count, low-stock alerts — without manually tallying receipts.
- Customer information was scattered and not organized in a way that let the business see repeat customers or total spend per customer.
- Supplier information (contacts, payment terms) was not centrally tracked.
- There was no structured way to manage delivery coverage, delivery fees, or courier partners across different provinces.

### 3.3 Market-reach problems
- The business was limited in how far it could reach customers who lived far from the store's physical location.
- Without an online payment option, out-of-town or long-distance customers had few safe ways to pay before receiving goods.

### 3.4 What the platform delivers as a result
- A searchable, always-available online catalog, removing the need to travel just to browse.
- A single admin system for products, stock, orders, customers, suppliers, and delivery — replacing manual records with structured, queryable data.
- Digital payment via KHQR/Bakong, so payment can be confirmed remotely and safely, even for a customer who is not physically in the shop.
- **Free delivery to all 25 provinces and cities in Cambodia**, removing the geographic limitation that traditional in-store selling had and turning a local shop into a nationwide storefront.
- Automatic, snapshot-based order history, so what a customer bought and paid stays accurate and auditable even as the live product catalog changes.

---

## 4. Technology & Tools Used in the Project

| Category | Choice | Notes |
|---|---|---|
| **Programming Language** | PHP 8.3 | Backend language |
| **Framework** | Laravel 13 | MVC framework: routing, Eloquent ORM, migrations, queues, scheduler |
| **Frontend Templating** | Blade | Laravel's server-rendered template engine — no separate SPA framework |
| **CSS Framework** | Tailwind CSS 4 | Utility-first styling |
| **Build Tool** | Vite | Frontend asset bundling |
| **Database Platform** | Neon | Managed, serverless Postgres hosting with branching & autoscaling |
| **Database Engine** | PostgreSQL | Relational database, accessed via `pdo_pgsql` |
| **Deployment Platform** | Fly.io | Cloud application hosting, `fly.toml` in the `sin` (Singapore) region |
| **Server Type** | Cloud server (containerized) | Runs the app as a Docker image, not a traditional bare-metal/VPS setup |
| **Web Server (in-container)** | Nginx | Reverse proxy / static file serving inside the container |
| **App Server (in-container)** | PHP-FPM | Executes PHP/Laravel |
| **Base Image** | `php:8.3-fpm` (Docker) | Single-stage Docker build |
| **Payment Integration** | KHQR / Bakong | Cambodia's National Bank of Cambodia (NBC) QR payment standard |
| **Version Control** | Git | Distributed version control |
| **Code Hosting** | GitHub | `github.com/saodara/CEC-Electronic` |
| **Local Dev Environment** | Docker Compose | Runs the same image locally as in production |
| **Code Style** | Laravel Pint | Automated PHP code formatting |
| **Testing Framework** | PHPUnit | Unit and feature tests |
| **API Testing Tool** | Postman | Manual/exploratory testing of every route (storefront, cart, checkout, admin) with a ready-to-import collection; documents CSRF-exempt vs. CSRF-protected endpoints |
| **Queue Driver** | Database (`QUEUE_CONNECTION=database`) | Background job processing |
| **Session Driver** | Cookie (production) | Secure cookie-based sessions on Fly.io |
| **Node.js (build-time only)** | Node 22 (via NodeSource) | Only used to build frontend assets; removed from the final runtime image |
| **QR Rendering Library** | qrcodejs (self-hosted) | Renders the KHQR string as a scannable QR code in the browser |

---

## 5. System Overview

CEC Electronic is one Laravel application split into three functional areas that share the same codebase and database:

1. **Storefront** — the public-facing shop, open to guests and logged-in customers.
2. **Customer Accounts** — registration, login, and order history for shoppers.
3. **Admin Panel** — the internal back office used by the business to run the store.

### 5.1 Storefront Features
- Home page, category browsing, and dedicated brand pages.
- Product detail pages with images, price, and specifications (stored as structured JSON per product).
- Product search across the entire catalog.
- Shopping cart:
  - Add, update quantity, and remove items.
  - Works for **both guests** (tracked by an anonymous session ID) **and logged-in customers** (tracked by user ID).
  - Re-adding the same product to the cart increases its quantity rather than creating a duplicate row.
  - The cart price is "snapshotted" at the moment an item is added — later product price changes don't silently change what's already sitting in someone's cart.
  - Cart items are protected by an ownership check, so one guest/customer can never modify another's cart by guessing an internal ID.
  - **Guest-to-account cart merge:** if a guest adds items to their cart and *then* registers or logs in, their guest cart is automatically merged into their new account cart (combining quantities for matching products) — nothing is lost.
- Checkout flow:
  - Requires an account (login or register) before placing an order.
  - Collects shipping details: name, phone, address line 1/2, city, province.
  - Delivery-zone selection.
  - Payment method selection, including **KHQR / Bakong** QR payment alongside cash-on-delivery / bank-transfer.
  - Empty-cart and double-submission are handled gracefully (e.g., a double-click on "Place order" doesn't crash the page).
- Order success page:
  - Shows the order confirmation and a live KHQR code (auto-regenerated if the previous one has expired).
  - Polls the server every 15 seconds to check whether the Bakong payment has been confirmed, and automatically shows a "Payment successful" confirmation once it is — no manual refresh needed.

### 5.2 Customer Account Features
- Register / login / logout.
- Personal account dashboard.
- Full order history and order detail pages, scoped only to the logged-in customer's own orders.
- Placing a guest order with a given email and later registering an account with the same email **automatically links** that earlier order to the new account.

### 5.3 Admin Panel Features
- **Separate, session-based admin authentication** — independent from normal customer login. Being marked as an admin user alone is not enough; an admin must also log in through the dedicated `/admin/login` form before the admin area is reachable, adding an extra layer of protection.
- **Dashboard** (the admin home page) with live operational metrics:
  - Product count, category count, order count.
  - Unique customer count (derived from distinct phone numbers across orders).
  - Total revenue (sum of completed order totals).
  - Low-stock alert count (products at or below a stock threshold).
  - Unread payment-notification count.
  - Recent products and recent orders lists, for a quick daily overview.
- **Product management** — full CRUD (create, read, update, delete) for products: name, description, price, compare-at price, cost price, stock quantity, active/featured flags, main image, gallery images, and structured specifications.
  - Product URLs (slugs) are generated automatically from the product name, and only regenerate if the name actually changes — so editing other fields never silently breaks an existing product link.
- **Category management** — full CRUD, including parent/sub-category relationships for organizing the catalog into a hierarchy.
- **Order management**:
  - View a paginated list of all orders.
  - View full order detail (customer info, items, totals, payment/delivery status).
  - Update order **status** (pending, processing, shipped, completed, cancelled) — this is separate from payment status.
  - Update **payment status** (unpaid, paid, refunded, failed) and manually verify/confirm a payment as a fallback path.
  - Assign delivery zone, delivery provider, and tracking number to an order, which automatically keeps a matching shipment record in sync.
- **Customer directory** — customers are looked up and grouped **by phone number** (not just by registered account), showing order count, total amount spent, and last order date per phone number — useful for identifying repeat customers even if they don't have a formal account.
- **Supplier management** — a directory of the business's own suppliers: company name, contact person, phone, email, address, and payment terms.
- **Delivery zone & delivery provider management** — configuring delivery coverage areas, delivery fees, estimated delivery time, and a directory of courier/delivery partners used to fulfill nationwide orders (all 25 provinces/cities).

### 5.4 Order Fulfillment Lifecycle (End-to-End)

```
1. Customer checks out
   → Order created: status = "pending", payment_status = "unpaid"

2. Admin opens the order
   → Any pending payment notification is automatically marked "seen"

3. Payment is confirmed
   → Automatically (KHQR/Bakong polling or background job), OR
   → Manually by an admin (cash on delivery / bank transfer)
   → payment_status becomes "paid", timestamp recorded

4. Admin assigns delivery
   → Delivery provider + tracking number attached to the order
   → A shipment record is created/kept in sync automatically

5. Order is shipped, then delivered
   → Shipped/delivered timestamps recorded
   → Order status progresses to "shipped" then "completed"
```

---

## 6. Database Design (Detailed)

The system uses **PostgreSQL**, with the schema fully managed through Laravel migrations (no manual schema edits). Core tables:

| Table | Purpose | Key columns |
|---|---|---|
| `users` | Shared table for customers **and** admins | `name`, `email`, `password`, `is_admin` (boolean flag distinguishing admin accounts) |
| `categories` | Product categories, with sub-category support | `parent_id` (self-referencing), `name`, `slug`, `description`, `image`, `is_active`, `sort_order` |
| `products` | Product catalog | `category_id`, `supplier_id`, `name`, `slug`, `sku`, `description`, `price`, `compare_at_price`, `cost_price`, `stock_quantity`, `is_active`, `is_featured`, `image`, `images` (JSON gallery), `specifications` (JSON) |
| `cart_items` | Shopping cart rows | `user_id` (nullable) OR `session_id` (nullable) — one or the other identifies the cart owner; `product_id`, `quantity`, `unit_price` |
| `customer_addresses` | Saved shipping addresses | `user_id`, `label`, `recipient_name`, `phone`, address lines, `city`, `province`, `postal_code`, `country`, `is_default` |
| `orders` | Placed orders | `order_number` (unique), `user_id`, customer name/email/phone, `status`, `payment_status`, `payment_method`, KHQR/Bakong fields (`bakong_qr_string`, `bakong_qr_md5`, `bakong_session_id`, `bakong_checkout_url`), `shipping_method`, `delivery_zone_id`, `delivery_provider_id`, `tracking_number`, `shipped_at`, `delivered_at`, `subtotal`, `shipping_total`, `discount_total`, `grand_total`, `shipping_address` (JSON snapshot), `notes`, `placed_at` |
| `order_items` | Line items per order | `order_id`, `product_id` (nullable), `product_name`, `sku` — **snapshotted at time of purchase** so later product edits/deletion never change historical order data; `quantity`, `unit_price`, `line_total` |
| `suppliers` | Business's own suppliers | `name`, `company_name`, `email`, `phone`, `website`, `address`, `contact_person`, `payment_terms`, `is_active`, `notes` |
| `purchase_orders` / `purchase_order_items` | Supplier purchase/restocking records | `po_number`, `supplier_id`, `status`, `subtotal`, `grand_total`, `expected_date`, `received_date` (+ line items per product/quantity/cost) |
| `delivery_zones` | Delivery coverage & pricing | `name`, `city`, `province`, `delivery_fee`, `free_delivery_minimum`, `estimated_days`, `is_active` |
| `delivery_providers` | Courier directory | `name`, `phone`, `email`, `tracking_url`, `base_fee`, `is_active` |
| `shipments` | Per-order shipment tracking | `order_id`, `delivery_provider_id`, `tracking_number`, `status`, `delivery_fee`, `picked_up_at`, `delivered_at` |

**Design notes worth presenting:**
- Money columns were originally stored as unsigned integers and later migrated to `decimal` columns for accurate currency handling.
- All product/category writes automatically invalidate the storefront's cache, so admin edits appear immediately instead of waiting out a cache expiry window.
- The schema already includes `purchase_orders`/`purchase_order_items` for supplier restocking, laying the groundwork for a future purchasing workflow beyond the current supplier directory.P% 
                              │
                              ▼
              Neon PostgreSQL (managed, serverless, TLS required)
```

**Container build (Docker):**
- Base image: `php:8.3-fpm`.
- Installs required PHP extensions: `pdo_pgsql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd`, `intl`, `zip`, `xml`.
- Installs Composer and Node.js 22 (build-time only, removed afterward to keep the final image small).
- Runs `composer install --no-dev --optimize-autoloader` and `npm run build` (Vite) during the image build.
- Copies in Nginx configuration and a custom entrypoint script; excludes `.env`, `.git`, `node_modules`, and `vendor` from the build context for security and size.

**Container boot sequence, every time it starts:**
1. Ensures a `.env` file exists (falls back to `.env.example` if missing).
2. Ensures `APP_KEY` is generated if not already set.
3. Fixes file/folder permissions for `storage/` and `bootstrap/cache`.
4. Waits for the database to become reachable before continuing (bounded retry, fails loudly and fast if the DB is unreachable, rather than hanging silently).
5. Runs database migrations automatically (`php artisan migrate --force`).
6. Seeds initial data (admin account + sample catalog) — safe to re-run without duplicating data.
7. Pre-compiles (caches) views, routes, and config **as root** before the app starts serving requests, avoiding runtime permission errors when rendering pages.
8. Starts the Laravel task scheduler in the background (for the automatic Bakong payment-check job).
9. Starts PHP-FPM, then Nginx in the foreground.

**HTTPS / reverse-proxy correctness:**
- Fly.io terminates HTTPS at its edge; the app is configured to trust the forwarded protocol/host headers so every generated URL (login, checkout, forms) is correctly `https://` without depending on a fragile hardcoded `APP_URL`.

**Database connectivity (Neon):**
- Uses TLS (`DB_SSLMODE=require`) for every connection — not optional.
- Neon separates compute from storage, enabling instant database branching, autoscaling, and scale-to-zero for cost efficiency during low-traffic periods.
- Schema is entirely migration-driven; there is no manual schema editing in production.

---

## 8. Payment Integration — KHQR / Bakong (Detailed)

KHQR is the National Bank of Cambodia's (NBC) standardized QR payment format, allowing a QR code to be scanned by essentially any participating Cambodian banking or e-wallet app.

### 8.1 How QR generation works
- The KHQR payload is generated **entirely locally**, in pure PHP — no external API call is needed just to produce the QR code.
- It follows the official KHQR / EMVCo-style tag-length-value (TLV) format, assembling fields such as:
  - Payload format indicator, point-of-initiation method
  - Merchant account information (the store's Bakong account alias)
  - Merchant category code, currency, and amount
  - Country and merchant name/city
  - A reference/bill number tied to the order number
  - A creation/expiry timestamp
  - A CRC16 checksum guaranteeing the QR content is well-formed
- Because the amount is embedded directly in the QR, the customer cannot accidentally pay the wrong amount.

### 8.2 How payment confirmation works
- The checkout success page renders the KHQR code and **polls the server every 15 seconds** to check whether payment has arrived.
- On confirmation, the page automatically transforms the "waiting for payment" view into a clear **"Payment successful"** confirmation, with the order number, amount, and a link to the order history — no manual page refresh required.
- A **background scheduled job** also checks pending Bakong payments automatically every 5 minutes, so payment confirmation does not depend on the customer keeping their browser tab open.
- Because a KHQR code carries a built-in expiry, the system automatically regenerates a fresh, valid QR code if a customer revisits an unpaid order after the previous one has expired.

### 8.3 Reliability & safety engineering
- **Resilience to network issues:** payment-check calls automatically retry on transient connection failures, and fail gracefully (never crash the checkout page) if the payment provider is temporarily unreachable.
- **Rate/quota protection:** the Bakong payment-check API has a limited daily request quota. The system protects this quota with three independent layers — a shared daily budget, a per-order request throttle, and a capped client-side polling window — so heavy usage from one order or one customer's browser tab can never exhaust the shared daily allowance for everyone else.
- **Fair background processing:** the automatic background payment checker prioritizes the **newest** unpaid orders first (rather than getting stuck re-checking an old backlog), and ignores orders that have been unpaid for more than 2 hours (treated as abandoned), so real, active customers are never starved behind stale carts.
- **Manual override:** admins can always mark an order's payment as confirmed manually, as a fallback for when the automatic payment-check service is temporarily unavailable.

### 8.4 Testing
- Automated tests cover QR generation correctness, transaction-check response handling, the daily-budget guard, graceful handling of DNS/network failures, and the background job's prioritization logic — all run against simulated responses, so tests never consume the real payment provider's daily quota.

---

## 9. Security & Reliability Practices

- **Separated authentication domains** — customer accounts and admin accounts share one `users` table but are gated differently: an admin needs both the `is_admin` flag **and** to have gone through the dedicated admin login flow, so a compromised or misconfigured flag alone can't open the back office.
- **Ownership checks everywhere sensitive:** cart items, orders, and account pages are all guarded so a user can only view or modify their own data — never another customer's, even if they guess an internal ID.
- **Historical data integrity:** order line items snapshot the product name, SKU, and price at the time of purchase, so past orders remain accurate and auditable even if a product is later renamed, repriced, or deleted.
- **Safe database seeding:** the admin account and sample data are seeded using "create or update" logic, so re-running the seeder (which happens on every fresh deployment) never creates duplicate records.
- **Secrets kept out of the deployed image:** environment files (`.env`) are explicitly excluded from the Docker build, so no local secrets are ever baked into a shipped container image.
- **Defensive caching:** storefront catalog reads are cached for performance, but writes are validated defensively and invalidate the whole cache on any product/category change — preventing corrupted or stale reads from silently serving wrong data to customers.
- **Graceful failure over hard crashes:** payment-related network failures, empty-cart edge cases, and double-submitted checkout forms are all handled with friendly fallbacks instead of raw errors.

---

## 10. Testing & Quality Assurance

- **PHPUnit** is used for automated testing (unit tests and feature tests).
- Automated coverage includes:
  - Cart quantity behavior.
  - The Bakong/KHQR service layer (QR generation, transaction checking, daily quota guard, network-failure handling).
  - The checkout order-creation flow, including the Bakong payment path.
  - The background payment-checking job's prioritization and time-window logic.
- **Laravel Pint** enforces consistent PHP code style across the codebase.
- API endpoints have also been manually exercised and documented via Postman during development.

---

## 11. Current Limitations & Roadmap

Being transparent about a system's current limitations is part of demonstrating a real understanding of it:

- **Shipping fee is not yet auto-calculated** from the selected delivery zone at checkout — it is currently set to zero at order creation and can be adjusted by an admin afterward.
- **Purchase order management** (restocking from suppliers) has database tables and models ready, but no admin screens or workflow are built yet — today, only the supplier *directory* itself is a fully working feature.
- **KHQR/Bakong payment quota:** the payment-verification API currently used is a self-service developer-tier account with a limited daily request quota — suitable for development and demonstration, but a real production rollout would need a proper Bakong business/merchant account with a higher quota (and ideally push/webhook notifications instead of polling).
- **Payment access token expiry:** the Bakong API access token has a fixed lifetime and needs to be periodically renewed; there is currently no automated warning before it expires.
- **Test coverage**, while present for the most business-critical and previously-buggy areas (cart, checkout, Bakong payments), is not yet comprehensive across the entire application.

**Planned/possible next steps:**
- Automatic delivery-fee calculation based on the selected delivery zone.
- A full purchase-order (restocking) workflow for admins.
- Migrating to a production-grade Bakong merchant account with webhook-based payment notifications.
- Expanding automated test coverage across the admin panel.
- Adding customer-facing order tracking tied directly to shipment status.

---

## 12. Summary

CEC Electronic demonstrates a complete, real-world digital transformation of a traditional electronics retail business: moving product discovery, ordering, and payment online for customers, while giving the business a centralized system to manage products, stock, orders, customers, suppliers, and nationwide delivery. It is built on a modern, cloud-deployed **PHP/Laravel + PostgreSQL (Neon)** stack, hosted on **Fly.io**, with native **Cambodian KHQR/Bakong** payment support engineered with real-world reliability safeguards — not just a proof-of-concept demo, but a system designed around the actual operational problems a growing e-commerce business faces.
