# Product Module — CEC Electronic

This document explains how products work in CEC Electronic, for both the **Admin** side (managing products) and the **User / Customer** side (browsing and buying products). The full source code of every product file is included at the end.

---

## 1. Overview

| Side | Who | What they can do |
|------|-----|------------------|
| **Admin** (`/admin/products`) | Staff logged in via `/admin/login` | List, filter by brand, create, edit, delete products, upload images, set price, stock, active or featured status |
| **User** (storefront) | Any visitor or customer | See products on the home page, browse by category or brand, search, filter, open a product page, add it to the cart |

**Main files**

| Layer | File |
|-------|------|
| Model | `app/Models/Product.php` |
| Admin controller | `app/Http/Controllers/Admin/ProductController.php` |
| User controller | `app/Http/Controllers/Storefront/CatalogController.php` |
| Add to cart | `app/Http/Controllers/Storefront/CartController.php` (`store`) |
| Admin views | `resources/views/admin/products/index`, `create`, `edit`, `_form.blade.php` |
| User views | `resources/views/shop/product.blade.php`, `shop/category.blade.php`, `shop/partials/product-card.blade.php` |
| Routes | `routes/web.php` |
| Database | `database/migrations/*` (products table and later column changes) |

> Note: `app/Http/Controllers/ShopController.php` also references products, but no route in `routes/web.php` uses it. It's legacy code, so it isn't included here.

---

## 2. Database: `products` table

Built up across several migrations:

| Column | Type | Added in | Notes |
|--------|------|----------|-------|
| `id` | bigint PK | create_products_table | |
| `category_id` | FK → categories, nullable | create_ecommerce_tables | set to null when the category is deleted |
| `brand_id` | FK → brands, nullable | create_brands_table | set to null when the brand is deleted |
| `supplier_id` | FK → suppliers, nullable | create_supplier_delivery_tables | set to null when the supplier is deleted |
| `name` | string | create_products_table | |
| `slug` | string, unique | create_products_table | auto-generated from the name |
| `sku` | string, unique, nullable | create_ecommerce_tables | |
| `description` | text, nullable | create_products_table | |
| `price` | decimal(10,2) | converted in convert_money_columns_to_decimal | |
| `compare_at_price` | decimal(10,2), nullable | create_ecommerce_tables | the old price, shown crossed out |
| `cost_price` | decimal(10,2), nullable | create_ecommerce_tables | used internally |
| `stock_quantity` | int, default 0 | create_ecommerce_tables | |
| `is_active` | bool, default true | create_ecommerce_tables | hidden from the shop when false |
| `is_featured` | bool, default false | create_ecommerce_tables | |
| `image` | string, nullable | create_products_table | path on the `public` disk (`products/...`) |
| `images`, `specifications` | json, nullable | create_ecommerce_tables | |
| `category` | string, nullable | create_products_table | legacy text category (fallback) |

**Relationships (Product model):** `categoryRelation()` → Category, `brand()` → Brand, `supplier()` → Supplier, `cartItems()`, `orderItems()`.

**Accessors:** `display_category` returns the category name, and `image_url` returns the image URL (or a placeholder).

**Cache:** Every time a product is saved or deleted, the model runs `Cache::flush()`, so storefront pages show admin changes right away.

---

## 3. Admin side

All admin routes use the `EnsureAdminSession` middleware.

### Routes (`Route::resource('products', ...)`)

| Method | URL | Action | Page / Result |
|--------|-----|--------|---------------|
| GET | `/admin` | `dashboard` | Dashboard: product count, low stock (≤ 5), latest products |
| GET | `/admin/products` | `index` | Product list, 10 per page, `?brand=` filter (returns JSON if requested) |
| GET | `/admin/products/create` | `create` | Add Product form |
| POST | `/admin/products` | `store` | Validate, generate slug, upload image, create |
| GET | `/admin/products/{product}` | `show` | Product as JSON |
| GET | `/admin/products/{product}/edit` | `edit` | Edit Product form |
| PUT | `/admin/products/{product}` | `update` | Validate, update slug if the name changed, replace the image |
| DELETE | `/admin/products/{product}` | `destroy` | Delete the product |

### Validation rules

- `name`: required, max 255
- `price`: required, numeric, ≥ 0.01
- `compare_at_price`, `cost_price`: optional, numeric, ≥ 0.01
- `stock_quantity`: optional integer ≥ 0 (defaults to 0)
- `sku`: optional, unique (ignores the current product when updating)
- `category_id` / `brand_id` / `supplier_id`: optional, must exist
- `image`: optional file (jpg, jpeg, png, gif, bmp, webp, svg, ico), max 4 MB
- `is_active` / `is_featured`: checkboxes (default false when unchecked)

### Admin flow

```
Admin login → /admin/products (list)
   ├─ "Add product" → create form → POST store → redirect to list with "Product created."
   ├─ "Edit" → edit form → PUT update → redirect to list with "Product updated."
   └─ "Delete" → DELETE destroy → redirect to list with "Product deleted."
```

- **Slug:** `Str::slug(name)`. If the slug already exists, `-2`, `-3`, and so on are added.
- **Image:** stored at `storage/app/public/products/`. When an image is replaced, the old file is deleted.

---

## 4. User (customer) side

### Routes

| Method | URL | Action | Description |
|--------|-----|--------|-------------|
| GET | `/` | `HomeController` | Home page with categories, brands and products |
| GET | `/category/{slug}` | `CatalogController@category` | Products in a category, with filters |
| GET | `/brands` | `CatalogController@brands` | List of all brands with product counts |
| GET | `/brands/{slug}` | `CatalogController@brand` | Products for one brand |
| GET | `/search?q=` | `CatalogController@search` | Searches name, SKU and description |
| GET | `/product/{slug}` | `CatalogController@product` | Product detail page |
| POST | `/cart/{product}` | `CartController@store` | Add to cart (quantity 1–99, JSON or redirect) |

### Filters (category, brand and search pages)

