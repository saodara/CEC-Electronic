# CEC Electronic — Project Flow Guide

This document explains **how the whole project works**: the customer (user) side, the admin side, the payment flow (Bakong KHQR), and what each part means. Short code snippets from the real project show how each step is done.

---

## 1. Overview

**CEC Electronic** is an online electronics store built with **Laravel** (PHP) and Blade views.

| Part | Who uses it | URL prefix | Main folders |
|------|-------------|------------|--------------|
| **Storefront (User page)** | Customers / guests | `/` | `app/Http/Controllers/Storefront`, `resources/views/shop`, `resources/views/checkout` |
| **Customer account** | Logged-in customers | `/account`, `/login`, `/register` | `app/Http/Controllers/Customer`, `resources/views/account` |
| **Admin panel** | Store staff (`is_admin = true`) | `/admin` | `app/Http/Controllers/Admin`, `resources/views/admin` |
| **Services (business logic)** | Used by controllers | — | `app/Services` |
| **Background job** | Scheduler | — | `app/Console/Commands`, `routes/console.php` |

### Big picture

```
 Guest / Customer                                   Admin
 ───────────────                                    ─────
 Home → Category/Brand/Search → Product             Admin Login
          │                                              │
          ▼                                              ▼
       Add to Cart  (CartService)                   Dashboard (stats)
          │                                              │
          ▼                                   ┌──────────┼──────────────┐
   Login / Register  (guest cart merged)      ▼          ▼              ▼
          │                               Products   Orders        Customers
          ▼                               Categories  - status      Suppliers
       Checkout  (CheckoutService)        Brands      - payment     Delivery zones
          │                                           - shipping
          ▼                                           - verify Bakong
   Bakong KHQR QR (BakongService)                     - receipt PDF
          │
          ▼
   Page polls payment status every 15s ──► Bakong API
          │                                   ▲
          ▼                                   │
     Order = PAID  ◄── scheduler job every 5 min (bakong:check-pending)
          │
          ▼
   My Account → Orders → Receipt PDF
```

---

## 2. Main Data (Models)

Located in `app/Models`.

| Model | Meaning |
|-------|---------|
| `User` | A customer or admin. `is_admin = true` means admin. |
| `Product` | An item for sale (name, slug, price, stock, image, category, brand, supplier). |
| `Category` | Product group (Laptops, Phones, Accessories…). |
| `Brand` | Manufacturer (Apple, ASUS…), has a logo. |
| `Supplier` | Who supplies the products to the store. |
| `CartItem` | One product in a cart. Owned by `user_id` (logged in) **or** `session_id` (guest). |
| `Order` | A placed order: customer info, totals, status, payment status, Bakong QR data. |
| `OrderItem` | A product line inside an order (snapshot of name, SKU, price, qty). |
| `DeliveryZone` / `DeliveryProvider` / `Shipment` | Shipping info set by admin. |

### Order status meanings

**`status`** (order progress):

| Value | Meaning |
|-------|---------|
| `pending` | Just placed, not handled yet (default) |
| `processing` | Admin is preparing it |
| `shipped` | Sent out to delivery |
| `completed` | Delivered / finished |
| `cancelled` | Cancelled |

**`payment_status`**:

| Value | Meaning |
|-------|---------|
| `unpaid` | Waiting for payment (default) |
| `paid` | Payment confirmed (by Bakong or admin) |
| `refunded` | Money returned |
| `failed` | Payment failed |

---

## 3. User Page Flow (Storefront)

All routes are in `routes/web.php`.

### 3.1 Browse products

| URL | Controller | Page shows |
|-----|-----------|------------|
| `GET /` | `HomeController` | Active categories, brands with logos, 12 newest products |
| `GET /category/{slug}` | `CatalogController@category` | Products in a category (with filters: processor, RAM, storage, price) |
| `GET /brands` | `CatalogController@brands` | All brands |
| `GET /brands/{slug}` | `CatalogController@brand` | Products of one brand |
| `GET /search?q=...` | `CatalogController@search` | Search results |
| `GET /product/{slug}` | `CatalogController@product` | Product detail page |

The catalog pages are **cached** (e.g. home for 5 minutes) to load fast:

```php
// app/Http/Controllers/Storefront/HomeController.php
$data = $callback();   // load categories, brands, products from DB
Cache::put('catalog.home', $data, 300); // keep for 300 seconds

return view('shop.home', compact('categories', 'brands', 'products'));
```

