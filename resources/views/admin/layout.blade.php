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
        .nav-group summary{padding:10px 12px;border-radius:7px;font-weight:700;display:flex;align-items:center;gap:11px;border-left:3px solid transparent;color:#c9defa;cursor:pointer;list-style:none}
        .nav-group summary::-webkit-details-marker{display:none}
        .nav-group summary .nav-icon{width:18px;text-align:center;font-size:15px;flex:0 0 auto}
        .nav-group summary:hover{background:rgba(255,255,255,.08);color:#fff}
        .nav-group[open] summary{color:#fff}
        .nav-caret{margin-left:auto;font-size:11px;transition:transform .12s ease}
        .nav-group[open] .nav-caret{transform:rotate(180deg)}
        .nav-sub{display:grid;gap:4px;margin-top:4px;padding-left:29px}
        .nav-sep{display:block;height:1px;margin:8px 12px;background:rgba(255,255,255,.14)}
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
        .pay-bell{position:relative}
        .pay-bell-btn{position:relative;width:40px;height:40px;border-radius:50%;border:1px solid var(--line);background:#f8fbff;cursor:pointer;font-size:18px;display:grid;place-items:center}
        .pay-bell-btn.ring{animation:bell-ring .9s ease 2}
        @keyframes bell-ring{0%,100%{transform:rotate(0)}20%{transform:rotate(-16deg)}40%{transform:rotate(14deg)}60%{transform:rotate(-10deg)}80%{transform:rotate(6deg)}}
        .pay-bell-count{position:absolute;top:-4px;right:-4px;min-width:19px;height:19px;padding:0 5px;border-radius:999px;background:var(--danger);color:#fff;font-size:11px;font-weight:900;display:grid;place-items:center;border:2px solid #fff}
        .pay-bell-count[hidden]{display:none}
        .pay-bell-menu{position:absolute;right:0;top:48px;width:330px;max-width:calc(100vw - 32px);background:#fff;border:1px solid var(--line);border-radius:10px;box-shadow:0 18px 40px rgba(16,24,40,.16);z-index:30;overflow:hidden}
        .pay-bell-menu[hidden]{display:none}
        .pay-bell-head{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-bottom:1px solid var(--line);font-weight:800}
        .pay-bell-head a{color:var(--brand);font-size:12.5px}
        .pay-bell-list{max-height:340px;overflow:auto}
        .pay-bell-item{display:flex;justify-content:space-between;gap:10px;padding:11px 14px;border-bottom:1px solid var(--line)}
        .pay-bell-item:last-child{border-bottom:0}
        .pay-bell-item:hover{background:#f8fbff}
        .pay-bell-item small{display:block;color:var(--muted);margin-top:2px}
        .pay-bell-empty{padding:16px 14px;color:var(--muted);margin:0}
        .pay-toasts{position:fixed;right:18px;bottom:18px;display:grid;gap:10px;z-index:9999;max-width:calc(100vw - 32px)}
        .pay-toast{display:flex;align-items:center;gap:12px;width:340px;max-width:100%;padding:14px;border-radius:10px;background:#fff;border:1px solid #b7ebc9;border-left:5px solid #087443;box-shadow:0 18px 40px rgba(16,24,40,.18);animation:toast-in .25s ease}
        .pay-toast .icon{width:36px;height:36px;border-radius:50%;background:#087443;color:#fff;display:grid;place-items:center;font-size:17px;flex:0 0 auto}
        .pay-toast strong{display:block}
        .pay-toast span{font-size:12.5px;color:var(--muted)}
        .pay-toast button{margin-left:auto;border:0;background:none;font-size:18px;color:var(--muted);cursor:pointer;align-self:flex-start}
        @keyframes toast-in{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
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
                <span class="nav-label">System</span>
                <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])><span class="nav-icon">&#128202;</span>Dashboard</a>
                <a href="{{ route('admin.products.index') }}" @class(['active' => request()->routeIs('admin.products.*', 'admin.brands.*')])><span class="nav-icon">&#128421;</span>Product</a>
                <a href="{{ route('admin.categories.index') }}" @class(['active' => request()->routeIs('admin.categories.*')])><span class="nav-icon">&#128194;</span>Category</a>
                <span class="nav-sep"></span>
                <a href="{{ route('admin.suppliers.index') }}" @class(['active' => request()->routeIs('admin.suppliers.*')])><span class="nav-icon">&#128666;</span>Supplier</a>
                <a href="{{ route('admin.purchase-orders.index') }}" @class(['active' => request()->routeIs('admin.purchase-orders.*')])><span class="nav-icon">&#128221;</span>Purchase Order</a>
                <a href="{{ route('admin.bills.index') }}" @class(['active' => request()->routeIs('admin.bills.*')])><span class="nav-icon">&#128179;</span>Bill</a>
                <span class="nav-sep"></span>
                <a href="{{ route('admin.customers.index') }}" @class(['active' => request()->routeIs('admin.customers.*')])><span class="nav-icon">&#128101;</span>Customer</a>
                <a href="{{ route('admin.orders.index') }}" @class(['active' => request()->routeIs('admin.orders.*', 'admin.delivery-zones.*')])><span class="nav-icon">&#129534;</span>Sale Order</a>
                <a href="{{ route('admin.invoices.index') }}" @class(['active' => request()->routeIs('admin.invoices.*')])><span class="nav-icon">&#128196;</span>Invoice</a>
                <span class="nav-sep"></span>
                <a href="{{ route('admin.expenses.index') }}" @class(['active' => request()->routeIs('admin.expenses.*')])><span class="nav-icon">&#128184;</span>Expenses</a>
                <span class="nav-sep"></span>
                <details class="nav-group" @if(request()->routeIs('admin.reports.*')) open @endif>
                    <summary><span class="nav-icon">&#128200;</span>Report<span class="nav-caret">&#9662;</span></summary>
                    <div class="nav-sub">
                        @foreach(['profit-loss' => 'Profit & Loss', 'products' => 'Product Revenue', 'customers' => 'Customer', 'suppliers' => 'Supplier'] as $reportRoute => $reportLabel)
                            <a href="{{ route('admin.reports.'.$reportRoute) }}" @class(['active' => request()->routeIs('admin.reports.'.$reportRoute)])>{{ $reportLabel }}</a>
                        @endforeach
                    </div>
                </details>
            </nav>
        </aside>

        <div class="main">
            <header class="top">
                <h1>@yield('heading', 'Admin Panel')</h1>
                <div style="display:flex;gap:10px;align-items:center">
                    <div class="pay-bell" id="payBell">
                        <button class="pay-bell-btn" id="payBellBtn" type="button" aria-label="Payment alerts" title="Payment alerts">
                            &#128276;
                            <span class="pay-bell-count" id="payBellCount" hidden>0</span>
                        </button>
                        <div class="pay-bell-menu" id="payBellMenu" hidden>
                            <div class="pay-bell-head">
                                <span>New payments</span>
                                <a href="{{ route('admin.orders.index') }}">View orders</a>
                            </div>
                            <div class="pay-bell-list" id="payBellList">
                                <p class="pay-bell-empty">No new payments.</p>
                            </div>
                        </div>
                    </div>
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

                @foreach(['purchase_order', 'bill', 'invoice', 'supplier'] as $noticeField)
                    @error($noticeField)
                        <div class="notice" style="background:#fee4e2;color:#b42318;border-color:#fecdca">{{ $message }}</div>
                    @enderror
                @endforeach

                @yield('content')
            </main>
        </div>
    </div>

    @include('partials.loading-overlay')

    <div class="pay-toasts" id="payToasts" aria-live="polite"></div>

    <script>
        (function () {
            var endpoint = @json(route('admin.payment-notifications'));
            var btn = document.getElementById('payBellBtn');
            var menu = document.getElementById('payBellMenu');
            var countEl = document.getElementById('payBellCount');
            var list = document.getElementById('payBellList');
            var toasts = document.getElementById('payToasts');
            var known = null; // ids already shown; null until the first load

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
                });
            }

            function beep() {
                try {
                    var ctx = new (window.AudioContext || window.webkitAudioContext)();
                    [880, 1320].forEach(function (freq, i) {
                        var o = ctx.createOscillator(), g = ctx.createGain();
                        o.frequency.value = freq;
                        o.connect(g); g.connect(ctx.destination);
                        var t = ctx.currentTime + i * 0.18;
                        g.gain.setValueAtTime(0.0001, t);
                        g.gain.exponentialRampToValueAtTime(0.25, t + 0.02);
                        g.gain.exponentialRampToValueAtTime(0.0001, t + 0.16);
                        o.start(t); o.stop(t + 0.17);
                    });
                } catch (e) {}
            }

            function toast(o) {
                var el = document.createElement('a');
                el.className = 'pay-toast';
                el.href = o.url;
                el.innerHTML = '<span class="icon">&#10003;</span><div><strong>Payment received: $' + esc(o.grand_total) + '</strong>'
                    + '<span>' + esc(o.order_number) + ' &middot; ' + esc(o.customer_name) + ' &middot; ' + esc(o.payment_method) + '</span></div>'
                    + '<button type="button" aria-label="Dismiss">&times;</button>';
                el.querySelector('button').addEventListener('click', function (e) { e.preventDefault(); el.remove(); });
                toasts.appendChild(el);
                setTimeout(function () { el.remove(); }, 15000);
            }

            function render(data) {
                countEl.hidden = data.count < 1;
                countEl.textContent = data.count > 99 ? '99+' : data.count;
                document.title = (data.count > 0 ? '(' + data.count + ') ' : '') + document.title.replace(/^\(\d+\+?\)\s/, '');

                list.innerHTML = data.orders.length ? data.orders.map(function (o) {
                    return '<a class="pay-bell-item" href="' + esc(o.url) + '"><div><strong>' + esc(o.order_number) + '</strong>'
                        + '<small>' + esc(o.customer_name) + ' &middot; ' + esc(o.payment_method) + '</small></div>'
                        + '<div style="text-align:right"><strong>$' + esc(o.grand_total) + '</strong><small>' + esc(o.paid_at) + '</small></div></a>';
                }).join('') : '<p class="pay-bell-empty">No new payments.</p>';

                var fresh = known === null ? [] : data.orders.filter(function (o) { return known.indexOf(o.id) === -1; });
                known = (known || []).concat(data.orders.map(function (o) { return o.id; }));

                if (fresh.length) {
                    fresh.reverse().forEach(toast);
                    beep();
                    btn.classList.remove('ring'); void btn.offsetWidth; btn.classList.add('ring');
                }
            }

            function poll() {
                fetch(endpoint, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) { if (data) render(data); })
                    .catch(function () {});
            }

            btn.addEventListener('click', function (e) { e.stopPropagation(); menu.hidden = !menu.hidden; });
            document.addEventListener('click', function (e) { if (!menu.contains(e.target)) menu.hidden = true; });

            poll();
            setInterval(function () { if (!document.hidden) poll(); }, 5000);
            document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
        })();
    </script>

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
