# Admin Front — CEC Electronic

How the admin panel works. Each section covers one feature and contains all of that feature's code.

| # | Feature | URL |
|---|---------|-----|
| 1 | Admin Login & Security | `/admin/login` |
| 2 | Admin Layout | (all admin pages) |
| 3 | Dashboard | `/admin` |
| 4 | Product | `/admin/products` |
| 5 | Category | `/admin/categories` |
| 6 | Brand | `/admin/brands` |
| 7 | Supplier | `/admin/suppliers` |
| 8 | Order & Payment | `/admin/orders` |
| 9 | Customer | `/admin/customers` |
| 10 | Delivery Zone | `/admin/delivery-zones` |

---

## 1. Admin Login & Security

- The admin account is created by `php artisan db:seed` using `ADMIN_EMAIL` and `ADMIN_PASSWORD` from `.env` (`users.is_admin = true`).
- Login succeeds only if the password is correct **and** `is_admin` is true. It then sets `session(admin_authenticated) = true`.
- The `EnsureAdminSession` middleware protects every `/admin/*` page. A normal customer cannot open the admin panel.

**Routes** (`routes/web.php`)

```php
Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'store'])
    ->name('admin.login.store')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
Route::post('/admin/logout', [AdminAuthController::class, 'destroy'])
    ->name('admin.logout')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
```