```php
// app/Http/Controllers/Storefront/CatalogController.php
public function product(string $slug): View
{
    $product = $this->cacheRemember(
        "catalog.product.{$slug}",
        fn () => Product::where('slug', $slug)->firstOrFail(),
        fn ($v) => $v instanceof Product
    );

    return view('shop.product', compact('product'));
}
```

### 3.2 Cart

| URL | Action |
|-----|--------|
| `GET /cart` | View cart |
| `POST /cart/{product}` | Add product (qty 1–99) |
| `PATCH /cart/items/{cartItem}` | Change quantity (0 = remove) |
| `DELETE /cart/items/{cartItem}` | Remove item |

**Key idea:** a guest can use the cart without logging in. The cart belongs to the **session** for guests and to the **user** after login.

```php
// app/Services/CartService.php
private function ownerColumn(Request $request): string
{
    return $request->user() ? 'user_id' : 'session_id';
}

public function add(Request $request, Product $product, int $quantity = 1): CartItem
{
    $cartItem = CartItem::firstOrNew([
        $this->ownerColumn($request) => $this->ownerValue($request),
        'product_id' => $product->id,
    ]);

    $cartItem->unit_price = $product->price;
    $cartItem->quantity = (int) $cartItem->quantity + max(1, $quantity);
    $cartItem->save();

    return $cartItem;
}
```

The cart controller returns **JSON** when called by JavaScript (AJAX), so the cart count updates without reloading the page:

```php
// app/Http/Controllers/Storefront/CartController.php
if ($request->wantsJson()) {
    return response()->json([
        'message' => 'Product added to cart.',
        'count'   => $this->cartService->count($request),
    ]);
}
return back()->with('status', 'Product added to cart.');
```

Security: a user can only change **their own** cart items:

```php
private function guardOwner(Request $request, CartItem $cartItem): void
{
    abort_unless($cartItem->{$this->ownerColumn($request)} === $this->ownerValue($request), 403);
}
```

### 3.3 Login / Register (customer)

| URL | Controller method |
|-----|------------------|
| `GET /login`, `POST /login` | `Customer\AuthController@login / authenticate` |
| `GET /register`, `POST /register` | `Customer\AuthController@register / store` |
| `POST /logout` | `Customer\AuthController@logout` |

Flow:

1. Save the current **guest session id** (to find the guest cart).
2. Log in / create the user.
3. Regenerate the session (security).
4. **Merge the guest cart** into the user's cart.
5. On register, older guest orders with the same email are linked to the new account.

```php
// app/Http/Controllers/Customer/AuthController.php
$sessionId = $request->session()->getId();

if (! Auth::attempt($credentials, $request->boolean('remember'))) {
    return back()->withErrors(['email' => 'Email or password is incorrect.']);
}

$request->session()->regenerate();
$this->cartService->mergeGuestCartIntoUser($sessionId, $request->user());

return redirect()->intended(route('account.dashboard'));
```

```php
// app/Services/CartService.php — merge guest cart
if ($userItem) {
    $userItem->increment('quantity', $guestItem->quantity); // same product → add qty
    $guestItem->delete();
} else {
    $guestItem->update(['user_id' => $user->id, 'session_id' => null]); // move it
}
```

### 3.4 Checkout

| URL | Meaning |
|-----|---------|
| `GET /checkout` | Checkout form (must be logged in) |
| `POST /checkout` | Place the order |
| `GET /checkout/success/{order}` | Order placed page + Bakong QR |
| `GET /checkout/payment-status/{order}` | JSON: is it paid yet? (polled by JS) |
| `POST /checkout/regenerate-qr/{order}` | Get a new QR after the old one expired |

**Step by step:**

1. Customer must be logged in, otherwise redirected to `/login`.
2. If the cart is empty (e.g. double click on "Place order") → back to cart.
3. Validate the form (name, phone, address, city…).
4. `CheckoutService::createOrder()` inside a **database transaction**:
   - Calculate subtotal from cart.
   - Create `Order` (`status = pending`, `payment_status = unpaid`, number like `EH-20260926-1234`).
   - Copy each cart item into `OrderItem`.
   - Clear the cart.
5. If payment method is `bakong` → generate a KHQR code.
6. Redirect to the success page.

