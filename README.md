# CEC Electronic E-Commerce

Laravel website for CEC Electronic computer store. It includes storefront, product catalog, brands, cart, checkout, customer login/register, admin panel, suppliers, delivery, and order management.

## About the Project

CEC Electronic is a full e-commerce platform for a computer/electronics retailer, covering both the customer-facing storefront and the internal admin back office in a single Laravel app.

**Storefront**
- Home page, category browsing, brand pages, and product detail pages
- Product search
- Shopping cart (add, update quantity, remove)
- Checkout with delivery zone selection and **Bakong / KHQR** payment
- Customer registration/login and an account area for order history

**Admin panel**
- Products and categories management
- Order management (view, update status)
- Customer directory (lookup by phone)
- Supplier and purchase order management
- Delivery zones and delivery providers management
- Session-based admin authentication, separate from customer accounts

## Tech Stack

- **Backend:** PHP 8.3, Laravel 13
- **Frontend:** Blade templates, Tailwind CSS 4, Vite
- **Database:** MySQL / MariaDB
- **Payment:** Bakong / KHQR (Cambodia)
- **Dev tooling:** Docker Compose, Laravel Pint, PHPUnit
- **Optional integration:** Supabase JS client (`@supabase/supabase-js`, `@supabase/ssr`)

## Project Structure

```
app/Http/Controllers/
├── Storefront/    Home, catalog/search, cart, checkout
├── Customer/      Customer auth, account/orders
├── Admin/         Products, categories, orders, customers,
│                  suppliers, delivery zones/providers, admin auth
app/Models/        Product, Category, CartItem, Order, OrderItem,
                   Supplier, PurchaseOrder(Item), DeliveryZone,
                   DeliveryProvider, Shipment, CustomerAddress, User
routes/web.php     Storefront, customer, and admin routes
```

## Quick Start with Docker