### 1.1 `app/Http/Middleware/EnsureAdminSession.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() || ! Auth::user()?->is_admin || ! (bool) $request->session()->get('admin_authenticated', false)) {
            return redirect()->guest(route('admin.login'));
        }

        return $next($request);
    }
}
```

### 1.2 `app/Http/Controllers/Admin/AuthController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], false) || ! Auth::user()?->is_admin) {
            Auth::logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The admin email or password is incorrect.',
                    'errors' => ['email' => ['The admin email or password is incorrect.']],
                ], 422);
            }

            return back()
                ->withErrors(['email' => 'The admin email or password is incorrect.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('admin_authenticated', true);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged in successfully.',
                'redirect' => route('admin.dashboard'),
            ]);
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $request->session()->forget('admin_authenticated');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged out.',
                'redirect' => route('admin.login'),
            ]);
        }

        return redirect()->route('admin.login');
    }
}
```

### 1.3 `resources/views/admin/auth/login.blade.php`

```blade
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login - CEC Electronic</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f3f6fb;color:#121926;font-family:Inter,Segoe UI,Arial,sans-serif}
        .login{width:min(420px,calc(100vw - 32px));background:#fff;border:1px solid #dde5f0;border-radius:8px;padding:24px;box-shadow:0 18px 45px rgba(16,24,40,.1)}
        .brand{display:flex;align-items:center;gap:12px;margin-bottom:18px}
        .mark{width:52px;height:52px;border-radius:8px;background:#fff;border:1px solid #dde5f0;display:grid;place-items:center;overflow:hidden}
        .mark img{width:100%;height:100%;object-fit:contain;padding:3px}
        h1{font-size:24px;margin:0}
        p{color:#667085;margin:6px 0 0;line-height:1.5}
        label{display:grid;gap:8px;font-weight:800;margin:18px 0}
        input{border:1px solid #dde5f0;border-radius:7px;padding:12px;font:inherit}
        button,a{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:7px;padding:11px 14px;font:inherit;font-weight:850;text-decoration:none}
        button{width:100%;background:#0057a8;color:#fff;cursor:pointer}
        a{margin-top:10px;width:100%;background:#eef5ff;color:#0057a8}
        .error{color:#d92d20;font-size:13px;margin-top:-8px;margin-bottom:12px}
    </style>
</head>
<body>
    <form class="login" action="{{ route('admin.login.store') }}" method="post">
        @csrf
        <div class="brand">
            <span class="mark"><img src="{{ asset('images/brand-logo.jpg') }}" alt="CEC Electronic logo"></span>
            <div>
                <h1>Admin Login</h1>
                <p>Control products, customers, orders, delivery, and suppliers.</p>
            </div>
        </div>

        <label>
            Admin email
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </label>
        @error('email')
            <div class="error">{{ $message }}</div>
        @enderror

        <label>
            Admin password
            <input type="password" name="password" required>
        </label>
        @error('password')
            <div class="error">{{ $message }}</div>
        @enderror

        <button type="submit">Login to admin panel</button>
        <a href="{{ route('shop.home') }}">Back to store</a>
    </form>
</body>
</html>
```

### 1.4 `database/seeders/DatabaseSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => env('ADMIN_EMAIL', 'admin@cecelectronic.co')], [
            'name' => 'CEC Admin',
            'password' => env('ADMIN_PASSWORD', 'change-this-password'),
            'is_admin' => true,
        ]);

        // Seed products
        $this->call([
            ProductSeeder::class,
            SupplierDeliverySeeder::class,
        ]);
    }
}
```

### 1.5 `database/migrations/2026_05_20_000002_add_is_admin_to_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
```

---

## 2. Admin Layout

- Shared by all admin pages: sidebar menu, top bar with logout, flash messages and the loading overlay.

### 2.1 `resources/views/admin/layout.blade.php`

```blade
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Admin - CEC Electronic')</title>
    <link rel="icon" href="{{ asset('images/brand-logo.jpg') }}" type="image/jpeg">
    <style>
        :root{
            --brand:#004b93;
            --brand-dark:#06366f;
            --ink:#172033;
            --muted:#667085;
            --line:#e5e9f2;
            --soft:#f4f7fb;
            --panel:#fff;
            --danger:#dc2626;
            --warning:#f6b300;
        }
        *{box-sizing:border-box}
        body{margin:0;background:var(--soft);color:var(--ink);font-family:Inter,Segoe UI,Arial,sans-serif;font-size:14px}
        a{color:inherit;text-decoration:none}
        button,input,textarea,select{font:inherit}
        .app{display:grid;grid-template-columns:250px minmax(0,1fr);min-height:100vh}
        .sidebar{background:linear-gradient(180deg,#0b315f,#082448);color:#d9e7f7;padding:22px 16px;position:sticky;top:0;height:100vh;overflow:auto}
        .brand{display:flex;align-items:center;gap:11px;color:#fff;margin-bottom:22px;padding-bottom:20px;border-bottom:1px solid rgba(255,255,255,.12)}
        .brand-mark{width:42px;height:42px;border-radius:9px;background:#fff;color:var(--brand);display:grid;place-items:center;overflow:hidden;flex:0 0 auto}
        .brand-mark img{width:100%;height:100%;object-fit:contain;padding:3px}
        .brand-text{display:flex;flex-direction:column;line-height:1.35;min-width:0}
        .brand-text strong{font-size:16px;font-weight:900}
        .brand-text span{font-size:10.5px;color:#9fc0e3;font-weight:800;text-transform:uppercase;letter-spacing:.05em}
        .nav{display:grid;gap:4px}
        .nav-label{font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;color:#7ea6cf;font-weight:800;padding:14px 12px 6px}
        .nav a{padding:10px 12px;border-radius:7px;font-weight:700;display:flex;align-items:center;gap:11px;border-left:3px solid transparent;color:#c9defa}
        .nav a .nav-icon{width:18px;text-align:center;font-size:15px;flex:0 0 auto}
        .nav a:hover{background:rgba(255,255,255,.08);color:#fff}
        .nav a.active{background:rgba(255,255,255,.14);color:#fff;border-left-color:var(--warning)}
        .main{min-width:0}
        .top{height:70px;background:#fff;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:10;box-shadow:0 2px 10px rgba(16,24,40,.03)}
        .top h1{font-size:22px;margin:0}
        .content{padding:24px}
        .panel{background:var(--panel);border:1px solid var(--line);border-radius:10px;box-shadow:0 1px 3px rgba(16,24,40,.04)}
        .btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:7px;padding:10px 14px;background:var(--brand);color:#fff;font-weight:800;cursor:pointer;transition:transform .12s ease,box-shadow .12s ease}
        .btn:hover{transform:translateY(-1px);box-shadow:0 10px 18px rgba(0,75,147,.22)}
        .btn.secondary{background:#eef5ff;color:var(--brand)}
        .btn.secondary:hover{box-shadow:0 10px 18px rgba(0,75,147,.1)}
        .btn.danger{background:var(--danger)}
        .btn.danger:hover{box-shadow:0 10px 18px rgba(220,38,38,.22)}
        .admin-chip{display:flex;align-items:center;gap:9px;padding:6px 12px 6px 6px;border:1px solid var(--line);border-radius:999px;background:#f8fbff;margin-right:4px}
        .admin-avatar{width:30px;height:30px;border-radius:50%;background:var(--brand);color:#fff;display:grid;place-items:center;font-weight:900;font-size:13px;flex:0 0 auto}
        .admin-chip-text{display:flex;flex-direction:column;line-height:1.3}
        .admin-chip-text strong{font-size:12.5px}
        .admin-chip-text span{font-size:10.5px;color:var(--muted)}
        .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}
        .stat{padding:18px;display:flex;align-items:center;gap:14px;transition:transform .15s ease,box-shadow .15s ease}
        .stat:hover{transform:translateY(-2px);box-shadow:0 14px 28px rgba(16,24,40,.08)}
        .stat-icon{width:46px;height:46px;border-radius:10px;display:grid;place-items:center;font-size:20px;flex:0 0 auto}
        .stat-icon.blue{background:#eef5ff;color:#004b93}
        .stat-icon.green{background:#e7f8ef;color:#087443}
        .stat-icon.amber{background:#fff3cf;color:#8a5a00}
        .stat-icon.purple{background:#f3ecff;color:#6d28d9}
        .stat-icon.red{background:#fee4e2;color:#b42318}
        .stat-icon.cyan{background:#e6f8fb;color:#0e7490}
        .stat-body{min-width:0}
        .stat-body span{color:var(--muted);display:block;margin-bottom:4px;font-size:12.5px}
        .stat-body strong{font-size:24px;display:block;line-height:1.1}
        .toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
        table{width:100%;border-collapse:collapse;background:#fff;border:1px solid var(--line);border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(16,24,40,.04)}
        th,td{padding:13px 14px;border-bottom:1px solid var(--line);text-align:left;vertical-align:middle}
        th{background:#f8fbff;color:#344054;font-size:12px;text-transform:uppercase;letter-spacing:.04em}
        tbody tr{transition:background .12s ease}
        tbody tr:hover{background:#f8fbff}
        tr:last-child td{border-bottom:0}
        .product-cell{display:flex;align-items:center;gap:12px}
        .thumb{width:58px;height:46px;border-radius:6px;background:#edf2f7;object-fit:cover}
        .muted{color:var(--muted)}
        .status{display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:999px;background:#e7f8ef;color:#087443;font-size:12px;font-weight:800}
        .status.warning{background:#fff3cf;color:#8a5a00}
        .status.orange{background:#ffead5;color:#c2410c}
        .status.danger{background:#fee4e2;color:#b42318}
        .status.info{background:#eef5ff;color:#004b93}
        .status.unread{background:#fff3cf;color:#8a5a00}
        .status-text{font-weight:800}
        .status-text.success{color:#087443}
        .status-text.danger{color:#b42318}
        .status-text.info{color:#004b93}
        .actions{display:flex;gap:8px;justify-content:flex-end}
        .split{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px}
        .mini-list{display:grid;gap:10px;padding:16px}
        .mini-item{display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--line);padding-bottom:10px}
        .mini-item:last-child{border-bottom:0;padding-bottom:0}
        a.mini-item{border-radius:8px;padding:10px;margin:0 -10px;border-bottom:1px solid var(--line);transition:background .12s ease}
        a.mini-item:last-child{border-bottom:0}
        a.mini-item:hover{background:#f8fbff}
        .notice{display:flex;align-items:center;gap:10px;padding:12px 14px;background:#e7f8ef;color:#087443;border:1px solid #b7ebc9;border-radius:8px;margin-bottom:14px;font-weight:700}
        .notice-icon{width:22px;height:22px;border-radius:50%;background:#087443;color:#fff;display:grid;place-items:center;font-size:12px;flex:0 0 auto}
        .payment-verified-banner{display:flex;align-items:center;gap:12px;padding:14px;margin-bottom:16px;border-radius:9px;background:linear-gradient(135deg,#e7f8ef,#dcf5e8);border:1px solid #b7ebc9;color:#065f38}
        .payment-verified-banner .icon{width:36px;height:36px;border-radius:50%;background:#087443;color:#fff;display:grid;place-items:center;font-size:17px;flex:0 0 auto}
        .payment-verified-banner strong{display:block;font-size:13.5px}
        .payment-verified-banner span{font-size:12px;color:#0d7a4f}
        .form{max-width:840px;padding:20px}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .field{display:grid;gap:7px}
        .field.full{grid-column:1 / -1}
        label{font-weight:800}
        input,textarea,select{border:1px solid var(--line);border-radius:7px;padding:11px 12px;background:#fff;color:var(--ink);width:100%}
        textarea{min-height:150px;resize:vertical}
        .error{color:var(--danger);font-size:12px}
        .pagination{margin-top:16px}
        .pager{display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap}
        .pager-nav{padding:9px 16px}
        .pager-nav.disabled{opacity:.45;cursor:not-allowed;pointer-events:none}
        .pager-pages{display:flex;align-items:center;gap:4px;flex-wrap:wrap}
        .pager-page{min-width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:7px;padding:0 6px;color:var(--ink);font-weight:700}
        .pager-page:hover{background:#eef5ff;color:var(--brand)}
        .pager-page.active{background:var(--brand);color:#fff}
        .pager-dots{min-width:24px;text-align:center;color:var(--muted)}
        .pager-summary{text-align:center;color:var(--muted);font-size:12.5px;margin-top:8px}
        @media (max-width:900px){
            .app{grid-template-columns:1fr}
            .sidebar{position:static;height:auto}
            .stats,.form-grid,.split{grid-template-columns:1fr}
            .top{position:static}
            .content{padding:16px}
            table{display:block;overflow:auto}
        }
    </style>
</head>
<body>
    <div class="app">
        <aside class="sidebar">
            <a class="brand" href="{{ route('admin.dashboard') }}">
                <span class="brand-mark"><img src="{{ asset('images/brand-logo.jpg') }}" alt="CEC Electronic logo"></span>
                <span class="brand-text">
                    <strong>CEC Electronic</strong>
                    <span>Admin Panel</span>
                </span>
            </a>
            <nav class="nav">
                <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])><span class="nav-icon">&#128202;</span>Dashboard</a>
                <a href="{{ route('admin.products.index') }}" @class(['active' => request()->routeIs('admin.products.*')])><span class="nav-icon">&#128421;</span>Products</a>
                <a href="{{ route('admin.categories.index') }}" @class(['active' => request()->routeIs('admin.categories.*')])><span class="nav-icon">&#128194;</span>Categories</a>
                <a href="{{ route('admin.brands.index') }}" @class(['active' => request()->routeIs('admin.brands.*')])><span class="nav-icon">&#127991;</span>Brands</a>
                <a href="{{ route('admin.suppliers.index') }}" @class(['active' => request()->routeIs('admin.suppliers.*')])><span class="nav-icon">&#128666;</span>Suppliers</a>
                <a href="{{ route('admin.orders.index') }}" @class(['active' => request()->routeIs('admin.orders.*')])><span class="nav-icon">&#129534;</span>Orders</a>
                <a href="{{ route('admin.customers.index') }}" @class(['active' => request()->routeIs('admin.customers.*')])><span class="nav-icon">&#128101;</span>Customers</a>
                <a href="{{ route('admin.delivery-zones.index') }}" @class(['active' => request()->routeIs('admin.delivery-zones.*')])><span class="nav-icon">&#128230;</span>Delivery</a>
                <span class="nav-label">Storefront</span>
                <a href="/"><span class="nav-icon">&#127978;</span>View Store</a>
            </nav>
        </aside>

        <div class="main">
            <header class="top">
                <h1>@yield('heading', 'Admin Panel')</h1>
                <div style="display:flex;gap:10px;align-items:center">
                    @auth
                        <span class="admin-chip">
                            <span class="admin-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                            <span class="admin-chip-text">
                                <strong>{{ auth()->user()->name }}</strong>
                                <span>Administrator</span>
                            </span>
                        </span>
                    @endauth
                    <a class="btn secondary" href="/">Storefront</a>
                    <form action="{{ route('admin.logout') }}" method="post">
                        @csrf
                        <button class="btn danger" type="submit">Logout</button>
                    </form>
                </div>
            </header>

            <main class="content">
                @if(session('status'))
                    <div class="notice"><span class="notice-icon">&#10003;</span>{{ session('status') }}</div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @include('partials.loading-overlay')

    <script>
        document.addEventListener('input', function (e) {
            var el = e.target;
            if (el.matches && el.matches('input[type="number"]')) {
                el.value = el.value.replace(/^0+(?=\d)/, '');
            }
        });
    </script>
</body>
</html>
```

### 2.2 `resources/views/partials/loading-overlay.blade.php`

```blade
<style>
    .page-loader{position:fixed;inset:0;background:rgba(255,255,255,.72);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:9999;opacity:0;visibility:hidden;transition:opacity .15s ease}
    .page-loader.is-active{opacity:1;visibility:visible}
    .page-loader-box{display:flex;flex-direction:column;align-items:center;gap:12px}
    .page-loader-spinner{width:44px;height:44px;border-radius:50%;border:4px solid var(--line,#dde5f0);border-top-color:var(--brand,#0057a8);animation:page-loader-spin .7s linear infinite}
    .page-loader-text{font-weight:800;color:var(--brand,#0057a8);font-size:13px;letter-spacing:.02em}
    @keyframes page-loader-spin{to{transform:rotate(360deg)}}
</style>

<div id="page-loader" class="page-loader" aria-hidden="true">
    <div class="page-loader-box">
        <span class="page-loader-spinner"></span>
        <span class="page-loader-text">Loading…</span>
    </div>
</div>

<script>
    (function () {
        var loader = document.getElementById('page-loader');
        if (! loader) return;

        var hideTimer;

        function showLoader() {
            loader.classList.add('is-active');
            loader.setAttribute('aria-hidden', 'false');
            // Safety net: a page that never finishes navigating (dropped
            // connection, blocked request) would otherwise leave the
            // overlay stuck forever.
            window.clearTimeout(hideTimer);
            hideTimer = window.setTimeout(hideLoader, 8000);
        }

        function hideLoader() {
            loader.classList.remove('is-active');
            loader.setAttribute('aria-hidden', 'true');
            window.clearTimeout(hideTimer);
        }

        window.PageLoader = { show: showLoader, hide: hideLoader };

        document.addEventListener('click', function (event) {
            if (event.defaultPrevented || event.button !== 0) return;
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

            var link = event.target.closest('a[href]');
            if (! link || link.dataset.noLoader !== undefined) return;
            if (link.target === '_blank' || link.hasAttribute('download')) return;

            var href = link.getAttribute('href') || '';
            if (! href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;

            var url;
            try {
                url = new URL(link.href, window.location.href);
            } catch (e) {
                return;
            }

            if (url.origin !== window.location.origin) return;
            // A link to the same page that only changes the hash (in-page anchor) doesn't navigate.
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;

            showLoader();
        });

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (event.defaultPrevented) return;
            if (form.dataset.noLoader !== undefined) return;
            // AJAX forms (add-to-cart, etc.) manage their own loading state.
            if (form.hasAttribute('data-cart-add')) return;

            showLoader();
        });

        // Restores from the browser's back/forward cache arrive with the
        // page already rendered, so any loader left over from before must
        // be cleared instead of sitting on screen.
        window.addEventListener('pageshow', hideLoader);
    })();
</script>
```

---

## 3. Dashboard

- Shows totals for products, categories, orders, customers and revenue, plus **low stock** (5 or fewer).
- **Payment notifications** are paid orders the admin has not opened yet (`admin_payment_seen_at` is empty).
- The `dashboard()` method lives in `Admin/ProductController` (full file in section 4).

**Routes** (`routes/web.php`)

```php
Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdminSession::class)->group(function () {
    Route::get('/', [AdminProductController::class, 'dashboard'])->name('dashboard');
});
```

### 3.1 `dashboard()` in `app/Http/Controllers/Admin/ProductController.php`

```php
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
```

### 3.2 `resources/views/admin/dashboard.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Admin Dashboard - CEC Electronic')
@section('heading', 'Dashboard')

@section('content')
    <section class="stats">
        <div class="panel stat">
            <span class="stat-icon blue">&#128421;</span>
            <span class="stat-body"><span>Total products</span><strong>{{ number_format($stats['products']) }}</strong></span>
        </div>
        <div class="panel stat">
            <span class="stat-icon purple">&#129534;</span>
            <span class="stat-body"><span>Orders</span><strong>{{ number_format($stats['orders']) }}</strong></span>
        </div>
        <div class="panel stat">
            <span class="stat-icon cyan">&#128101;</span>
            <span class="stat-body"><span>Customers</span><strong>{{ number_format($stats['customers']) }}</strong></span>
        </div>
        <div class="panel stat">
            <span class="stat-icon green">&#128176;</span>
            <span class="stat-body"><span>Revenue</span><strong>${{ number_format($stats['revenue'], 2) }}</strong></span>
        </div>
    </section>

    <section class="stats">
        <div class="panel stat">
            <span class="stat-icon blue">&#128194;</span>
            <span class="stat-body"><span>Categories</span><strong>{{ number_format($stats['categories']) }}</strong></span>
        </div>
        <div class="panel stat">
            <span class="stat-icon red">&#9888;</span>
            <span class="stat-body"><span>Low stock</span><strong>{{ number_format($stats['low_stock']) }}</strong></span>
        </div>
        <div class="panel stat">
            <span class="stat-icon green">&#128181;</span>
            <span class="stat-body"><span>Catalog value</span><strong>${{ number_format($stats['value'], 2) }}</strong></span>
        </div>
        <div class="panel stat">
            <span class="stat-icon amber">&#128276;</span>
            <span class="stat-body"><span>Payment alerts</span><strong>{{ number_format($stats['payment_notifications']) }}</strong></span>
        </div>
    </section>

    <section class="split">
        <div>
            <div class="toolbar">
                <h2 style="margin:0">Payment notifications</h2>
                <a class="btn secondary" href="{{ route('admin.orders.index') }}">Review orders</a>
            </div>
            <div class="panel mini-list">
                @forelse($paymentNotifications as $order)
                    <a class="mini-item" href="{{ route('admin.orders.show', $order) }}">
                        <div>
                            <strong>{{ $order->order_number }}</strong>
                            <div class="muted">{{ $order->customer_name }} paid by {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</div>
                        </div>
                        <div style="text-align:right">
                            <strong>${{ number_format($order->grand_total, 2) }}</strong>
                            <div><span class="status unread">New payment</span></div>
                        </div>
                    </a>
                @empty
                    <p class="muted" style="margin:0">No unread payment notifications.</p>
                @endforelse
            </div>
        </div>

        <div>
            <div class="toolbar">
                <h2 style="margin:0">Latest orders</h2>
                <a class="btn secondary" href="{{ route('admin.orders.index') }}">View all</a>
            </div>
            <div class="panel mini-list">
                @forelse($latestOrders as $order)
                    <div class="mini-item">
                        <div>
                            <strong>{{ $order->order_number }}</strong>
                            <div class="muted">{{ $order->customer_name }} - {{ $order->customer_phone }}</div>
                        </div>
                        <div style="text-align:right">
                            <strong>${{ number_format($order->grand_total, 2) }}</strong>
                            <div><span class="status {{ $order->status === 'pending' ? 'warning' : '' }}">{{ ucfirst($order->status) }}</span></div>
                        </div>
                    </div>
                @empty
                    <p class="muted" style="margin:0">No orders yet.</p>
                @endforelse
            </div>
        </div>

    </section>

    <section style="margin-top:16px">
        <div>
            <div class="toolbar">
                <h2 style="margin:0">Latest products</h2>
                <a class="btn secondary" href="{{ route('admin.products.index') }}">Manage</a>
            </div>
            <div class="panel" style="overflow:hidden">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Stock</th>
                            <th style="text-align:right">Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestProducts as $product)
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <img class="thumb" src="{{ $product->image_url }}" alt="{{ $product->name }}">
                                        <div>
                                            <strong>{{ $product->name }}</strong>
                                            <div class="muted">${{ number_format($product->price, 2) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="status {{ $product->stock_quantity <= 5 ? 'danger' : '' }}">{{ $product->stock_quantity }} left</span></td>
                                <td><div class="actions"><a class="btn secondary" href="{{ route('admin.products.edit', $product) }}">Edit</a></div></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="muted">No products yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
```

---

## 4. Product

- List (10 per page, filter by brand), create, edit, delete.
- Name and price are required. SKU must be unique. The image can be up to 4 MB and is stored under `storage/app/public/products`.
- The slug is generated from the name. Saving a product clears the cache so the shop updates right away.

**Routes** (`routes/web.php`)

```php
Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdminSession::class)->group(function () {
    Route::resource('products', AdminProductController::class);
});
```

### 4.1 `app/Http/Controllers/Admin/ProductController.php`

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

### 4.2 `app/Models/Product.php`

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

### 4.3 `resources/views/admin/products/index.blade.php`

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

### 4.4 `resources/views/admin/products/create.blade.php`

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

### 4.5 `resources/views/admin/products/edit.blade.php`

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

### 4.6 `resources/views/admin/products/_form.blade.php`

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

---

## 5. Category

- Fields: parent category, name, description, image, active flag, sort order. The slug is generated automatically.

**Routes** (`routes/web.php`)

```php
Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdminSession::class)->group(function () {
    Route::resource('categories', AdminCategoryController::class)->except(['show']);
});
```

### 5.1 `app/Http/Controllers/Admin/CategoryController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()->orderBy('sort_order')->latest()->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $category = new Category(['is_active' => true]);
        $parents = Category::query()->orderBy('name')->get();

        return view('admin.categories.create', compact('category', 'parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $this->storeUploadedImage($request, $data);

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category): View
    {
        $parents = Category::query()
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('admin.categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $category->name === $data['name'] ? $category->slug : $this->uniqueSlug($data['name'], $category->id);
        $this->storeUploadedImage($request, $data, $category);

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('admin.categories.index')->with('status', 'Category deleted.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]) + [
            'is_active' => false,
            'sort_order' => 0,
        ];
    }

    private function storeUploadedImage(Request $request, array &$data, ?Category $category = null): void
    {
        if (! $request->hasFile('image')) {
            unset($data['image']);
            return;
        }

        $path = $request->file('image')->store('categories', 'public');
        $data['image'] = $path;

        if ($category?->image && ! Str::startsWith($category->image, ['http://', 'https://', '/'])) {
            Storage::disk('public')->delete($category->image);
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (
            Category::query()
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

### 5.2 `app/Models/Category.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // See Product::booted() — same reasoning: no single cache key to
        // target for the storefront's dynamic catalog cache keys.
        static::saved(fn () => Cache::flush());
        static::deleted(fn () => Cache::flush());
    }

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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

### 5.3 `resources/views/admin/categories/index.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Categories - CEC Electronic Admin')
@section('heading', 'Categories')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Category structure</h2>
            <p class="muted" style="margin:6px 0 0">Organize products for storefront browsing and filters.</p>
        </div>
        <a class="btn" href="{{ route('admin.categories.create') }}">Add category</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Sort</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td><strong>{{ $category->name }}</strong></td>
                        <td class="muted">{{ $category->slug }}</td>
                        <td><span class="status">{{ $category->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td>{{ $category->sort_order }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="post" onsubmit="return confirm('Delete this category?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No categories yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $categories->links() }}</div>
@endsection
```

### 5.4 `resources/views/admin/categories/create.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Add Category - CEC Electronic Admin')
@section('heading', 'Add Category')

@section('content')
    <form class="panel form" action="{{ route('admin.categories.store') }}" method="post" enctype="multipart/form-data">
        @include('admin.categories._form', ['buttonText' => 'Create category'])
    </form>
@endsection
```

### 5.5 `resources/views/admin/categories/edit.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Edit Category - CEC Electronic Admin')
@section('heading', 'Edit Category')

@section('content')
    <form class="panel form" action="{{ route('admin.categories.update', $category) }}" method="post" enctype="multipart/form-data">
        @method('PUT')
        @include('admin.categories._form', ['buttonText' => 'Save changes'])
    </form>
@endsection
```

### 5.6 `resources/views/admin/categories/_form.blade.php`

```blade
@csrf

<div class="form-grid">
    <div class="field full">
        <label for="name">Category name</label>
        <input id="name" name="name" value="{{ old('name', $category->name) }}" required>
        @error('name') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="parent_id">Parent category</label>
        <select id="parent_id" name="parent_id">
            <option value="">None</option>
            @foreach($parents as $parent)
                <option value="{{ $parent->id }}" @selected((int) old('parent_id', $category->parent_id) === $parent->id)>{{ $parent->name }}</option>
            @endforeach
        </select>
        @error('parent_id') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="sort_order">Sort order</label>
        <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
        @error('sort_order') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field full">
        <label for="image">Category image</label>
        @if($category->image)
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
                <img class="thumb" src="{{ $category->image_url }}" alt="{{ $category->name ?: 'Category image' }}">
                <span class="muted">Upload a new JPG, PNG, or WEBP file to replace this image.</span>
            </div>
        @endif
        <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        @error('image') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field full">
        <label for="description">Description</label>
        <textarea id="description" name="description">{{ old('description', $category->description) }}</textarea>
        @error('description') <span class="error">{{ $message }}</span> @enderror
    </div>

    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))> Active</label>
</div>

<div style="display:flex;gap:10px;margin-top:18px">
    <button class="btn" type="submit">{{ $buttonText }}</button>
    <a class="btn secondary" href="{{ route('admin.categories.index') }}">Cancel</a>
</div>
```

---

## 6. Brand

- Fields: name, logo (jpg, png or webp, up to 4 MB), active flag, sort order.
- Deleting a brand keeps its products (`brand_id` is set to null). Only active brands **with a logo** appear on the shop home page.

**Routes** (`routes/web.php`)

```php
Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdminSession::class)->group(function () {
    Route::resource('brands', AdminBrandController::class)->except(['show']);
});
```

### 6.1 `app/Http/Controllers/Admin/BrandController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Brand::query()->withCount('products')->orderBy('sort_order')->orderBy('name')->paginate(10);

        return view('admin.brands.index', compact('brands'));
    }

    public function create(): View
    {
        $brand = new Brand(['is_active' => true]);

        return view('admin.brands.create', compact('brand'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $this->storeUploadedLogo($request, $data);

        Brand::create($data);

        return redirect()->route('admin.brands.index')->with('status', 'Brand created.');
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $brand->name === $data['name'] ? $brand->slug : $this->uniqueSlug($data['name'], $brand->id);
        $this->storeUploadedLogo($request, $data, $brand);

        $brand->update($data);

        return redirect()->route('admin.brands.index')->with('status', 'Brand updated.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        // products.brand_id is nullOnDelete, so its products are kept, just unbranded.
        $brand->delete();

        return redirect()->route('admin.brands.index')->with('status', 'Brand deleted.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]) + [
            'is_active' => false,
            'sort_order' => 0,
        ];
    }

    private function storeUploadedLogo(Request $request, array &$data, ?Brand $brand = null): void
    {
        if (! $request->hasFile('logo')) {
            unset($data['logo']);
            return;
        }

        $path = $request->file('logo')->store('brands', 'public');
        $data['logo'] = $path;

        if ($brand?->logo && ! Str::startsWith($brand->logo, ['http://', 'https://', '/', 'images/'])) {
            Storage::disk('public')->delete($brand->logo);
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (
            Brand::query()
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

### 6.2 `app/Models/Brand.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // See Product::booted() — same reasoning: no single cache key to
        // target for the storefront's dynamic catalog cache keys.
        static::saved(fn () => Cache::flush());
        static::deleted(fn () => Cache::flush());
    }

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getInitialsAttribute(): string
    {
        return Str::of($this->name)->substr(0, 2)->upper()->toString();
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        if (Str::startsWith($this->logo, ['http://', 'https://', '/'])) {
            return $this->logo;
        }

        if (Str::startsWith($this->logo, 'images/')) {
            return asset($this->logo);
        }

        // asset() rather than Storage::url() — see Category::getImageUrlAttribute().
        return asset('storage/' . $this->logo);
    }
}
```

### 6.3 `resources/views/admin/brands/index.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Brands - CEC Electronic Admin')
@section('heading', 'Brands')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Product brands</h2>
            <p class="muted" style="margin:6px 0 0">Assign brands to products so customers can browse and filter by brand.</p>
        </div>
        <a class="btn" href="{{ route('admin.brands.create') }}">Add brand</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Brand</th>
                    <th>Slug</th>
                    <th>Products</th>
                    <th>Status</th>
                    <th>Sort</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($brands as $brand)
                    <tr>
                        <td>
                            <div class="product-cell">
                                @if($brand->logo_url)
                                    <img class="thumb" src="{{ $brand->logo_url }}" alt="{{ $brand->name }} logo" style="object-fit:contain">
                                @endif
                                <strong>{{ $brand->name }}</strong>
                            </div>
                        </td>
                        <td class="muted">{{ $brand->slug }}</td>
                        <td>{{ $brand->products_count }}</td>
                        <td><span class="status">{{ $brand->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td>{{ $brand->sort_order }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="{{ route('admin.products.index', ['brand' => $brand->id]) }}">Products</a>
                                <a class="btn secondary" href="{{ route('admin.brands.edit', $brand) }}">Edit</a>
                                <form action="{{ route('admin.brands.destroy', $brand) }}" method="post" onsubmit="return confirm('Delete this brand? Its products will be kept without a brand.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No brands yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $brands->links() }}</div>
@endsection
```

### 6.4 `resources/views/admin/brands/create.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Add Brand - CEC Electronic Admin')
@section('heading', 'Add Brand')

@section('content')
    <form class="panel form" action="{{ route('admin.brands.store') }}" method="post" enctype="multipart/form-data">
        @include('admin.brands._form', ['buttonText' => 'Create brand'])
    </form>
@endsection
```

### 6.5 `resources/views/admin/brands/edit.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Edit Brand - CEC Electronic Admin')
@section('heading', 'Edit Brand')

@section('content')
    <form class="panel form" action="{{ route('admin.brands.update', $brand) }}" method="post" enctype="multipart/form-data">
        @method('PUT')
        @include('admin.brands._form', ['buttonText' => 'Save changes'])
    </form>
@endsection
```

### 6.6 `resources/views/admin/brands/_form.blade.php`

```blade
@csrf

<div class="form-grid">
    <div class="field full">
        <label for="name">Brand name</label>
        <input id="name" name="name" value="{{ old('name', $brand->name) }}" required>
        @error('name') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="sort_order">Sort order</label>
        <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $brand->sort_order ?? 0) }}">
        @error('sort_order') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field full">
        <label for="logo">Brand logo</label>
        @if($brand->logo_url)
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
                <img class="thumb" src="{{ $brand->logo_url }}" alt="{{ $brand->name ?: 'Brand logo' }}" style="object-fit:contain">
                <span class="muted">Upload a new JPG, PNG, or WEBP file to replace this logo.</span>
            </div>
        @endif
        <input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        @error('logo') <span class="error">{{ $message }}</span> @enderror
    </div>

    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $brand->is_active ?? true))> Active</label>
</div>

<div style="display:flex;gap:10px;margin-top:18px">
    <button class="btn" type="submit">{{ $buttonText }}</button>
    <a class="btn secondary" href="{{ route('admin.brands.index') }}">Cancel</a>
</div>
```

---

## 7. Supplier

- Fields: name, company, email, phone, website, address, contact person, payment terms, active flag, notes.
- Only active suppliers can be chosen in the product form.

**Routes** (`routes/web.php`)

```php
Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdminSession::class)->group(function () {
    Route::resource('suppliers', AdminSupplierController::class)->except(['show']);
});
```

### 7.1 `app/Http/Controllers/Admin/SupplierController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        $suppliers = Supplier::withCount('products')->latest()->paginate(10);

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        $supplier = new Supplier(['is_active' => true]);

        return view('admin.suppliers.create', compact('supplier'));
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::create($this->validatedData($request));

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier created.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validatedData($request));

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier deleted.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]) + ['is_active' => false];
    }
}
```

### 7.2 `app/Models/Supplier.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'website',
        'address',
        'contact_person',
        'payment_terms',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
```

### 7.3 `resources/views/admin/suppliers/index.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Suppliers - CEC Electronic Admin')
@section('heading', 'Suppliers')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Supplier management</h2>
            <p class="muted" style="margin:6px 0 0">Track vendors, contact details, terms, and product sourcing.</p>
        </div>
        <a class="btn" href="{{ route('admin.suppliers.create') }}">Add supplier</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Contact</th>
                    <th>Terms</th>
                    <th>Products</th>
                    <th>Status</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr>
                        <td><strong>{{ $supplier->name }}</strong><div class="muted">{{ $supplier->company_name }}</div></td>
                        <td>{{ $supplier->phone }}<div class="muted">{{ $supplier->email }}</div></td>
                        <td>{{ $supplier->payment_terms ?: 'Not set' }}</td>
                        <td>{{ $supplier->products_count }}</td>
                        <td><span class="status">{{ $supplier->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="{{ route('admin.suppliers.edit', $supplier) }}">Edit</a>
                                <form action="{{ route('admin.suppliers.destroy', $supplier) }}" method="post" onsubmit="return confirm('Delete this supplier?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No suppliers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $suppliers->links() }}</div>
@endsection
```

### 7.4 `resources/views/admin/suppliers/create.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Add Supplier - CEC Electronic Admin')
@section('heading', 'Add Supplier')

@section('content')
    <form class="panel form" action="{{ route('admin.suppliers.store') }}" method="post">
        @include('admin.suppliers._form', ['buttonText' => 'Create supplier'])
    </form>
@endsection
```

### 7.5 `resources/views/admin/suppliers/edit.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Edit Supplier - CEC Electronic Admin')
@section('heading', 'Edit Supplier')

@section('content')
    <form class="panel form" action="{{ route('admin.suppliers.update', $supplier) }}" method="post">
        @method('PUT')
        @include('admin.suppliers._form', ['buttonText' => 'Save changes'])
    </form>
@endsection
```

### 7.6 `resources/views/admin/suppliers/_form.blade.php`

```blade
@csrf

<div class="form-grid">
    <div class="field">
        <label for="name">Supplier name</label>
        <input id="name" name="name" value="{{ old('name', $supplier->name) }}" required>
        @error('name') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="company_name">Company name</label>
        <input id="company_name" name="company_name" value="{{ old('company_name', $supplier->company_name) }}">
    </div>

    <div class="field">
        <label for="contact_person">Contact person</label>
        <input id="contact_person" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}">
    </div>

    <div class="field">
        <label for="phone">Phone</label>
        <input id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}">
    </div>

    <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $supplier->email) }}">
    </div>

    <div class="field">
        <label for="website">Website</label>
        <input id="website" name="website" type="url" value="{{ old('website', $supplier->website) }}">
    </div>

    <div class="field full">
        <label for="address">Address</label>
        <textarea id="address" name="address">{{ old('address', $supplier->address) }}</textarea>
    </div>

    <div class="field">
        <label for="payment_terms">Payment terms</label>
        <input id="payment_terms" name="payment_terms" value="{{ old('payment_terms', $supplier->payment_terms) }}" placeholder="Net 30, prepaid, COD">
    </div>

    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active ?? true))> Active</label>

    <div class="field full">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes">{{ old('notes', $supplier->notes) }}</textarea>
    </div>
</div>

<div style="display:flex;gap:10px;margin-top:18px">
    <button class="btn" type="submit">{{ $buttonText }}</button>
    <a class="btn secondary" href="{{ route('admin.suppliers.index') }}">Cancel</a>
</div>
```

---

## 8. Order & Payment

- **List:** 10 orders per page, with a count of new payments.
- **Show:** opening a newly paid order marks the payment notification as seen.
- **Update:** changes the status, payment status, delivery zone or provider, tracking number and dates. It also creates or updates a **Shipment**.
- **Verify payment:** checks Bakong right away for this order. If the payment is found, the order is marked paid.
- **Receipt:** downloads a PDF.
- **Background check:** `bakong:check-pending` runs every 5 minutes and checks recent unpaid Bakong orders. It needs `php artisan schedule:work` or a cron job running.

**Routes** (`routes/web.php`)

```php
Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdminSession::class)->group(function () {
    Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'update']);
    Route::get('orders/{order}/receipt', [AdminOrderController::class, 'receipt'])->name('orders.receipt');
    Route::post('orders/{order}/verify-payment', [AdminOrderController::class, 'verifyPayment'])->name('orders.verify-payment');
});
```

### 8.1 `app/Http/Controllers/Admin/OrderController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryProvider;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Services\BakongService;
use App\Services\ReceiptPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()
            ->with(['deliveryProvider', 'deliveryZone'])
            ->withCount('items')
            ->latest()
            ->paginate(10);
        $paymentNotificationsCount = Order::query()
            ->whereNotNull('payment_confirmed_at')
            ->whereNull('admin_payment_seen_at')
            ->count();

        return view('admin.orders.index', compact('orders', 'paymentNotificationsCount'));
    }

    public function receipt(Order $order, ReceiptPdf $receipts): Response
    {
        return $receipts->download($order);
    }

    public function show(Order $order): View
    {
        if ($order->payment_confirmed_at && ! $order->admin_payment_seen_at) {
            $order->update(['admin_payment_seen_at' => now()]);
            $order->refresh();
        }

        $order->load(['items', 'deliveryProvider', 'deliveryZone', 'shipments.deliveryProvider']);
        $deliveryProviders = DeliveryProvider::where('is_active', true)->orderBy('name')->get();
        $deliveryZones = DeliveryZone::where('is_active', true)->orderBy('name')->get();

        return view('admin.orders.show', compact('order', 'deliveryProviders', 'deliveryZones'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:50'],
            'payment_status' => ['required', 'string', 'max:50'],
            'delivery_zone_id' => ['nullable', 'exists:delivery_zones,id'],
            'delivery_provider_id' => ['nullable', 'exists:delivery_providers,id'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'shipped_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
        ]);

        if ($data['payment_status'] === 'paid' && $order->payment_status !== 'paid') {
            $data['payment_confirmed_at'] = now();
            $data['admin_payment_seen_at'] = now();
        }

        $order->update($data);

        if (! empty($data['delivery_provider_id']) || ! empty($data['tracking_number'])) {
            $order->shipments()->updateOrCreate(
                ['tracking_number' => $data['tracking_number'] ?? null],
                [
                    'delivery_provider_id' => $data['delivery_provider_id'] ?? null,
                    'tracking_number' => $data['tracking_number'] ?? null,
                    'status' => ($data['delivered_at'] ?? null) ? 'delivered' : (($data['shipped_at'] ?? null) ? 'shipped' : 'pending'),
                    'delivery_fee' => (int) round((float) $order->shipping_total),
                    'picked_up_at' => $data['shipped_at'] ?? null,
                    'delivered_at' => $data['delivered_at'] ?? null,
                ]
            );
        }

        return redirect()->route('admin.orders.show', $order)->with('status', 'Order updated.');
    }

    /**
     * Lets an admin manually re-check one order against Bakong on demand.
     * Bypasses the shared automated daily-check budget — a human clicking
     * this button is inherently rate-limited, unlike the automated live
     * polling / background job, which is what that budget guards against.
     */
    public function verifyPayment(Request $request, Order $order, BakongService $bakong): RedirectResponse
    {
        if ($order->payment_method !== 'bakong' || ! $order->bakong_qr_md5) {
            return back()->with('status', 'This order has no Bakong payment to verify.');
        }

        if ($order->payment_status === 'paid') {
            return back()->with('status', 'This order is already marked paid.');
        }

        // Debounce accidental double-clicks; not a budget limit.
        if (! $bakong->allowOnce("bakong-admin-verify:{$order->id}", 10)) {
            return back()->with('status', 'Already checking — please wait a moment.');
        }

        $transaction = $bakong->checkTransactionByMd5($order->bakong_qr_md5, enforceBudget: false);

        if ($transaction === null) {
            return back()->with('status', 'No matching Bakong payment found yet. If the customer just paid, try again in a few seconds.');
        }

        $order->update([
            'payment_status' => 'paid',
            'payment_confirmed_at' => now(),
            'admin_payment_seen_at' => now(),
        ]);

        return back()->with('status', 'Payment confirmed via Bakong.');
    }
}
```

### 8.2 `app/Services/ReceiptPdf.php`

```php
<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReceiptPdf
{
    /**
     * A receipt only exists once payment is confirmed; unpaid orders 404.
     */
    public function download(Order $order): Response
    {
        return $this->pdf($order)->download($this->filename($order));
    }

    /**
     * Same receipt, opened in the browser instead of saved.
     */
    public function stream(Order $order): Response
    {
        return $this->pdf($order)->stream($this->filename($order));
    }

    private function pdf(Order $order)
    {
        abort_unless($order->payment_status === 'paid', 404);

        $order->loadMissing('items', 'deliveryProvider');

        return Pdf::loadView('receipts.order', compact('order'))->setPaper('a4');
    }

    private function filename(Order $order): string
    {
        return 'receipt-'.$order->order_number.'.pdf';
    }
}
```

### 8.3 `app/Console/Commands/CheckPendingBakongPayments.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\BakongService;
use Illuminate\Console\Command;

/**
 * Keeps confirming Bakong KHQR payments in the background so a customer
 * closing their browser tab (or an admin never checking back) doesn't leave
 * a paid order stuck as "unpaid" forever — the live page polling only covers
 * the first ~10 minutes after checkout.
 */
class CheckPendingBakongPayments extends Command
{
    protected $signature = 'bakong:check-pending {--limit=5 : Maximum number of orders to check this run}';

    protected $description = 'Check pending Bakong KHQR orders against the Bakong transaction API and mark them paid once confirmed.';

    public function handle(BakongService $bakong): int
    {
        $limit = max(1, (int) $this->option('limit'));

        // Newest first: a customer waiting right now matters more than an
        // hours-old abandoned cart, and without this, a backlog of stale
        // unpaid orders would fill every run's limit and starve new ones out
        // forever. A 2-hour window also means we stop spending budget on
        // carts that are almost certainly abandoned rather than just slow.
        $orders = Order::query()
            ->where('payment_method', 'bakong')
            ->where('payment_status', 'unpaid')
            ->whereNotNull('bakong_qr_md5')
            ->where('created_at', '>=', now()->subHours(2))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $checked = 0;
        $confirmed = 0;

        foreach ($orders as $order) {
            // Without this, every unpaid order in the 2-hour window gets
            // re-checked on every single 5-minute run — a handful of stale
            // abandoned carts can then burn most of the shared daily Bakong
            // budget by themselves, starving checks for orders customers are
            // actively paying right now. Space job-driven rechecks per order
            // out to once every 20 minutes instead (live page polling already
            // covers the minutes right after checkout much more tightly).
            if (! $bakong->allowOnce("bakong-job-check:{$order->id}", 1200)) {
                continue;
            }

            $checked++;
            $transaction = $bakong->checkTransactionByMd5($order->bakong_qr_md5);

            if ($transaction !== null) {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_confirmed_at' => now(),
                ]);
                $confirmed++;
            }
        }

        $this->info("Checked {$checked} of {$orders->count()} pending Bakong order(s), confirmed {$confirmed}.");

        return self::SUCCESS;
    }
}
```

### 8.4 `routes/console.php`

```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keeps confirming KHQR payments even after a customer closes the checkout
// tab. The Bakong daily quota guard inside BakongService keeps this (and
// live page polling) from ever exceeding the account's request cap.
Schedule::command('bakong:check-pending --limit=5')->everyFiveMinutes();
```

### 8.5 `app/Models/Order.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'payment_status',
        'payment_confirmed_at',
        'admin_payment_seen_at',
        'payment_method',
        'bakong_session_id',
        'bakong_checkout_url',
        'bakong_qr_string',
        'bakong_qr_md5',
        'bakong_qr_expires_at',
        'shipping_method',
        'delivery_zone_id',
        'delivery_provider_id',
        'tracking_number',
        'shipped_at',
        'delivered_at',
        'subtotal',
        'shipping_total',
        'discount_total',
        'grand_total',
        'shipping_address',
        'notes',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'subtotal' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'placed_at' => 'datetime',
            'payment_confirmed_at' => 'datetime',
            'bakong_qr_expires_at' => 'datetime',
            'admin_payment_seen_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function bakongQrExpired(): bool
    {
        return $this->bakong_qr_expires_at !== null && $this->bakong_qr_expires_at->isPast();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function deliveryProvider(): BelongsTo
    {
        return $this->belongsTo(DeliveryProvider::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
```

### 8.6 `app/Models/Shipment.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'delivery_provider_id',
        'tracking_number',
        'status',
        'delivery_fee',
        'picked_up_at',
        'delivered_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryProvider(): BelongsTo
    {
        return $this->belongsTo(DeliveryProvider::class);
    }
}
```

### 8.7 `app/Models/DeliveryProvider.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'tracking_url',
        'base_fee',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
```

### 8.8 `resources/views/admin/orders/index.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Orders - CEC Electronic Admin')
@section('heading', 'Orders')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Order management</h2>
            <p class="muted" style="margin:6px 0 0">Review customer orders, payment state, and fulfillment progress.</p>
        </div>
        @if($paymentNotificationsCount > 0)
            <span class="status unread">{{ $paymentNotificationsCount }} new payment {{ $paymentNotificationsCount === 1 ? 'notification' : 'notifications' }}</span>
        @endif
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Delivery</th>
                    <th>Payment</th>
                    <th>Total</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $statusClasses = [
                        'pending' => 'orange',
                        'processing' => 'info',
                        'shipped' => 'info',
                        'completed' => '',
                        'cancelled' => 'danger',
                    ];
                    $paymentClasses = [
                        'unpaid' => 'danger',
                        'paid' => 'success',
                        'refunded' => 'info',
                        'failed' => 'danger',
                    ];
                @endphp
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <strong>{{ $order->order_number }}</strong>
                            @if($order->payment_confirmed_at && ! $order->admin_payment_seen_at)
                                <span class="status unread" style="margin-left:8px">New payment</span>
                            @endif
                            <div class="muted">{{ $order->items_count }} items</div>
                        </td>
                        <td>{{ $order->customer_name }}<div class="muted">{{ $order->customer_phone }}</div></td>
                        <td><span class="status {{ $statusClasses[$order->status] ?? '' }}">{{ ucfirst($order->status) }}</span></td>
                        <td>{{ $order->deliveryProvider?->name ?: 'Unassigned' }}<div class="muted">{{ $order->tracking_number }}</div></td>
                        <td>
                            <span class="status-text {{ $paymentClasses[$order->payment_status] ?? '' }}">{{ ucfirst($order->payment_status) }}</span>
                            <div class="muted">{{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</div>
                        </td>
                        <td>${{ number_format($order->grand_total, 2) }}</td>
                        <td><div class="actions"><a class="btn secondary" href="{{ route('admin.orders.show', $order) }}">Open</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $orders->links() }}</div>
@endsection
```

### 8.9 `resources/views/admin/orders/show.blade.php`

```blade
@extends('admin.layout')

@section('title', $order->order_number.' - CEC Electronic Admin')
@section('heading', 'Order '.$order->order_number)

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">{{ $order->order_number }}</h2>
            <p class="muted" style="margin:6px 0 0">Placed {{ $order->placed_at?->format('M d, Y h:i A') ?? $order->created_at->format('M d, Y h:i A') }}</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @if($order->payment_status === 'paid')
                <a class="btn" href="{{ route('admin.orders.receipt', $order) }}">Download receipt</a>
            @endif
            <a class="btn secondary" href="{{ route('admin.orders.index') }}">Back to orders</a>
        </div>
    </div>

    <section style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:16px">
        <div class="panel" style="padding:18px">
            <h3 style="margin-top:0">Items</h3>
            @foreach($order->items as $item)
                <div style="display:grid;grid-template-columns:1fr 80px 100px;gap:12px;border-bottom:1px solid var(--line);padding:12px 0">
                    <strong>{{ $item->product_name }}</strong>
                    <span class="muted">x {{ $item->quantity }}</span>
                    <strong style="text-align:right">${{ number_format($item->line_total, 2) }}</strong>
                </div>
            @endforeach
        </div>

        <aside class="panel" style="padding:18px">
            <h3 style="margin-top:0">Customer</h3>
            <p class="muted" style="line-height:1.7">{{ $order->customer_name }}<br>{{ $order->customer_phone }}<br>{{ $order->customer_email }}</p>

            @if($order->payment_confirmed_at)
                <div class="payment-verified-banner">
                    <span class="icon">&#10003;</span>
                    <span>
                        <strong>Payment verified</strong>
                        <span>Via {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }} &middot; {{ $order->payment_confirmed_at->format('M d, Y h:i A') }}</span>
                    </span>
                </div>
            @elseif($order->payment_method === 'bakong' && $order->bakong_qr_md5)
                @if(session('status'))
                    <div class="muted" style="margin-bottom:10px">{{ session('status') }}</div>
                @endif
                <form action="{{ route('admin.orders.verify-payment', $order) }}" method="post" style="margin-bottom:14px">
                    @csrf
                    <button class="btn secondary" style="width:100%" type="submit">Verify with Bakong now</button>
                </form>
            @endif

            <form action="{{ route('admin.orders.update', $order) }}" method="post">
                @csrf
                @method('PUT')
                <label>Status
                    <select name="status">
                        @foreach(['pending','processing','shipped','completed','cancelled'] as $status)
                            <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display:block;margin-top:12px">Payment
                    <select name="payment_status">
                        @foreach(['unpaid','paid','refunded','failed'] as $status)
                            <option value="{{ $status }}" @selected($order->payment_status === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display:block;margin-top:12px">Delivery zone
                    <select name="delivery_zone_id">
                        <option value="">Unassigned</option>
                        @foreach($deliveryZones as $zone)
                            <option value="{{ $zone->id }}" @selected($order->delivery_zone_id === $zone->id)>{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display:block;margin-top:12px">Delivery provider
                    <select name="delivery_provider_id">
                        <option value="">Unassigned</option>
                        @foreach($deliveryProviders as $provider)
                            <option value="{{ $provider->id }}" @selected($order->delivery_provider_id === $provider->id)>{{ $provider->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display:block;margin-top:12px">Tracking number
                    <input name="tracking_number" value="{{ old('tracking_number', $order->tracking_number) }}">
                </label>
                <label style="display:block;margin-top:12px">Shipped at
                    <input name="shipped_at" type="datetime-local" value="{{ old('shipped_at', $order->shipped_at?->format('Y-m-d\\TH:i')) }}">
                </label>
                <label style="display:block;margin-top:12px">Delivered at
                    <input name="delivered_at" type="datetime-local" value="{{ old('delivered_at', $order->delivered_at?->format('Y-m-d\\TH:i')) }}">
                </label>
                <button class="btn" style="width:100%;margin-top:14px" type="submit">Update order</button>
            </form>

            <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">
            <div style="display:flex;justify-content:space-between;font-size:16px"><span>Total</span><strong style="color:var(--brand)">${{ number_format($order->grand_total, 2) }}</strong></div>
            <div class="muted" style="margin-top:12px;line-height:1.7">
                Zone: {{ $order->deliveryZone?->name ?: 'Unassigned' }}<br>
                Provider: {{ $order->deliveryProvider?->name ?: 'Unassigned' }}
            </div>
        </aside>
    </section>
@endsection
```

---

## 9. Customer

- There is no customers table. Customers are built by grouping orders by `customer_phone`: name, email, number of orders, total spent and last order date.
- The detail page shows all orders for that phone number, with totals for spent and unpaid.

**Routes** (`routes/web.php`)

```php
Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdminSession::class)->group(function () {
    Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{phone}', [AdminCustomerController::class, 'show'])->name('customers.show');
});
```

### 9.1 `app/Http/Controllers/Admin/CustomerController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = Order::query()
            ->select([
                'customer_phone',
                DB::raw('MAX(customer_name) as customer_name'),
                DB::raw('MAX(customer_email) as customer_email'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(grand_total) as total_spent'),
                DB::raw('MAX(created_at) as last_order_at'),
            ])
            ->groupBy('customer_phone')
            ->orderByDesc('last_order_at')
            ->paginate(10);

        return view('admin.customers.index', compact('customers'));
    }

    public function show(string $phone): View
    {
        $orders = Order::query()
            ->where('customer_phone', $phone)
            ->withCount('items')
            ->latest()
            ->get();

        abort_if($orders->isEmpty(), 404);

        $customer = $orders->first();
        $stats = [
            'orders' => $orders->count(),
            'spent' => $orders->sum('grand_total'),
            'unpaid' => $orders->where('payment_status', '!=', 'paid')->count(),
            'latest' => $orders->max('created_at'),
        ];

        return view('admin.customers.show', compact('customer', 'orders', 'stats'));
    }
}
```

### 9.2 `resources/views/admin/customers/index.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Customers - CEC Electronic Admin')
@section('heading', 'Customers')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Customer management</h2>
            <p class="muted" style="margin:6px 0 0">View order history, total spend, contact details, and payment risk by customer phone.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.orders.index') }}">Orders</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Orders</th>
                    <th>Total spent</th>
                    <th>Latest order</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td><strong>{{ $customer->customer_name }}</strong></td>
                        <td>
                            {{ $customer->customer_phone }}
                            <div class="muted">{{ $customer->customer_email ?: 'No email' }}</div>
                        </td>
                        <td>{{ number_format($customer->orders_count) }}</td>
                        <td>${{ number_format($customer->total_spent, 2) }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($customer->last_order_at)->format('M d, Y') }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="{{ route('admin.customers.show', $customer->customer_phone) }}">Open</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">No customers yet. Customer records are created from checkout orders.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $customers->links() }}</div>
@endsection
```

### 9.3 `resources/views/admin/customers/show.blade.php`

```blade
@extends('admin.layout')

@section('title', $customer->customer_name.' - CEC Electronic Admin')
@section('heading', 'Customer Profile')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">{{ $customer->customer_name }}</h2>
            <p class="muted" style="margin:6px 0 0">{{ $customer->customer_phone }} - {{ $customer->customer_email ?: 'No email' }}</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.customers.index') }}">Back to customers</a>
    </div>

    <section class="stats">
        <div class="panel stat">
            <span>Total orders</span>
            <strong>{{ number_format($stats['orders']) }}</strong>
        </div>
        <div class="panel stat">
            <span>Total spent</span>
            <strong>${{ number_format($stats['spent'], 2) }}</strong>
        </div>
        <div class="panel stat">
            <span>Unpaid orders</span>
            <strong>{{ number_format($stats['unpaid']) }}</strong>
        </div>
        <div class="panel stat">
            <span>Latest order</span>
            <strong style="font-size:20px">{{ $stats['latest']?->format('M d, Y') }}</strong>
        </div>
    </section>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>
                            <strong>{{ $order->order_number }}</strong>
                            <div class="muted">{{ $order->created_at->format('M d, Y h:i A') }}</div>
                        </td>
                        <td><span class="status {{ $order->status === 'pending' ? 'warning' : '' }}">{{ ucfirst($order->status) }}</span></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</td>
                        <td>{{ $order->items_count }}</td>
                        <td>${{ number_format($order->grand_total, 2) }}</td>
                        <td><div class="actions"><a class="btn secondary" href="{{ route('admin.orders.show', $order) }}">Open order</a></div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
```

---

## 10. Delivery Zone

- Fields: name, city, province, delivery fee, free-delivery minimum, estimated days, active flag.
- Zones are assigned to orders on the order detail page.

**Routes** (`routes/web.php`)

```php
Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdminSession::class)->group(function () {
    Route::resource('delivery-zones', AdminDeliveryZoneController::class)->except(['show']);
});
```

### 10.1 `app/Http/Controllers/Admin/DeliveryZoneController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryZoneController extends Controller
{
    public function index(): View
    {
        $zones = DeliveryZone::latest()->paginate(10);

        return view('admin.delivery-zones.index', compact('zones'));
    }

    public function create(): View
    {
        $zone = new DeliveryZone(['is_active' => true, 'estimated_days' => 1]);

        return view('admin.delivery-zones.create', compact('zone'));
    }

    public function store(Request $request): RedirectResponse
    {
        DeliveryZone::create($this->validatedData($request));

        return redirect()->route('admin.delivery-zones.index')->with('status', 'Delivery zone created.');
    }

    public function edit(DeliveryZone $deliveryZone): View
    {
        $zone = $deliveryZone;

        return view('admin.delivery-zones.edit', compact('zone'));
    }

    public function update(Request $request, DeliveryZone $deliveryZone): RedirectResponse
    {
        $deliveryZone->update($this->validatedData($request));

        return redirect()->route('admin.delivery-zones.index')->with('status', 'Delivery zone updated.');
    }

    public function destroy(DeliveryZone $deliveryZone): RedirectResponse
    {
        $deliveryZone->delete();

        return redirect()->route('admin.delivery-zones.index')->with('status', 'Delivery zone deleted.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'delivery_fee' => ['nullable', 'integer', 'min:0'],
            'free_delivery_minimum' => ['nullable', 'integer', 'min:0'],
            'estimated_days' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['delivery_fee' => 0, 'estimated_days' => 1, 'is_active' => false];
    }
}
```

### 10.2 `app/Models/DeliveryZone.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'province',
        'delivery_fee',
        'free_delivery_minimum',
        'estimated_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
```

### 10.3 `resources/views/admin/delivery-zones/index.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Delivery - CEC Electronic Admin')
@section('heading', 'Delivery')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Delivery</h2>
            <p class="muted" style="margin:6px 0 0">Configure delivery areas, lead time, and availability.</p>
        </div>
        <a class="btn" href="{{ route('admin.delivery-zones.create') }}">Add New Delivery</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>Zone</th><th>Location</th><th>Estimate</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
                @forelse($zones as $zone)
                    <tr>
                        <td><strong>{{ $zone->name }}</strong></td>
                        <td>{{ $zone->city ?: 'Any city' }}<div class="muted">{{ $zone->province }}</div></td>
                        <td>{{ $zone->estimated_days }} day(s)</td>
                        <td><span class="status">{{ $zone->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td><div class="actions"><a class="btn secondary" href="{{ route('admin.delivery-zones.edit', $zone) }}">Edit</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No delivery entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $zones->links() }}</div>
@endsection
```

### 10.4 `resources/views/admin/delivery-zones/create.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Add New Delivery - CEC Electronic Admin')
@section('heading', 'Add New Delivery')

@section('content')
    <form class="panel form" action="{{ route('admin.delivery-zones.store') }}" method="post">
        @include('admin.delivery-zones._form', ['buttonText' => 'Create delivery'])
    </form>
@endsection
```

### 10.5 `resources/views/admin/delivery-zones/edit.blade.php`

```blade
@extends('admin.layout')

@section('title', 'Edit Delivery - CEC Electronic Admin')
@section('heading', 'Edit Delivery')

@section('content')
    <form class="panel form" action="{{ route('admin.delivery-zones.update', $zone) }}" method="post">
        @method('PUT')
        @include('admin.delivery-zones._form', ['buttonText' => 'Save changes'])
    </form>
@endsection
```

### 10.6 `resources/views/admin/delivery-zones/_form.blade.php`

```blade
@csrf

<div class="form-grid">
    <div class="field">
        <label for="name">Zone name</label>
        <input id="name" name="name" value="{{ old('name', $zone->name) }}" required>
    </div>
    <div class="field">
        <label for="city">City</label>
        <input id="city" name="city" value="{{ old('city', $zone->city) }}">
    </div>
    <div class="field">
        <label for="province">Province</label>
        <input id="province" name="province" value="{{ old('province', $zone->province) }}">
    </div>
    <div class="field">
        <label for="delivery_fee">Delivery fee USD</label>
        <input id="delivery_fee" name="delivery_fee" type="number" min="0" value="{{ old('delivery_fee', $zone->delivery_fee ?? 0) }}">
    </div>
    <div class="field">
        <label for="free_delivery_minimum">Free delivery minimum</label>
        <input id="free_delivery_minimum" name="free_delivery_minimum" type="number" min="0" value="{{ old('free_delivery_minimum', $zone->free_delivery_minimum) }}">
    </div>
    <div class="field">
        <label for="estimated_days">Estimated days</label>
        <input id="estimated_days" name="estimated_days" type="number" min="1" value="{{ old('estimated_days', $zone->estimated_days ?? 1) }}">
    </div>
    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $zone->is_active ?? true))> Active</label>
</div>

<div style="display:flex;gap:10px;margin-top:18px">
    <button class="btn" type="submit">{{ $buttonText }}</button>
    <a class="btn secondary" href="{{ route('admin.delivery-zones.index') }}">Cancel</a>
</div>
```