```php
// app/Http/Controllers/Storefront/CheckoutController.php
public function store(Request $request): RedirectResponse
{
    if (! $request->user()) {
        return redirect()->guest(route('customer.login'));
    }

    if ($this->cartService->items($request)->isEmpty()) {
        return redirect()->route('shop.cart')->with('status', 'Your cart is empty.');
    }

    $data = $request->validate([
        'customer_name'  => ['required', 'string', 'max:255'],
        'customer_phone' => ['required', 'string', 'max:50'],
        'address_line_1' => ['required', 'string', 'max:255'],
        'city'           => ['required', 'string', 'max:100'],
        // ...
    ]);

    $order = $this->checkoutService->createOrder($request, $data);

    if ($order->payment_method === 'bakong') {
        $this->issueQr($order);
    }

    return redirect()->route('checkout.success', $order);
}
```

```php
// app/Services/CheckoutService.php
return DB::transaction(function () use ($request, $data, $items) {
    $subtotal = $items->sum(fn (CartItem $item) => $item->line_total);

    $order = Order::create([
        'order_number'   => $this->orderNumber(),   // EH-YYYYMMDD-####
        'user_id'        => $request->user()?->id,
        'status'         => 'pending',
        'payment_status' => 'unpaid',
        'payment_method' => $data['payment_method'] ?? 'bakong',
        'subtotal'       => $subtotal,
        'grand_total'    => $subtotal,
        // ... customer + shipping address
    ]);

    foreach ($items as $item) {
        $order->items()->create([
            'product_id'   => $item->product_id,
            'product_name' => $item->product?->name ?: 'Deleted product',
            'quantity'     => $item->quantity,
            'unit_price'   => $item->unit_price,
            'line_total'   => $item->line_total,
        ]);
    }

    $this->cartService->clear($request);
    return $order;
});
```

> Why a transaction? If anything fails halfway, nothing is saved — you never get an order without items, or a cleared cart without an order.

### 3.5 Bakong KHQR payment flow (most important)

**Bakong** is Cambodia's national payment system. **KHQR** is its QR code standard. The customer scans the QR with their banking app (ABA, ACLEDA, Wing…).

```
 [Place order]
      │
      ▼
 BakongService::generateQrForOrder()
   → KHQR string + md5 hash + expires_at (3 minutes)
   → saved on the order
      │
      ▼
 Success page shows QR + countdown timer
      │
      ├── JS polls  GET /checkout/payment-status/{order}  every 15 s
      │      └── server checks Bakong API (max once per 60 s per order)
      │             POST {BAKONG_PROD_BASE_API_URL}/check_transaction_by_md5 { md5 }
      │                 found → payment_status = 'paid'
      │
      ├── QR expired? → one final check → "Generate new QR" button
      │
      └── Customer closed tab? → scheduler job still checks every 5 min
```

**1. Generate the QR**

```php
// app/Services/BakongService.php
public function generateQrForOrder(Order $order): ?array
{
    $qr = KhqrGenerator::individual(
        accountId:         $this->accountUsername,
        merchantName:      $this->accountName,
        merchantCity:      $this->merchantCity,
        amount:            (float) $order->grand_total,
        currency:          'USD',
        billNumber:        $order->order_number,
        expirationSeconds: $this->qrExpirySeconds, // default 180s
    );

    return [
        'qr'         => $qr['qr'],   // string drawn as the QR image
        'md5'        => $qr['md5'],  // used to ask Bakong "was this paid?"
        'expires_at' => Carbon::createFromTimestamp($qr['expires_at']),
    ];
}
```

**2. Browser polls the status** (`resources/views/checkout/success.blade.php`)

```js
var POLL_INTERVAL_MS = 15000;

function checkPayment() {
    fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (data.is_paid)    { markPaid(); return; }   // show "Paid ✓"
            if (data.qr_expired) { showExpired(); return; } // show "new QR" button
        });
}
timer = window.setInterval(checkPayment, POLL_INTERVAL_MS);
```

**3. Server checks Bakong (with throttle)**