The easiest way to run this project. Requires [Docker Desktop](https://www.docker.com/products/docker-desktop/).

1. Clone the project:

```bash
git clone <repo-url>
cd CEC-Electronic
```

2. Start everything:

```bash
docker compose up -d
```

First boot takes a few minutes — it installs PHP and JS dependencies, runs migrations, and seeds the database automatically.

3. Open the app:

```
http://localhost:8080
```

Admin panel:

```
http://localhost:8080/admin/login
```

Use the `ADMIN_EMAIL` and `ADMIN_PASSWORD` from your `.env` file (auto-created from `.env.example` on first boot).

**Stop the app:**

```bash
docker compose down
```

**Reset the database** (wipe all data and re-seed):

```bash
docker compose down -v
docker compose up -d
```

---

## Requirements

- PHP 8.2 or newer
- Composer
- MySQL 8 or MariaDB
- Node.js and npm, only needed if you want to build Vite assets
- Laragon for local development on Windows

## Local Setup With Laragon And MySQL

1. Put project in Laragon web folder:

```bash
D:\laragon\www\E-Commerce
```

2. Start Laragon:

- Start Apache or Nginx
- Start MySQL

3. Create database in Laragon:

- Open Laragon
- Click `Database`
- Open HeidiSQL or phpMyAdmin
- Create database:

```sql
CREATE DATABASE cec_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

4. Create or update the `.env` file for Laragon MySQL:

```env
APP_NAME="CEC Electronic"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://e-commerce.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cec_ecommerce
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
FILESYSTEM_DISK=public
ADMIN_EMAIL=saodara09dr@gmail.com
ADMIN_PASSWORD=your_admin_password
```

5. Install PHP packages:

```bash
composer install
```

6. Generate app key:

```bash
php artisan key:generate
```

7. Create storage link for uploaded images:

```bash
php artisan storage:link
```

8. Create tables and seed the real store data:

```bash
php artisan migrate --seed
```

This creates the MySQL tables for customers, admin users, sessions, cache, carts, products, categories, orders, order items, suppliers, delivery zones, and delivery providers. The first admin user is saved in the `users` table with `is_admin = 1`.

9. Run website:

```bash
php artisan serve
```

Then open:

```text
http://127.0.0.1:8000
```

Admin login:

```text
http://127.0.0.1:8000/admin/login
```

Use the admin email and password from `.env` after running `php artisan migrate --seed`. The seeder saves that admin account into the MySQL `users` table:

```env
ADMIN_EMAIL=saodara09dr@gmail.com
ADMIN_PASSWORD=your_admin_password
```

## Vultr MySQL Database Setup

Use this when your database is hosted on Vultr.

1. Create a Vultr Managed Database or MySQL server.

2. Create database:

```sql
CREATE DATABASE cec_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Create database user:

```sql
CREATE USER 'cec_user'@'%' IDENTIFIED BY 'your_private_database_password';
GRANT ALL PRIVILEGES ON cec_ecommerce.* TO 'cec_user'@'%';
FLUSH PRIVILEGES;
```

4. Allow your web server IP to connect in Vultr firewall or database trusted sources.

5. Update `.env` on your server:

```env
APP_NAME="CEC Electronic"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=your_vultr_mysql_host
DB_PORT=3306
DB_DATABASE=cec_ecommerce
DB_USERNAME=cec_user
DB_PASSWORD=your_private_database_password

SESSION_DRIVER=database
FILESYSTEM_DISK=public
ADMIN_EMAIL=saodara09dr@gmail.com
ADMIN_PASSWORD=your_admin_password
```

If Vultr gives you SSL settings for MySQL, keep the certificate files on the server and configure MySQL SSL in `config/database.php` if required.

6. Run migrations and seeders on the server:

```bash
php artisan migrate --seed
```

This saves the admin account, catalog, suppliers, delivery setup, sessions, cache, carts, and orders in the Vultr MySQL database.

For production updates after first install, use:

```bash
php artisan migrate --force
```

## Product Images

Local product images are stored in:

```text
public/images/Product Image
```

Brand images are stored in:

```text
public/images/Brand
```

Uploaded product/category images from admin are stored in:

```text
storage/app/public/products
storage/app/public/categories
```

Make sure this command has been run:

```bash
php artisan storage:link
```

## Bakong Payment

The checkout page includes Bakong / KHQR payment.

The current Bakong QR image is stored here:

```text
public/images/Payment/bakong-qr.jpg
```

To change the shop payment QR, replace that file with the real CEC Electronic Bakong QR image and keep the same filename:

```text
bakong-qr.jpg
```

For mobile customers, the system can show an `Open Bakong / bank app` button when a real payment deep link is configured.

Add the deep link from Bakong, KHQR, or your payment provider in `.env`:

```env
BAKONG_MERCHANT_NAME="CEC Electronic"
BAKONG_DEEPLINK=your_real_bakong_or_khqr_payment_link
```

Important: a normal QR image cannot force-open every customer's Cambodia bank app by itself. The automatic app opening needs a valid KHQR/Bakong payment deep link from the bank or payment provider. Without that link, customers scan the KHQR using ABA, ACLEDA, Wing, Bakong, or any KHQR-supported Cambodia banking app.

Checkout payment flow:

- Customer must login or register before checkout.
- Customer selects `Bakong / KHQR`.
- Customer clicks `Place order`.
- Payment popup opens with the Bakong QR.
- Customer scans and pays.
- Customer clicks `Paid / Confirm`.
- Popup closes and the order is created.

## Useful Commands

Clear cache:

```bash
php artisan optimize:clear
```

Run tests:

```bash
php artisan test
```

Re-seed product data:

```bash
php artisan db:seed --class=ProductSeeder
```

Build frontend assets if needed:

```bash
npm install
npm run build
```

## Database Schema

All data is stored in MySQL/MariaDB. Prices and monetary amounts are stored as unsigned integers (smallest currency unit, no decimals). Tables are created by the migrations in `database/migrations/`.

### Accounts & Access

- **`users`** — shared table for both customers and admins. Columns: `name`, `email` (unique), `email_verified_at`, `password`, `remember_token`, `is_admin` (boolean flag that grants access to `/admin/*`, protected by `EnsureAdminSession` middleware).
- **`customer_addresses`** — saved shipping addresses per customer. Belongs to `users`. Columns: `label`, `recipient_name`, `phone`, `address_line_1/2`, `city`, `province`, `postal_code`, `country` (defaults `Cambodia`), `is_default`.
- **`sessions`**, **`cache`**, **`jobs`** — Laravel's session/cache/queue tables (used since `SESSION_DRIVER=database`).

### Catalog

- **`categories`** — self-referencing (`parent_id`) for subcategories. Columns: `name`, `slug` (unique), `description`, `image`, `is_active`, `sort_order`.
- **`products`** — columns: `category_id` (FK, nullable), `supplier_id` (FK, nullable), `name`, `slug` (unique), `sku` (unique, nullable), `description`, `image`, `images` (JSON gallery), `specifications` (JSON key/value), `price`, `compare_at_price`, `cost_price`, `stock_quantity`, `is_active`, `is_featured`, `category` (legacy free-text label kept for backward compatibility).

### Cart & Checkout

- **`cart_items`** — one row per product in a cart. Linked to a logged-in customer via `user_id`, or to a guest via `session_id`; unique per `(user_id, product_id)` and `(session_id, product_id)`. Columns: `quantity`, `unit_price`.
- **`orders`** — columns: `order_number` (unique), `user_id` (FK, nullable for guest checkout), `customer_name`, `customer_email`, `customer_phone`, `status` (e.g. `pending`), `payment_status` (e.g. `unpaid`), `payment_method`, `shipping_method`, `subtotal`, `shipping_total`, `discount_total`, `grand_total`, `shipping_address` (JSON snapshot), `notes`, `placed_at`.
  - Delivery: `delivery_zone_id`, `delivery_provider_id`, `tracking_number`, `shipped_at`, `delivered_at`.
  - Payment tracking: `payment_confirmed_at`, `admin_payment_seen_at`.
  - Bakong/KHQR: `bakong_session_id`, `bakong_checkout_url`, `bakong_qr_string`, `bakong_qr_md5`.
- **`order_items`** — line items belonging to an `order`. Snapshots `product_name` and `sku` at time of purchase (independent of later product edits). Columns: `product_id` (FK, nullable), `quantity`, `unit_price`, `line_total`.

### Suppliers & Purchasing

- **`suppliers`** — columns: `name`, `company_name`, `email`, `phone`, `website`, `address`, `contact_person`, `payment_terms`, `is_active`, `notes`.
- **`purchase_orders`** — a supplier order used to restock inventory. Columns: `po_number` (unique), `supplier_id` (FK), `status` (e.g. `draft`), `subtotal`, `grand_total`, `expected_date`, `received_date`, `notes`.
- **`purchase_order_items`** — line items belonging to a `purchase_order`. Columns: `product_id` (FK), `quantity`, `unit_cost`, `line_total`.

### Delivery & Shipping

- **`delivery_zones`** — pricing/coverage areas. Columns: `name`, `city`, `province`, `delivery_fee`, `free_delivery_minimum`, `estimated_days`, `is_active`.
- **`delivery_providers`** — courier/partner directory. Columns: `name`, `phone`, `email`, `tracking_url`, `base_fee`, `is_active`, `notes`.
- **`shipments`** — one shipment per `order`, optionally tied to a `delivery_provider`. Columns: `tracking_number`, `status` (e.g. `pending`), `delivery_fee`, `picked_up_at`, `delivered_at`, `notes`.

### Quick Reference

| Concern | Tables |
| --- | --- |
| Accounts & access | `users`, `customer_addresses`, `sessions` |
| Catalog | `categories`, `products` |
| Cart & orders | `cart_items`, `orders`, `order_items` |
| Suppliers & purchasing | `suppliers`, `purchase_orders`, `purchase_order_items` |
| Delivery | `delivery_zones`, `delivery_providers`, `shipments` |
| Framework infra | `sessions`, `cache`, `jobs`, `password_reset_tokens` |

Use the admin panel for normal create, update, and delete work:

```text
http://127.0.0.1:8000/admin
```

Use HeidiSQL or phpMyAdmin only when you need direct database maintenance.

## Important Security Notes

- Change the admin password before using the website in a real store.
- After changing `ADMIN_EMAIL` or `ADMIN_PASSWORD`, run `php artisan db:seed` so the MySQL admin account is updated.
- Do not keep `APP_DEBUG=true` in production.
- Use a strong MySQL password on Vultr.
- Restrict Vultr database access to your server IP only.
- Keep `.env` private and never upload it publicly.
