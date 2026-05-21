<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Admin - CEC Electronic')</title>
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
        .sidebar{background:#0b315f;color:#d9e7f7;padding:22px 16px;position:sticky;top:0;height:100vh}
        .brand{display:flex;align-items:center;gap:10px;color:#fff;font-size:20px;font-weight:900;margin-bottom:26px}
        .brand-mark{width:42px;height:42px;border-radius:8px;background:#fff;color:var(--brand);display:grid;place-items:center;overflow:hidden}
        .brand-mark img{width:100%;height:100%;object-fit:contain;padding:3px}
        .nav{display:grid;gap:6px}
        .nav a{padding:11px 12px;border-radius:7px;font-weight:700}
        .nav a:hover,.nav a.active{background:rgba(255,255,255,.12);color:#fff}
        .main{min-width:0}
        .top{height:70px;background:#fff;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:10}
        .top h1{font-size:22px;margin:0}
        .content{padding:24px}
        .panel{background:var(--panel);border:1px solid var(--line);border-radius:8px}
        .btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:7px;padding:10px 14px;background:var(--brand);color:#fff;font-weight:800;cursor:pointer}
        .btn.secondary{background:#eef5ff;color:var(--brand)}
        .btn.danger{background:var(--danger)}
        .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}
        .stat{padding:18px}
        .stat span{color:var(--muted);display:block;margin-bottom:8px}
        .stat strong{font-size:28px}
        .toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
        table{width:100%;border-collapse:collapse;background:#fff;border:1px solid var(--line);border-radius:8px;overflow:hidden}
        th,td{padding:13px 14px;border-bottom:1px solid var(--line);text-align:left;vertical-align:middle}
        th{background:#f8fbff;color:#344054;font-size:12px;text-transform:uppercase;letter-spacing:.04em}
        tr:last-child td{border-bottom:0}
        .product-cell{display:flex;align-items:center;gap:12px}
        .thumb{width:58px;height:46px;border-radius:6px;background:#edf2f7;object-fit:cover}
        .muted{color:var(--muted)}
        .status{padding:5px 8px;border-radius:999px;background:#e7f8ef;color:#087443;font-size:12px;font-weight:800}
        .status.warning{background:#fff3cf;color:#8a5a00}
        .status.danger{background:#fee4e2;color:#b42318}
        .status.info{background:#eef5ff;color:#004b93}
        .status.unread{background:#fff3cf;color:#8a5a00}
        .actions{display:flex;gap:8px;justify-content:flex-end}
        .split{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px}
        .mini-list{display:grid;gap:10px;padding:16px}
        .mini-item{display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--line);padding-bottom:10px}
        .mini-item:last-child{border-bottom:0;padding-bottom:0}
        .notice{padding:12px 14px;background:#e7f8ef;color:#087443;border:1px solid #b7ebc9;border-radius:8px;margin-bottom:14px;font-weight:700}
        .form{max-width:840px;padding:20px}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .field{display:grid;gap:7px}
        .field.full{grid-column:1 / -1}
        label{font-weight:800}
        input,textarea,select{border:1px solid var(--line);border-radius:7px;padding:11px 12px;background:#fff;color:var(--ink);width:100%}
        textarea{min-height:150px;resize:vertical}
        .error{color:var(--danger);font-size:12px}
        .pagination{margin-top:16px}
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
                <span>Admin Panel</span>
            </a>
            <nav class="nav">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a href="{{ route('admin.products.index') }}">Products</a>
                <a href="{{ route('admin.categories.index') }}">Categories</a>
                <a href="{{ route('admin.suppliers.index') }}">Suppliers</a>
                <a href="{{ route('admin.orders.index') }}">Orders</a>
                <a href="{{ route('admin.customers.index') }}">Customers</a>
                <a href="{{ route('admin.delivery-zones.index') }}">Delivery Zones</a>
                <a href="{{ route('admin.delivery-providers.index') }}">Delivery Providers</a>
                <a href="{{ route('admin.products.create') }}">Add Product</a>
                <a href="/">View Store</a>
            </nav>
        </aside>

        <div class="main">
            <header class="top">
                <h1>@yield('heading', 'Admin Panel')</h1>
                <div style="display:flex;gap:8px;align-items:center">
                    <a class="btn secondary" href="/">Storefront</a>
                    <form action="{{ route('admin.logout') }}" method="post">
                        @csrf
                        <button class="btn danger" type="submit">Logout</button>
                    </form>
                </div>
            </header>

            <main class="content">
                @if(session('status'))
                    <div class="notice">{{ session('status') }}</div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