```php
// CheckoutController@paymentStatus
if ($order->payment_status === 'unpaid' && $order->bakong_qr_md5) {
    // only really call Bakong once per 60s per order
    if (app(BakongService::class)->allowOnce("bakong-check:{$order->id}", 60)) {
        $tx = app(BakongService::class)->checkTransactionByMd5($order->bakong_qr_md5);

        if ($tx !== null) {
            $order->update(['payment_status' => 'paid', 'payment_confirmed_at' => now()]);
        }
    }
}

return response()->json([
    'is_paid'    => $order->payment_status === 'paid',
    'qr_expired' => $order->payment_status !== 'paid' && $order->bakongQrExpired(),
]);
```

> **Why throttle?** Bakong's API only allows a small number of checks per day for the whole store (`BAKONG_DAILY_CHECK_LIMIT`, default 90). `BakongService` keeps a shared daily counter so the store never goes over the limit.

**4. Regenerate QR** — before creating a new QR, the old one is checked once more (in case the customer paid in the last seconds):

```php
// CheckoutController@regenerateQr
if ($order->bakong_qr_md5
    && app(BakongService::class)->checkTransactionByMd5($order->bakong_qr_md5, enforceBudget: false) !== null) {
    $order->update(['payment_status' => 'paid', 'payment_confirmed_at' => now()]);
    return redirect()->route('checkout.success', $order)->with('status', 'Payment received.');
}

$this->issueQr($order); // new QR, new md5, new 3-minute timer
```

**5. Background job** — covers customers who close the tab:

```php
// routes/console.php
Schedule::command('bakong:check-pending --limit=5')->everyFiveMinutes();
```

```php
// app/Console/Commands/CheckPendingBakongPayments.php
$orders = Order::query()
    ->where('payment_method', 'bakong')
    ->where('payment_status', 'unpaid')
    ->whereNotNull('bakong_qr_md5')
    ->where('created_at', '>=', now()->subHours(2)) // only recent orders
    ->orderByDesc('created_at')
    ->limit($limit)
    ->get();

foreach ($orders as $order) {
    if (! $bakong->allowOnce("bakong-job-check:{$order->id}", 1200)) continue; // each order max every 20 min

    if ($bakong->checkTransactionByMd5($order->bakong_qr_md5) !== null) {
        $order->update(['payment_status' => 'paid', 'payment_confirmed_at' => now()]);
    }
}
```

> The scheduler needs `php artisan schedule:work` (dev) or a cron `* * * * * php artisan schedule:run` (server).

### 3.6 My Account

| URL | Meaning |
|-----|---------|
| `GET /account` | Dashboard: last 10 orders |
| `GET /account/orders` | All orders (paginated) |
| `GET /account/orders/{order}` | Order detail |
| `GET /account/orders/{order}/receipt` | Download PDF receipt |
| `GET /account/orders/{order}/receipt/view` | View PDF receipt in browser |

Customers can only see **their own** orders:

```php
// app/Http/Controllers/Customer/AccountController.php
abort_unless($order->user_id === $request->user()->id, 403);
return $receipts->download($order); // ReceiptPdf service → resources/views/receipts/order.blade.php
```

---

## 4. Admin Page Flow

### 4.1 Admin login & protection

| URL | Meaning |
|-----|---------|
| `GET /admin/login` | Admin login form |
| `POST /admin/login` | Check email/password **and** `is_admin` |
| `POST /admin/logout` | Logout |

```php
// app/Http/Controllers/Admin/AuthController.php
if (! Auth::attempt([...]) || ! Auth::user()?->is_admin) {
    Auth::logout(); // normal customers cannot log in here
    return back()->withErrors(['email' => 'The admin email or password is incorrect.']);
}

$request->session()->regenerate();
$request->session()->put('admin_authenticated', true); // admin flag in session
return redirect()->intended(route('admin.dashboard'));
```

Every `/admin/*` page goes through the `EnsureAdminSession` middleware:

```php
// app/Http/Middleware/EnsureAdminSession.php
if (! Auth::check()
    || ! Auth::user()?->is_admin
    || ! $request->session()->get('admin_authenticated', false)) {
    return redirect()->guest(route('admin.login'));
}
return $next($request);
```