Query parameters (all arrays): `category[]`, `brand[]`, `processor[]`, `ram[]`, `storage[]`, `price[]`.

- **Processor:** `Intel Core`, `AMD Ryzen`, `Apple M series` (keyword match on name and description)
- **RAM / Storage:** matched as text on name and description (for example `16GB`, `512GB`)
- **Price:** `Under $500`, `$500 - $999`, `$1,000 - $1,499`, `$1,500+`

The shop only shows products where `is_active = true`.

### Caching

Catalog results are cached for **300 seconds**. Each cache key includes a hash of the active filters. `cacheRemember()` checks the shape of each cached value, because the file cache can return corrupted values when two requests write at the same time.

### User flow

```
Home / Category / Brand / Search
      ↓ click product card
/product/{slug}  (detail page)
      ↓ "Add to cart"  (POST /cart/{product})
/cart → /checkout → payment (Bakong KHQR) → order
```

---

## 5. Full source code

### 5.1 `routes/web.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DeliveryZoneController as AdminDeliveryZoneController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Customer\AccountController;
use App\Http\Controllers\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;

Route::get('/', HomeController::class)->name('shop.home');
Route::get('/search', [CatalogController::class, 'search'])->name('shop.search');
Route::get('/brands', [CatalogController::class, 'brands'])->name('shop.brands');
Route::get('/brands/{slug}', [CatalogController::class, 'brand'])->name('shop.brand');
Route::get('/category/{slug}', [CatalogController::class, 'category'])->name('shop.category');
Route::get('/product/{slug}', [CatalogController::class, 'product'])->name('shop.product');

Route::get('/cart', [CartController::class, 'index'])->name('shop.cart');
Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/checkout/regenerate-qr/{order}', [CheckoutController::class, 'regenerateQr'])->name('checkout.regenerate-qr');
Route::get('/checkout/payment-status/{order}', [CheckoutController::class, 'paymentStatus'])->name('checkout.payment-status');

Route::get('/login', [CustomerAuthController::class, 'login'])->name('customer.login');
Route::post('/login', [CustomerAuthController::class, 'authenticate'])
    ->name('customer.login.store')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
Route::get('/register', [CustomerAuthController::class, 'register'])->name('customer.register');
Route::post('/register', [CustomerAuthController::class, 'store'])
    ->name('customer.register.store')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
Route::post('/logout', [CustomerAuthController::class, 'logout'])
    ->name('customer.logout')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

Route::prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AccountController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/receipt', [AccountController::class, 'receipt'])->name('orders.receipt');
    Route::get('/orders/{order}/receipt/view', [AccountController::class, 'viewReceipt'])->name('orders.receipt.view');
});

Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'store'])
    ->name('admin.login.store')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
