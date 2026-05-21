# CEC Electronic E-Commerce

Laravel website for CEC Electronic computer store. It includes storefront, product catalog, brands, cart, checkout, customer login/register, admin panel, suppliers, delivery, and order management.

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

## Database Control

All main system data is stored in MySQL:

- Admin and customer accounts: `users`
- Customer carts: `cart_items`
- Products and categories: `products`, `categories`
- Checkout orders: `orders`, `order_items`
- Delivery setup: `delivery_zones`, `delivery_providers`, `shipments`
- Suppliers and purchase records: supplier tables
- Login sessions and cache: `sessions`, `cache`

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