```php
// routes/web.php
Route::prefix('admin')->name('admin.')
    ->middleware(\App\Http\Middleware\EnsureAdminSession::class)
    ->group(function () {
        Route::get('/', [AdminProductController::class, 'dashboard'])->name('dashboard');
        Route::resource('products', AdminProductController::class);
        Route::resource('categories', AdminCategoryController::class)->except(['show']);
        Route::resource('brands', AdminBrandController::class)->except(['show']);
        Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'update']);
        Route::get('orders/{order}/receipt', [AdminOrderController::class, 'receipt']);
        Route::post('orders/{order}/verify-payment', [AdminOrderController::class, 'verifyPayment']);
        Route::get('customers', [AdminCustomerController::class, 'index']);
        Route::get('customers/{phone}', [AdminCustomerController::class, 'show']);
        Route::resource('suppliers', AdminSupplierController::class)->except(['show']);
        Route::resource('delivery-zones', AdminDeliveryZoneController::class)->except(['show']);
    });
```

### 4.2 Dashboard (`GET /admin`)

Shows store statistics:

| Stat | Meaning |
|------|---------|
| products / categories / orders | Total counts |
| customers | Distinct customer phone numbers |
| revenue | Sum of all orders' `grand_total` |
| **payment_notifications** | Orders paid but admin has **not opened them yet** |
| low_stock | Products with stock ≤ 5 |

```php
// Admin\ProductController@dashboard
'payment_notifications' => Order::query()
    ->whereNotNull('payment_confirmed_at')   // paid
    ->whereNull('admin_payment_seen_at')     // admin not seen yet
    ->count(),
'low_stock' => Product::where('stock_quantity', '<=', 5)->count(),
```

### 4.3 Products (`/admin/products`)

Full CRUD: list (filter by brand), create, edit, delete.

- Auto-creates a unique **slug** from the name (`iphone-15`, `iphone-15-2`…).
- Uploads the image to `storage/app/public/products` (shown via `public/storage` symlink).
- Deletes the old image when a new one is uploaded.

```php
// Admin\ProductController@store
$data = $this->validatedData($request);          // name, price, stock, sku, image...
$data['slug'] = $this->uniqueSlug($data['name']);
$this->storeUploadedImage($request, $data);      // ->store('products', 'public')

Product::create($data);
return redirect()->route('admin.products.index')->with('status', 'Product created.');
```

> If images don't show, run `php artisan storage:link` and check `public/storage` points to this project's `storage/app/public`.

### 4.4 Categories, Brands, Suppliers, Delivery zones

Same simple CRUD pattern (index / create / edit / delete) with a shared `_form.blade.php` for create and edit.

| Page | Used for |
|------|----------|
| Categories | Group products; shown on home and `/category/{slug}` |
| Brands | Brand logos on home and `/brands` |
| Suppliers | Who supplies each product (internal) |
| Delivery zones | Shipping areas assigned to orders |

### 4.5 Orders (`/admin/orders`) — main admin workflow

```
Orders list ─► open order ─► (marks payment notification as seen)
                   │
                   ├── Update status: pending → processing → shipped → completed
                   ├── Update payment: unpaid / paid / refunded / failed
                   ├── Assign delivery zone + provider + tracking number
                   │       └── creates/updates a Shipment record
                   ├── "Verify payment" → ask Bakong now (Bakong orders)
                   └── Download receipt PDF
```

**Opening an order** clears its payment notification:

```php
// Admin\OrderController@show
if ($order->payment_confirmed_at && ! $order->admin_payment_seen_at) {
    $order->update(['admin_payment_seen_at' => now()]);
}
```

**Updating an order**:

```php
// Admin\OrderController@update
if ($data['payment_status'] === 'paid' && $order->payment_status !== 'paid') {
    $data['payment_confirmed_at'] = now();   // admin marked it paid manually (e.g. cash)
    $data['admin_payment_seen_at'] = now();
}

$order->update($data);

if (! empty($data['delivery_provider_id']) || ! empty($data['tracking_number'])) {
    $order->shipments()->updateOrCreate(
        ['tracking_number' => $data['tracking_number'] ?? null],
        [
            'delivery_provider_id' => $data['delivery_provider_id'] ?? null,
            'status' => $data['delivered_at'] ? 'delivered' : ($data['shipped_at'] ? 'shipped' : 'pending'),
            // ...
        ]
    );
}
```

**Verify payment manually** — if a customer says "I paid but it still shows unpaid":