Route::post('/admin/logout', [AdminAuthController::class, 'destroy'])
    ->name('admin.logout')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdminSession::class)->group(function () {
    Route::get('/', [AdminProductController::class, 'dashboard'])->name('dashboard');
    Route::resource('products', AdminProductController::class);
    Route::resource('categories', AdminCategoryController::class)->except(['show']);
    Route::resource('brands', AdminBrandController::class)->except(['show']);
    Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'update']);
    Route::get('orders/{order}/receipt', [AdminOrderController::class, 'receipt'])->name('orders.receipt');
    Route::post('orders/{order}/verify-payment', [AdminOrderController::class, 'verifyPayment'])->name('orders.verify-payment');
    Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{phone}', [AdminCustomerController::class, 'show'])->name('customers.show');
    Route::resource('suppliers', AdminSupplierController::class)->except(['show']);
    Route::resource('delivery-zones', AdminDeliveryZoneController::class)->except(['show']);
});
```

### 5.2 `app/Models/Product.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Storefront catalog/home pages cache reads under dynamic per-filter
        // keys (see CatalogController::cacheRemember), so there's no single
        // key to target here — flush the whole cache store instead so admin
        // edits show up immediately rather than waiting out the TTL.
        static::saved(fn () => Cache::flush());
        static::deleted(fn () => Cache::flush());
    }

    protected $fillable = [
        'category_id',
        'brand_id',
        'supplier_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'compare_at_price',
        'cost_price',
        'stock_quantity',
        'is_active',
        'is_featured',
        'image',
        'images',
        'specifications',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'specifications' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock_quantity' => 'integer',
        ];
    }

    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getDisplayCategoryAttribute(): string
    {
        return $this->categoryRelation?->name ?: ucfirst((string) $this->category);
    }

    public function getImageUrlAttribute(): string
    {
        if (! $this->image) {
            return asset('images/product-placeholder.svg');
        }

        if (Str::startsWith($this->image, ['http://', 'https://', '/'])) {
            return $this->image;
        }

        if (Str::startsWith($this->image, 'images/')) {
            return asset($this->image);
        }

        // asset() (not Storage::disk('public')->url()) so this resolves against
        // the actual request host, matching how the rest of the app derives URLs
        // instead of depending on APP_URL (see commit a6d3a8b).
        return asset('storage/' . $this->image);
    }
}
```

### 5.3 `app/Http/Controllers/Admin/ProductController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'products' => Product::count(),
            'categories' => Category::count(),
            'orders' => Order::count(),
            'customers' => Order::query()->distinct('customer_phone')->count('customer_phone'),
            'revenue' => Order::sum('grand_total'),
            'payment_notifications' => Order::query()
                ->whereNotNull('payment_confirmed_at')
                ->whereNull('admin_payment_seen_at')
                ->count(),
            'low_stock' => Product::query()->where('stock_quantity', '<=', 5)->count(),
            'value' => Product::sum('price'),
        ];

        $latestProducts = Product::query()->latest()->take(6)->get();
        $latestOrders = Order::query()->latest()->take(6)->get();
        $paymentNotifications = Order::query()
            ->whereNotNull('payment_confirmed_at')
            ->whereNull('admin_payment_seen_at')
            ->latest('payment_confirmed_at')
            ->take(6)
            ->get();

        return view('admin.dashboard', compact('stats', 'latestProducts', 'latestOrders', 'paymentNotifications'));
    }

    public function index(Request $request): View|JsonResponse
    {
        $brands = Brand::query()->orderBy('name')->get(['id', 'name']);
        $selectedBrand = $request->integer('brand') ?: null;

        $products = Product::query()
            ->with(['categoryRelation', 'brand', 'supplier'])
            ->when($selectedBrand, fn ($query) => $query->where('brand_id', $selectedBrand))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($products);
        }

        return view('admin.products.index', compact('products', 'brands', 'selectedBrand'));
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product->load(['categoryRelation', 'supplier']));
    }

    public function create(): View
    {
        $product = new Product();
        $categories = Category::query()->orderBy('name')->get();
        $brands = Brand::query()->orderBy('name')->get();
        $suppliers = Supplier::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.products.create', compact('product', 'categories', 'brands', 'suppliers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $this->storeUploadedImage($request, $data);

        Product::create($data);

        return redirect()->route('admin.products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $categories = Category::query()->orderBy('name')->get();
        $brands = Brand::query()->orderBy('name')->get();
        $suppliers = Supplier::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories', 'brands', 'suppliers'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        $data['slug'] = $product->name === $data['name']
            ? $product->slug
            : $this->uniqueSlug($data['name'], $product->id);
        $this->storeUploadedImage($request, $data, $product);

        $product->update($data);

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0.01'],
            'cost_price' => ['nullable', 'numeric', 'min:0.01'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:120', Rule::unique('products', 'sku')->ignore($product?->id)],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,bmp,webp,svg,ico', 'max:4096'],
            'category' => ['nullable', 'string', 'max:80'],
        ]) + [
            'stock_quantity' => 0,
            'is_active' => false,
            'is_featured' => false,
        ];
    }

    private function storeUploadedImage(Request $request, array &$data, ?Product $product = null): void
    {
        if (! $request->hasFile('image')) {
            unset($data['image']);
            return;
        }

        $path = $request->file('image')->store('products', 'public');
        $data['image'] = $path;

        if ($product?->image && ! Str::startsWith($product->image, ['http://', 'https://', '/'])) {
            Storage::disk('public')->delete($product->image);
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (
            Product::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
```

### 5.4 `app/Http/Controllers/Storefront/CatalogController.php`

```php
<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * How long to cache catalog reads (category/product/brand listings).
     * The DB is geographically far from the app, so caching read-heavy,
     * rarely-changing catalog data avoids paying that round-trip on every view.
     */
    private const CACHE_TTL = 300;

    /**
     * Products don't have dedicated processor/RAM/storage columns, so these
     * facets are matched against the name/description text.
     */
    private const PROCESSOR_KEYWORDS = [
        'Intel Core' => ['intel', 'core i'],
        'AMD Ryzen' => ['ryzen', 'amd'],
        'Apple M series' => ['apple', 'macbook', 'imac', 'm1', 'm2', 'm3', 'm4'],
    ];

    private const PRICE_RANGES = [
        'Under $500' => [null, 499.99],
        '$500 - $999' => [500, 999.99],
        '$1,000 - $1,499' => [1000, 1499.99],
        '$1,500+' => [1500, null],
    ];

    /**
     * The file cache store has no atomic lock between concurrent workers, so
     * two simultaneous requests populating the same cold key can race and
     * corrupt the write. Unlike a locked store, the corruption doesn't always
     * throw — unserialize() can silently return the wrong shape (e.g. a
     * __PHP_Incomplete_Class or a string where an object was expected), which
     * then breaks far downstream in the view. So we validate the shape of
     * whatever comes back and treat anything unexpected as a miss.
     */
    private function cacheRemember(string $key, \Closure $callback, \Closure $isValid): mixed
    {
        try {
            $cached = Cache::get($key);
        } catch (\Throwable) {
            $cached = null;
        }

        if ($cached !== null && $isValid($cached)) {
            return $cached;
        }

        $fresh = $callback();

        try {
            Cache::put($key, $fresh, self::CACHE_TTL);
        } catch (\Throwable) {
            // Best-effort; if the write fails, the next request just recomputes too.
        }

        return $fresh;
    }

    public function category(string $slug, Request $request): View
    {
        $filterKey = $this->filterCacheKey($request);

        ['categoryName' => $categoryName, 'products' => $products] = $this->cacheRemember(
            "catalog.category.{$slug}.{$filterKey}",
            function () use ($slug, $request) {
                $category = Category::where('slug', $slug)->first();
                $categorySlugs = $request->query('category');

                $query = Product::query()->where('is_active', true);

                if ($categorySlugs !== null) {
                    // Checkbox filters were submitted; they fully control which categories show.
                    $query->whereHas('categoryRelation', fn ($q) => $q->whereIn('slug', (array) $categorySlugs));
                } elseif ($category) {
                    $query->where('category_id', $category->id);
                } else {
                    // Legacy fallback for products still using the plain `category` string column.
                    $query->where('category', $slug);
                }

                $this->applyBrandFilter($query, $request);
                $this->applyFacetFilters($query, $request);

                return [
                    'categoryName' => $category?->name ?: ucfirst(str_replace('-', ' ', $slug)),
                    'products' => $query->latest()->get(),
                ];
            },
            fn ($v) => is_array($v) && isset($v['categoryName']) && is_string($v['categoryName'])
                && ($v['products'] ?? null) instanceof Collection
        );

        $categories = $this->activeCategories();
        $brands = $this->activeBrands();
        $selectedCategories = $request->query('category', [$slug]);
        $selectedBrands = (array) $request->query('brand', []);

        return view('shop.category', compact('categoryName', 'products', 'categories', 'brands', 'selectedCategories', 'selectedBrands'));
    }

    public function product(string $slug): View
    {
        $product = $this->cacheRemember(
            "catalog.product.{$slug}",
            fn () => Product::where('slug', $slug)->firstOrFail(),
            fn ($v) => $v instanceof Product
        );

        return view('shop.product', compact('product'));
    }

    public function search(Request $request): View
    {
        $query = trim((string) $request->query('q'));
        $categoryName = $query ? 'Search: ' . $query : 'Search';
        $filterKey = $this->filterCacheKey($request);

        $products = $this->cacheRemember(
            'catalog.search.' . md5($query) . '.' . $filterKey,
            function () use ($query, $request) {
                $builder = Product::query()
                    ->where('is_active', true)
                    ->when($query, function ($builder) use ($query) {
                        $builder->where(function ($inner) use ($query) {
                            $inner->where('name', 'like', "%{$query}%")
                                ->orWhere('sku', 'like', "%{$query}%")
                                ->orWhere('description', 'like', "%{$query}%");
                        });
                    });

                $this->applyCategoryFilter($builder, $request);
                $this->applyBrandFilter($builder, $request);
                $this->applyFacetFilters($builder, $request);

                return $builder->latest()->get();
            },
            fn ($v) => $v instanceof Collection
        );

        $categories = $this->activeCategories();
        $brands = $this->activeBrands();
        $selectedCategories = $request->query('category', []);
        $selectedBrands = (array) $request->query('brand', []);

        return view('shop.category', compact('categoryName', 'products', 'categories', 'brands', 'selectedCategories', 'selectedBrands'));
    }

    public function brands(): View
    {
        $brands = $this->cacheRemember(
            'catalog.brands.list',
            fn () => Brand::query()
                ->active()
                ->ordered()
                ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
                ->get(),
            fn ($v) => $v instanceof Collection
        );

        return view('shop.brands', compact('brands'));
    }

    public function brand(string $slug, Request $request): View
    {
        $filterKey = $this->filterCacheKey($request);

        ['brandName' => $brandName, 'products' => $products] = $this->cacheRemember(
            "catalog.brand.{$slug}.{$filterKey}",
            function () use ($slug, $request) {
                $brand = Brand::query()->active()->where('slug', $slug)->first();

                if (! $brand) {
                    return ['brandName' => null, 'products' => new Collection()];
                }

                $builder = Product::query()->where('is_active', true);

                if ($request->query('brand') !== null) {
                    // Sidebar brand checkboxes were submitted; they fully control which brands show.
                    $this->applyBrandFilter($builder, $request);
                } else {
                    $builder->where('brand_id', $brand->id);
                }

                $this->applyCategoryFilter($builder, $request);
                $this->applyFacetFilters($builder, $request);

                return ['brandName' => $brand->name, 'products' => $builder->latest()->get()];
            },
            fn ($v) => is_array($v) && array_key_exists('brandName', $v)
                && ($v['brandName'] === null || is_string($v['brandName']))
                && ($v['products'] ?? null) instanceof Collection
        );

        abort_if($brandName === null, 404);

        $categoryName = $brandName . ' Products';
        $categories = $this->activeCategories();
        $brands = $this->activeBrands();
        $selectedCategories = $request->query('category', []);
        $selectedBrands = (array) $request->query('brand', [$slug]);

        return view('shop.category', compact('categoryName', 'products', 'categories', 'brands', 'selectedCategories', 'selectedBrands'));
    }

    private function applyCategoryFilter(Builder $query, Request $request): void
    {
        $categorySlugs = (array) $request->query('category', []);

        if ($categorySlugs) {
            $query->whereHas('categoryRelation', fn ($q) => $q->whereIn('slug', $categorySlugs));
        }
    }

    private function applyBrandFilter(Builder $query, Request $request): void
    {
        $brandSlugs = (array) $request->query('brand', []);

        if ($brandSlugs) {
            $query->whereHas('brand', fn ($q) => $q->whereIn('slug', $brandSlugs));
        }
    }

    private function applyFacetFilters(Builder $query, Request $request): void
    {
        $processors = (array) $request->query('processor', []);
        $ramSizes = (array) $request->query('ram', []);
        $storageSizes = (array) $request->query('storage', []);
        $priceRanges = (array) $request->query('price', []);

        if ($processors) {
            $query->where(function ($outer) use ($processors) {
                foreach ($processors as $label) {
                    foreach (self::PROCESSOR_KEYWORDS[$label] ?? [] as $keyword) {
                        $outer->orWhere('name', 'like', "%{$keyword}%")
                            ->orWhere('description', 'like', "%{$keyword}%");
                    }
                }
            });
        }

        if ($ramSizes) {
            $query->where(function ($outer) use ($ramSizes) {
                foreach ($ramSizes as $size) {
                    $outer->orWhere('name', 'like', "%{$size}%")
                        ->orWhere('description', 'like', "%{$size}%");
                }
            });
        }

        if ($storageSizes) {
            $query->where(function ($outer) use ($storageSizes) {
                foreach ($storageSizes as $size) {
                    $outer->orWhere('name', 'like', "%{$size}%")
                        ->orWhere('description', 'like', "%{$size}%");
                }
            });
        }

        if ($priceRanges) {
            $query->where(function ($outer) use ($priceRanges) {
                foreach ($priceRanges as $range) {
                    [$min, $max] = self::PRICE_RANGES[$range] ?? [null, null];
                    $outer->orWhere(function ($bounded) use ($min, $max) {
                        if ($min !== null) {
                            $bounded->where('price', '>=', $min);
                        }
                        if ($max !== null) {
                            $bounded->where('price', '<=', $max);
                        }
                    });
                }
            });
        }
    }

    /**
     * Distinguishes cached results across different filter combinations so
     * one visitor's applied filters can't be served back to another visitor
     * requesting the same category/search/brand with different filters.
     */
    private function filterCacheKey(Request $request): string
    {
        $relevant = collect(['processor', 'ram', 'storage', 'price', 'category', 'brand'])
            ->mapWithKeys(fn ($key) => [
                $key => collect((array) $request->query($key, []))->sort()->values()->all(),
            ])
            ->all();

        return md5(json_encode($relevant));
    }

    private function activeCategories(): Collection
    {
        return $this->cacheRemember(
            'catalog.categories.active',
            fn () => Category::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug']),
            fn ($v) => $v instanceof Collection
        );
    }

    private function activeBrands(): Collection
    {
        return $this->cacheRemember(
            'catalog.brands.active',
            fn () => Brand::query()->active()->ordered()->get(['id', 'name', 'slug']),
            fn ($v) => $v instanceof Collection
        );
    }
}
```

### 5.5 `app/Http/Controllers/Storefront/CartController.php`

```php
<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cartService)
    {
    }

    public function index(Request $request): View
    {
        $items = $this->cartService->items($request);
        $subtotal = $this->cartService->subtotal($request);

        return view('shop.cart', compact('items', 'subtotal'));
    }

    public function store(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $request->merge([
            'quantity' => $this->normalizeQuantity($request->input('quantity', 1), 1),
        ]);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $this->cartService->add($request, $product, $data['quantity'] ?? 1);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Product added to cart.',
                'product' => $product->name,
                'count' => $this->cartService->count($request),
            ]);
        }

        return back()->with('status', 'Product added to cart.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $request->merge([
            'quantity' => $this->normalizeQuantity($request->input('quantity', 1), 0),
        ]);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $this->cartService->updateQuantity($request, $cartItem, $data['quantity']);

        if ($request->wantsJson()) {
            $removed = $data['quantity'] <= 0;

            return response()->json([
                'removed' => $removed,
                'item_id' => $cartItem->id,
                'quantity' => $removed ? 0 : $cartItem->quantity,
                'line_total' => $removed ? null : number_format($cartItem->line_total, 2),
                'subtotal' => number_format($this->cartService->subtotal($request), 2),
                'count' => $this->cartService->count($request),
            ]);
        }

        return back()->with('status', 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $itemId = $cartItem->id;

        $this->cartService->remove($request, $cartItem);

        if ($request->wantsJson()) {
            return response()->json([
                'removed' => true,
                'item_id' => $itemId,
                'subtotal' => number_format($this->cartService->subtotal($request), 2),
                'count' => $this->cartService->count($request),
            ]);
        }

        return back()->with('status', 'Item removed.');
    }

    private function normalizeQuantity(mixed $value, int $minimum): int
    {
        if (! is_numeric($value)) {
            return $minimum;
        }

        return max($minimum, (int) floor((float) $value));
    }
}
```

### 5.6 `app/Http/Controllers/Storefront/HomeController.php`

```php
<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $callback = function () {
            $categories = Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            if ($categories->isEmpty()) {
                $categories = collect([
                    (object) ['slug' => 'laptops', 'name' => 'Laptops'],
                    (object) ['slug' => 'phones', 'name' => 'Phones'],
                    (object) ['slug' => 'accessories', 'name' => 'Accessories'],
                ]);
            }

            return [
                'categories' => $categories,
                'brands' => Brand::query()->active()->ordered()->whereNotNull('logo')->get(),
                'products' => Product::query()
                    ->where('is_active', true)
                    ->latest()
                    ->take(12)
                    ->get(),
            ];
        };

        // The file cache has no atomic lock between concurrent workers: two
        // simultaneous requests populating a cold key can race and corrupt the
        // write. unserialize() doesn't always throw on that corruption — it can
        // silently return the wrong shape — so validate before trusting it.
        try {
            $cached = Cache::get('catalog.home');
        } catch (\Throwable) {
            $cached = null;
        }

        $isValid = fn ($v) => is_array($v)
            && ($v['categories'] ?? null) instanceof Collection
            && ($v['brands'] ?? null) instanceof Collection
            && ($v['products'] ?? null) instanceof Collection;

        if ($cached !== null && $isValid($cached)) {
            $data = $cached;
        } else {
            $data = $callback();

            try {
                Cache::put('catalog.home', $data, 300);
            } catch (\Throwable) {
                // Best-effort; if the write fails, the next request just recomputes too.
            }
        }

        ['categories' => $categories, 'brands' => $brands, 'products' => $products] = $data;

        return view('shop.home', compact('categories', 'brands', 'products'));
    }
}
```

### 5.7 `resources/views/admin/products/index.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Products - CEC Electronic Admin')
@section('heading', 'Products')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Product catalog</h2>
            <p class="muted" style="margin:6px 0 0">Manage names, categories, prices, images, and specs shown on the storefront.</p>
        </div>
        <a class="btn" href="{{ route('admin.products.create') }}">Add product</a>
    </div>

    <form class="toolbar" method="get" action="{{ route('admin.products.index') }}" style="justify-content:flex-start;gap:10px">
        <label for="brand-filter" class="muted">Brand</label>
        <select id="brand-filter" name="brand" onchange="this.form.submit()">
            <option value="">All brands</option>
            @foreach($brands as $brand)
                <option value="{{ $brand->id }}" @selected($selectedBrand === $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
        @if($selectedBrand)
            <a class="btn secondary" href="{{ route('admin.products.index') }}">Clear</a>
        @endif
        <noscript><button class="btn" type="submit">Filter</button></noscript>
    </form>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Supplier</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <div class="product-cell">
                                <img class="thumb" src="{{ $product->image_url }}" alt="{{ $product->name }}">
                                <div>
                                    <strong>{{ $product->name }}</strong>
                                    <div class="muted">{{ $product->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $product->display_category ?: 'Uncategorized' }}</td>
                        <td>{{ $product->brand?->name ?: 'No brand' }}</td>
                        <td>{{ $product->supplier?->name ?: 'No supplier' }}</td>
                        <td>${{ number_format($product->price, 2) }}</td>
                        <td><span class="status">In stock</span></td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="{{ route('shop.product', $product->slug) }}">View</a>
                                <a class="btn secondary" href="{{ route('admin.products.edit', $product) }}">Edit</a>
                                <form action="{{ route('admin.products.destroy', $product) }}" method="post" onsubmit="return confirm('Delete this product?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $products->links() }}
    </div>
@endsection
```

### 5.8 `resources/views/admin/products/create.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Add Product - CEC Electronic Admin')
@section('heading', 'Add Product')

@section('content')
    <form class="panel form" action="{{ route('admin.products.store') }}" method="post" enctype="multipart/form-data">
        @include('admin.products._form', ['buttonText' => 'Create product'])
    </form>
@endsection
```

### 5.9 `resources/views/admin/products/edit.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Edit Product - CEC Electronic Admin')
@section('heading', 'Edit Product')

@section('content')
    <form class="panel form" action="{{ route('admin.products.update', $product) }}" method="post" enctype="multipart/form-data">
        @method('PUT')
        @include('admin.products._form', ['buttonText' => 'Save changes'])
    </form>
@endsection
```

### 5.10 `resources/views/admin/products/_form.blade.php`

```blade
@csrf

<div class="form-grid">
    <div class="field full">
        <label for="name">Product name</label>
        <input id="name" name="name" value="{{ old('name', $product->name) }}" required>
        @error('name') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="category">Category</label>
        <select id="category" name="category_id">
            <option value="">Uncategorized</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="brand_id">Brand</label>
        <select id="brand_id" name="brand_id">
            <option value="">No brand</option>
            @foreach($brands as $brand)
                <option value="{{ $brand->id }}" @selected((int) old('brand_id', $product->brand_id) === $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
        @error('brand_id') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="price">Price USD</label>
        <input id="price" name="price" type="number" min="0.01" step="0.01" value="{{ old('price', $product->price ?? 0) }}" required>
        @error('price') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="supplier_id">Supplier</label>
        <select id="supplier_id" name="supplier_id">
            <option value="">No supplier</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((int) old('supplier_id', $product->supplier_id) === $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
        @error('supplier_id') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="sku">SKU</label>
        <input id="sku" name="sku" value="{{ old('sku', $product->sku) }}">
        @error('sku') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="stock_quantity">Stock quantity</label>
        <input id="stock_quantity" name="stock_quantity" type="number" min="0" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}">
        @error('stock_quantity') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="compare_at_price">Compare at price</label>
        <input id="compare_at_price" name="compare_at_price" type="number" min="0.01" step="0.01" value="{{ old('compare_at_price', $product->compare_at_price) }}">
        @error('compare_at_price') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="cost_price">Cost price</label>
        <input id="cost_price" name="cost_price" type="number" min="0.01" step="0.01" value="{{ old('cost_price', $product->cost_price) }}">
        @error('cost_price') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field full">
        <label for="image">Product image</label>
        @if($product->image)
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
                <img class="thumb" src="{{ $product->image_url }}" alt="{{ $product->name ?: 'Product image' }}">
                <span class="muted">Upload a new image or icon file to replace this image.</span>
            </div>
        @endif
        <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.gif,.bmp,.webp,.svg,.ico,image/*">
        <span class="muted" style="display:block;margin-top:6px">Supports JPG, PNG, GIF, BMP, WEBP, SVG, and ICO up to 4MB.</span>
        @error('image') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field full">
        <label for="description">Description and specs</label>
        <textarea id="description" name="description">{{ old('description', $product->description) }}</textarea>
        @error('description') <span class="error">{{ $message }}</span> @enderror
    </div>

    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))> Active</label>
    <label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured ?? false))> Featured</label>
</div>

<div style="display:flex;gap:10px;margin-top:18px">
    <button class="btn" type="submit">{{ $buttonText }}</button>
    <a class="btn secondary" href="{{ route('admin.products.index') }}">Cancel</a>
</div>
```

### 5.11 `resources/views/shop/product.blade.php`

```blade
@extends('shop.layout')

@section('title', $product->name.' - CEC Electronic')

@section('content')
    @php
        $image = $product->image_url;
        $oldPrice = $product->compare_at_price && $product->compare_at_price > $product->price ? $product->compare_at_price : null;
        $sku = $product->sku ?: strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $product->slug), 0, 3)) . '-' . str_pad((string) $product->id, 4, '0', STR_PAD_LEFT);
        $specs = $product->specifications ?: [
            'Warranty' => 'Official store warranty',
            'Delivery' => 'Same-day Phnom Penh option',
            'Support' => 'CEC Electronic service desk',
        ];
    @endphp

    <section class="detail">
        <div class="panel detail-media">
            <img src="{{ $image }}" alt="{{ $product->name }}">
        </div>

        <aside class="panel detail-info">
            <div class="sku">{{ $sku }}</div>
            <h1>{{ $product->name }}</h1>
            <div class="stock">{{ $product->stock_quantity > 0 ? 'In stock: '.$product->stock_quantity : 'Pre-order available' }}</div>

            <div style="margin:18px 0">
                <span class="price">${{ number_format($product->price, 2) }}</span>
                @if($oldPrice)
                    <span class="old-price">${{ number_format($oldPrice, 2) }}</span>
                @endif
            </div>

            <p style="color:var(--muted);line-height:1.7">{{ $product->description ?: 'High-quality electronics product with official warranty and dependable after-sales support.' }}</p>

            <div class="spec-table">
                @foreach($specs as $label => $value)
                    <div class="spec-row">
                        <span>{{ $label }}</span>
                        <strong>{{ is_array($value) ? implode(', ', $value) : $value }}</strong>
                    </div>
                @endforeach
            </div>

            <div class="panel" style="padding:14px;margin:18px 0;background:#f8fbff">
                <strong>CEC store services</strong>
                <div class="checks" style="margin-top:10px">
                    <span>Same-day delivery in selected Phnom Penh areas</span>
                    <span>Official warranty support</span>
                    <span>Repair tracking and warranty check</span>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <form action="{{ route('cart.store', $product) }}" method="post" data-cart-add>
                    @csrf
                    <button class="btn" style="width:100%" type="submit">Add to cart</button>
                </form>
                <a class="btn secondary" href="{{ route('shop.cart') }}">View cart</a>
            </div>
        </aside>
    </section>
@endsection
```

### 5.12 `resources/views/shop/partials/product-card.blade.php`

```blade
@php
    $image = $p->image_url;
    $oldPrice = $p->compare_at_price && $p->compare_at_price > $p->price ? $p->compare_at_price : null;
    $sku = $p->sku ?: strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $p->slug), 0, 3)) . '-' . str_pad((string) $p->id, 4, '0', STR_PAD_LEFT);
    $stockText = $p->stock_quantity > 0 ? 'In stock: '.$p->stock_quantity : 'Pre-order';
@endphp

<article class="product-card">
    <a class="product-media" href="{{ route('shop.product', $p->slug) }}">
        @if($oldPrice)
            <span class="badge">Save ${{ number_format($oldPrice - $p->price, 2) }}</span>
        @endif
        <img src="{{ $image }}" alt="{{ $p->name }}">
    </a>
    <div class="product-body">
        <div class="card-meta">
            <div class="sku">{{ $sku }}</div>
            <div class="stock">{{ $stockText }}</div>
        </div>
        <a class="product-title" href="{{ route('shop.product', $p->slug) }}">{{ $p->name }}</a>
        <p class="spec">{{ $p->description ?: 'Fast processor, bright display, reliable storage, and official warranty for work, study, and entertainment.' }}</p>
        <div>
            <span class="price">${{ number_format($p->price, 2) }}</span>
            @if($oldPrice)
                <span class="old-price">${{ number_format($oldPrice, 2) }}</span>
            @endif
        </div>
        <form class="card-actions" action="{{ route('cart.store', $p) }}" method="post" data-cart-add>
            @csrf
            <button class="btn" type="submit">Add to cart</button>
            <a class="icon-btn" href="{{ route('shop.product', $p->slug) }}" aria-label="View {{ $p->name }}" style="display:grid;place-items:center">i</a>
        </form>
    </div>
</article>
```

### 5.13 `resources/views/shop/category.blade.php`

```blade
@extends('shop.layout')

@section('title', $categoryName.' - CEC Electronic')

@section('content')
    @php
        $filterGroups = [
            'processor' => ['label' => 'Processor', 'options' => ['Intel Core','AMD Ryzen','Apple M series']],
            'ram' => ['label' => 'RAM Size', 'options' => ['8GB','16GB','32GB','64GB']],
            'storage' => ['label' => 'Storage', 'options' => ['256GB SSD','512GB SSD','1TB SSD','2TB SSD']],
        ];
        $priceRanges = ['Under $500', '$500 - $999', '$1,000 - $1,499', '$1,500+'];
        $selected = [
            'processor' => (array) request()->query('processor', []),
            'ram' => (array) request()->query('ram', []),
            'storage' => (array) request()->query('storage', []),
            'price' => (array) request()->query('price', []),
            'category' => (array) ($selectedCategories ?? []),
            'brand' => (array) ($selectedBrands ?? []),
        ];
    @endphp

    <div class="section-head">
        <div>
            <h2>{{ $categoryName }}</h2>
            <p>Compare CEC Electronic models, prices, stock, and core specs in one catalog view.</p>
        </div>
        <a class="btn secondary" href="/">Back home</a>
    </div>

    <div class="brand-strip">
        <a class="brand-pill" href="{{ route('shop.brands') }}">All Brands</a>
        @foreach($brands as $brand)
            <a class="brand-pill" href="{{ route('shop.brand', $brand->slug) }}">{{ $brand->name }}</a>
        @endforeach
    </div>

    <section class="catalog">
        <aside class="panel filter">
            <h3>Filters</h3>
            <form method="GET" action="{{ url()->current() }}">
                @if(request()->query('q'))
                    <input type="hidden" name="q" value="{{ request()->query('q') }}">
                @endif
                <div class="filter-group">
                    <div class="filter-title"><span>Categories</span><span>-</span></div>
                    <div class="checks">
                        @forelse($categories ?? [] as $cat)
                            <label>
                                <input type="checkbox" name="category[]" value="{{ $cat->slug }}"
                                    @checked(in_array($cat->slug, $selected['category']))> {{ $cat->name }}
                            </label>
                        @empty
                            <label><input type="checkbox" checked disabled> {{ $categoryName }}</label>
                        @endforelse
                    </div>
                </div>
                <div class="filter-group">
                    <div class="filter-title"><span>Brand</span><span>-</span></div>
                    <div class="checks">
                        @foreach($brands ?? [] as $brand)
                            <label>
                                <input type="checkbox" name="brand[]" value="{{ $brand->slug }}"
                                    @checked(in_array($brand->slug, $selected['brand']))> {{ $brand->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="filter-group">
                    <div class="filter-title"><span>Price range</span><span>-</span></div>
                    <div class="checks">
                        @foreach($priceRanges as $range)
                            <label>
                                <input type="checkbox" name="price[]" value="{{ $range }}"
                                    @checked(in_array($range, $selected['price']))> {{ $range }}
                            </label>
                        @endforeach
                    </div>
                </div>
                @foreach($filterGroups as $param => $group)
                    <div class="filter-group">
                        <div class="filter-title"><span>{{ $group['label'] }}</span><span>-</span></div>
                        <div class="checks">
                            @foreach($group['options'] as $option)
                                <label>
                                    <input type="checkbox" name="{{ $param }}[]" value="{{ $option }}"
                                        @checked(in_array($option, $selected[$param]))> {{ $option }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:8px">
                    <a class="btn secondary" href="{{ url()->current() }}{{ request()->query('q') ? '?q=' . urlencode(request()->query('q')) : '' }}">Clear</a>
                    <button class="btn" type="submit">Apply</button>
                </div>
            </form>
        </aside>

        <div>
            <div class="panel toolbar">
                <strong>{{ $products->count() }} products</strong>
                <label>
                    Sort by
                    <select>
                        <option>Newest</option>
                        <option>Oldest</option>
                        <option>Price: low to high</option>
                        <option>Price: high to low</option>
                    </select>
                </label>
            </div>

            @if($products->isEmpty())
                <div class="panel" style="padding:30px;text-align:center;color:var(--muted)">
                    No products found in this category.
                </div>
            @else
                <div class="grid">
                    @foreach($products as $p)
                        @include('shop.partials.product-card', ['p' => $p])
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
```

### 5.14 `database/migrations/0001_01_02_000000_create_products_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->string('image')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

### 5.15 `database/migrations/0001_01_03_000000_create_ecommerce_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('sku')->nullable()->unique()->after('slug');
            $table->unsignedInteger('compare_at_price')->nullable()->after('price');
            $table->unsignedInteger('cost_price')->nullable()->after('compare_at_price');
            $table->integer('stock_quantity')->default(0)->after('cost_price');
            $table->boolean('is_active')->default(true)->after('stock_quantity');
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->json('images')->nullable()->after('image');
            $table->json('specifications')->nullable()->after('images');
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('Default');
            $table->string('recipient_name');
            $table->string('phone');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Cambodia');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price');
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
            $table->unique(['session_id', 'product_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone');
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_method')->nullable();
            $table->string('shipping_method')->nullable();
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('shipping_total')->default(0);
            $table->unsignedInteger('discount_total')->default(0);
            $table->unsignedInteger('grand_total')->default(0);
            $table->json('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('line_total');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('customer_addresses');

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn([
                'sku',
                'compare_at_price',
                'cost_price',
                'stock_quantity',
                'is_active',
                'is_featured',
                'images',
                'specifications',
            ]);
        });

        Schema::dropIfExists('categories');
    }
};
```

### 5.16 `database/migrations/0001_01_04_000000_create_supplier_delivery_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('payment_terms')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('grand_total')->default(0);
            $table->date('expected_date')->nullable();
            $table->date('received_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_cost');
            $table->unsignedInteger('line_total');
            $table->timestamps();
        });

        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->unsignedInteger('free_delivery_minimum')->nullable();
            $table->unsignedInteger('estimated_days')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('delivery_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tracking_url')->nullable();
            $table->unsignedInteger('base_fee')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_zone_id')->nullable()->after('shipping_method')->constrained()->nullOnDelete();
            $table->foreignId('delivery_provider_id')->nullable()->after('delivery_zone_id')->constrained()->nullOnDelete();
            $table->string('tracking_number')->nullable()->after('delivery_provider_id');
            $table->timestamp('shipped_at')->nullable()->after('tracking_number');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tracking_number')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_zone_id');
            $table->dropConstrainedForeignId('delivery_provider_id');
            $table->dropColumn(['tracking_number', 'shipped_at', 'delivered_at']);
        });

        Schema::dropIfExists('delivery_providers');
        Schema::dropIfExists('delivery_zones');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });

        Schema::dropIfExists('suppliers');
    }
};
```

### 5.17 `database/migrations/2026_09_19_000001_create_brands_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Brands that previously lived in config/brands.php.
     */
    private const INITIAL_BRANDS = [
        ['Acer', 'acer', 'images/Brand/acer-logo.jpg'],
        ['AOC', 'aoc', 'images/Brand/aoc-logo.png'],
        ['Apple', 'apple', 'images/Brand/apple-logo.png'],
        ['Asus', 'asus', 'images/Brand/asus-logo.png'],
        ['Cabletime', 'cabletime', 'images/Brand/CABLETIME.png'],
        ['Dell', 'dell', 'images/Brand/Dell.png'],
        ['Epson', 'epson', 'images/Brand/EPSON.png'],
        ['HikVISION', 'hikvision', 'images/Brand/HikVISION.png'],
        ['Huawei', 'huawei', 'images/Brand/2148933-3840x2160-desktop-4k-huawei-logo-wallpaper.jpg'],
        ['Lenovo', 'lenovo', 'images/Brand/lenovo-logo.png'],
        ['Logitech', 'logitech', 'images/Brand/logitech-logo.png'],
        ['MSI', 'msi', 'images/Brand/msi.png'],
        ['NEC', 'nec', 'images/Brand/NEC.png'],
        ['PROLINK', 'prolink', 'images/Brand/prolink-logo.jpeg'],
        ['Rongta', 'rongta', 'images/Brand/rongta-logo.png'],
        ['Samsung', 'samsung', 'images/Brand/samsung-logo.png'],
        ['SanDisk', 'sandisk', 'images/Brand/sandisk-logo.png'],
        ['Transcend', 'transcend', 'images/Brand/transcend-logo.jpeg'],
    ];

    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('category_id')
                ->constrained('brands')->nullOnDelete();
        });

        $now = now();

        foreach (self::INITIAL_BRANDS as $index => [$name, $slug, $logo]) {
            $brandId = DB::table('brands')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'logo' => $logo,
                'is_active' => true,
                'sort_order' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Backfill from the product name only. The old storefront also matched
            // descriptions, but that mislabels accessories that merely mention a
            // brand (e.g. "charger for Dell laptops"). Admins can adjust the rest.
            DB::table('products')
                ->whereNull('brand_id')
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($name) . '%'])
                ->update(['brand_id' => $brandId]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
        });

        Schema::dropIfExists('brands');
    }
};
```

### 5.18 `database/migrations/2026_09_04_000001_convert_money_columns_to_decimal.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->change();
            $table->decimal('compare_at_price', 10, 2)->nullable()->change();
            $table->decimal('cost_price', 10, 2)->nullable()->change();
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->change();
            $table->decimal('line_total', 10, 2)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->change();
            $table->decimal('shipping_total', 10, 2)->default(0)->change();
            $table->decimal('discount_total', 10, 2)->default(0)->change();
            $table->decimal('grand_total', 10, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('price')->default(0)->change();
            $table->unsignedInteger('compare_at_price')->nullable()->change();
            $table->unsignedInteger('cost_price')->nullable()->change();
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unsignedInteger('unit_price')->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('unit_price')->change();
            $table->unsignedInteger('line_total')->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('subtotal')->default(0)->change();
            $table->unsignedInteger('shipping_total')->default(0)->change();
            $table->unsignedInteger('discount_total')->default(0)->change();
            $table->unsignedInteger('grand_total')->default(0)->change();
        });
    }
};
```
