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