```php
// Admin\OrderController@verifyPayment
if (! $bakong->allowOnce("bakong-admin-verify:{$order->id}", 10)) {
    return back()->with('status', 'Already checking — please wait a moment.');
}

$transaction = $bakong->checkTransactionByMd5($order->bakong_qr_md5, enforceBudget: false);

if ($transaction === null) {
    return back()->with('status', 'No matching Bakong payment found yet.');
}

$order->update([
    'payment_status' => 'paid',
    'payment_confirmed_at' => now(),
    'admin_payment_seen_at' => now(),
]);
```

### 4.6 Customers (`/admin/customers`)

Customers are grouped **by phone number** from orders (so guest buyers also appear):

```php
// Admin\CustomerController@index
Order::query()
    ->select([
        'customer_phone',
        DB::raw('MAX(customer_name) as customer_name'),
        DB::raw('COUNT(*) as orders_count'),
        DB::raw('SUM(grand_total) as total_spent'),
        DB::raw('MAX(created_at) as last_order_at'),
    ])
    ->groupBy('customer_phone')
    ->orderByDesc('last_order_at')
    ->paginate(10);
```

`/admin/customers/{phone}` shows all orders of that customer with totals and unpaid count.

---

## 5. Full Order Lifecycle (User + Admin together)

| # | Who | Action | Order state |
|---|-----|--------|-------------|
| 1 | Customer | Adds products to cart | — |
| 2 | Customer | Logs in, fills checkout form, places order | `pending` / `unpaid` |
| 3 | System | Generates Bakong KHQR (3-min expiry) | `pending` / `unpaid` |
| 4 | Customer | Scans QR with bank app and pays | — |
| 5 | System | Page polling or scheduler finds payment | `pending` / **`paid`** |
| 6 | Admin | Sees payment notification on dashboard, opens order | notification cleared |
| 7 | Admin | Sets `processing`, packs items | `processing` / `paid` |
| 8 | Admin | Assigns delivery + tracking, sets `shipped` | `shipped` / `paid` |
| 9 | Admin | Delivered → `completed` | `completed` / `paid` |
| 10 | Customer | Views order & downloads PDF receipt in My Account | — |

---

## 6. Important Config (`.env`)

```env
APP_URL=https://your-domain

# Bakong KHQR (config/services.php → services.bakong.*)
BAKONG_PROD_BASE_API_URL=https://api-bakong.nbc.gov.kh/v1
BAKONG_ACCOUNT_USERNAME=yourname@bank
BAKONG_ACCOUNT_NAME="CEC Electronic"
BAKONG_ACCESS_TOKEN=...
BAKONG_MERCHANT_CITY="Phnom Penh"
BAKONG_DAILY_CHECK_LIMIT=90
BAKONG_QR_EXPIRY_SECONDS=180
```

> These map to `config/services.php` → `bakong`. More detail: `BAKONG_KHQR_INTEGRATION.md`, deployment: `DEPLOYMENT.md`.

---

## 7. Useful Commands

```bash
composer install && npm install
php artisan migrate --seed        # create tables (+ sample data)
php artisan storage:link          # make product images visible
npm run dev                       # build frontend assets
php artisan serve                 # run the site at http://127.0.0.1:8000
php artisan schedule:work         # run the Bakong background checker locally
php artisan bakong:check-pending  # run the checker once manually
```

---

## 8. Folder Map (quick reference)

```
app/
├── Console/Commands/CheckPendingBakongPayments.php   # background payment checker
├── Http/
│   ├── Controllers/
│   │   ├── Storefront/  Home, Catalog, Cart, Checkout  # user pages
│   │   ├── Customer/    Auth, Account                  # login + my account
│   │   └── Admin/       Auth, Product(+dashboard), Category, Brand,
│   │                    Order, Customer, Supplier, DeliveryZone
│   └── Middleware/EnsureAdminSession.php               # protects /admin
├── Models/                                             # database tables
└── Services/
    ├── CartService.php       # cart for guest & user, merge on login
    ├── CheckoutService.php   # create order from cart (transaction)
    ├── BakongService.php     # QR generation + payment check + daily budget
    ├── KhqrGenerator.php     # builds the KHQR string
    └── ReceiptPdf.php        # PDF receipts
resources/views/
├── shop/        # home, category, brands, product, cart
├── checkout/    # checkout form + success (QR + polling JS)
├── account/     # login, register, dashboard, orders
├── admin/       # all admin pages
└── receipts/    # PDF receipt template
routes/
├── web.php      # all URLs
└── console.php  # scheduler (bakong:check-pending every 5 min)
```
