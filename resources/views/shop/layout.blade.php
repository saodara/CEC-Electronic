<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'CEC Electronic')</title>
    <style>
        :root{
            --brand:#0057a8;
            --brand-dark:#063a74;
            --accent:#f6b300;
            --accent-soft:#fff3cf;
            --danger:#d92d20;
            --success:#087443;
            --ink:#121926;
            --muted:#667085;
            --line:#dde5f0;
            --soft:#f3f6fb;
            --panel:#fff;
        }
        *{box-sizing:border-box}
        html{scroll-behavior:smooth;scroll-padding-top:132px}
        body{margin:0;background:#f5f7fb;color:var(--ink);font-family:Inter,Segoe UI,Arial,sans-serif;font-size:14px}
        a{color:inherit;text-decoration:none}
        img{display:block;max-width:100%}
        button,input,select,textarea{font:inherit}
        input,select,textarea{width:100%;border:1px solid var(--line);border-radius:7px;padding:10px 11px;background:#fff;color:var(--ink);margin-top:6px}
        label{font-weight:700;color:#344054}
        .topbar{background:#082f5f;color:#e7f0fb;font-size:12px}
        .topbar-inner,.header-inner,.nav-inner,.wrap{max-width:1260px;margin:0 auto;padding:0 18px}
        .topbar-inner{min-height:34px;display:flex;align-items:center;justify-content:space-between;gap:18px}
        .topbar-left,.topbar-right{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
        .header{background:#fff;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:20;box-shadow:0 4px 18px rgba(16,24,40,.04)}
        .header-inner{min-height:78px;display:grid;grid-template-columns:230px minmax(280px,1fr) 328px;align-items:center;gap:16px}
        .logo{display:flex;align-items:center;gap:11px;color:var(--brand);min-width:0}
        .logo-mark{width:54px;height:54px;border-radius:8px;background:#fff;border:1px solid var(--line);display:grid;place-items:center;overflow:hidden}
        .logo-mark img{width:100%;height:100%;object-fit:contain;padding:3px}
        .logo-text strong{display:block;font-size:21px;line-height:1}
        .logo-text span{display:block;color:var(--muted);font-size:12px;margin-top:5px}
        .search{display:grid;grid-template-columns:150px 1fr 54px;border:2px solid var(--brand);border-radius:6px;overflow:hidden;background:#fff}
        .search select,.search input,.search button{border:0;border-radius:0;margin:0;min-width:0}
        .search select{background:#eef5ff;border-right:1px solid var(--line);color:#344054}
        .search input{outline:0;padding:13px 14px}
        .search button{background:var(--brand);color:#fff;font-weight:900;cursor:pointer}
        .quick-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px}
        .quick{display:flex;align-items:center;gap:8px;padding:8px 9px;border:1px solid var(--line);border-radius:6px;background:#fff;min-height:42px}
        .quick:hover{border-color:#b8c7dc;background:#f8fbff}
        .quick-icon{width:25px;height:25px;border-radius:5px;background:#eef5ff;color:var(--brand);display:grid;place-items:center;font-weight:900;font-size:11px;flex:0 0 auto;overflow:hidden}
        .quick-icon img{width:100%;height:100%;object-fit:cover;display:block}
        .quick strong{display:block;font-size:13px;white-space:nowrap}
        .quick span span{display:block;color:var(--muted);font-size:11px;white-space:nowrap}
        .nav{background:var(--brand);color:#fff}
        .nav-inner{display:flex;align-items:center;gap:2px;overflow:auto}
        .nav a{padding:13px 15px;white-space:nowrap;font-weight:750}
        .nav a:hover,.nav a.active{background:rgba(255,255,255,.16)}
        .wrap{padding-top:16px;padding-bottom:34px}
        .panel{background:var(--panel);border:1px solid var(--line);border-radius:6px}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:6px;padding:10px 14px;background:var(--brand);color:#fff;font-weight:850;cursor:pointer;min-height:40px}
        .btn.secondary{background:#eef5ff;color:var(--brand)}
        .btn.accent{background:var(--accent);color:#1f2937}
        .icon-btn{width:42px;height:40px;border-radius:6px;border:1px solid var(--line);background:#fff;color:var(--brand);font-size:18px;cursor:pointer}
        .hero{display:grid;grid-template-columns:248px minmax(0,1fr) 276px;gap:12px;margin-bottom:14px}
        .category-menu{padding:0;overflow:hidden}
        .category-menu h3,.filter h3{margin:0;padding:13px 14px;background:#f8fbff;border-bottom:1px solid var(--line);font-size:15px}
        .category-link{display:flex;align-items:center;justify-content:space-between;padding:11px 14px;border-bottom:1px solid #eef2f7;color:#26364f;font-weight:700}
        .category-link:last-child{border-bottom:0}
        .category-link:hover{background:#eef5ff;color:var(--brand)}
        .hero-main{min-height:318px;padding:30px;background:linear-gradient(110deg,#07376f 0%,#0057a8 56%,#e8f4ff 56%);color:#fff;display:grid;grid-template-columns:minmax(0,1fr) 300px;align-items:center;overflow:hidden;position:relative}
        .hero-main h1{margin:0;font-size:34px;line-height:1.12;letter-spacing:0}
        .hero-main p{margin:12px 0 20px;max-width:530px;color:#e8f4ff;font-size:15px;line-height:1.6}
        .hero-points{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px}
        .hero-points span{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.24);border-radius:999px;padding:7px 10px;font-weight:750;font-size:12px}
        .device-scene{display:grid;gap:14px;align-items:end}
        .laptop-art{height:190px;border-radius:16px;background:linear-gradient(145deg,#111827,#3b4b62);box-shadow:0 28px 46px rgba(0,0,0,.28);padding:12px;transform:rotate(-3deg)}
        .laptop-screen{height:100%;border-radius:10px;background:linear-gradient(135deg,#ffffff,#d9e8ff);position:relative;overflow:hidden}
        .laptop-screen:before{content:"";position:absolute;inset:24px;background:linear-gradient(135deg,#0057a8,#19b2e8);border-radius:10px}
        .laptop-base{height:14px;width:84%;margin:-3px auto 0;border-radius:0 0 18px 18px;background:#202938}
        .deal-stack{display:grid;gap:12px}
        .deal{padding:16px;min-height:153px;overflow:hidden;position:relative}
        .deal small{color:var(--muted);font-weight:800;text-transform:uppercase}
        .deal b{display:block;color:var(--brand);font-size:21px;margin:5px 0}
        .deal p{margin:0;color:var(--muted);line-height:1.55}
        .service-row{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:14px 0}
        .service{padding:13px;display:flex;gap:12px;align-items:center}
        .service-icon{width:38px;height:38px;border-radius:8px;background:var(--accent-soft);display:grid;place-items:center;font-weight:900;color:#8a5a00;flex:0 0 auto;overflow:hidden}
        .service-icon img{width:100%;height:100%;object-fit:cover;display:block}
        .service strong{display:block}
        .service span{display:block;color:var(--muted);font-size:12px;margin-top:3px;line-height:1.35}
        .section-head{display:flex;align-items:end;justify-content:space-between;gap:16px;margin:20px 0 11px}
        .section-head h2{margin:0;font-size:21px}
        .section-head p{margin:6px 0 0;color:var(--muted)}
        .brand-strip{display:flex;gap:8px;overflow:auto;padding:12px;background:#fff;border:1px solid var(--line);border-radius:8px;margin-bottom:16px}
        .brand-pill{padding:8px 12px;border:1px solid var(--line);border-radius:999px;color:#475467;background:#fff;white-space:nowrap;font-weight:750}
        .brand-showcase{padding:16px;margin:16px 0;background:#fff;border:1px solid var(--line);border-radius:6px;overflow:hidden}
        .brand-showcase-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:12px}
        .brand-showcase-head h2{margin:0;font-size:20px}
        .brand-showcase-head p{margin:4px 0 0;color:var(--muted)}
        .brand-controls{display:flex;gap:8px;align-items:center}
        .brand-control{width:36px;height:36px;border-radius:7px;border:1px solid var(--line);background:#fff;color:var(--brand);font-weight:950;cursor:pointer}
        .brand-slider-wrap{position:relative;overflow:hidden}
        .brand-slider-wrap:before,.brand-slider-wrap:after{content:"";position:absolute;top:0;bottom:0;width:58px;z-index:2;pointer-events:none}
        .brand-slider-wrap:before{left:0;background:linear-gradient(90deg,#fff,rgba(255,255,255,0))}
        .brand-slider-wrap:after{right:0;background:linear-gradient(270deg,#fff,rgba(255,255,255,0))}
        .brand-slider{display:flex;gap:12px;overflow-x:auto;scroll-behavior:smooth;scrollbar-width:none;padding:2px}
        .brand-slider::-webkit-scrollbar{display:none}
        .brand-slider.is-marquee{width:max-content;overflow:visible;animation:brand-marquee 26s linear infinite}
        .brand-slider-wrap:hover .brand-slider.is-marquee{animation-play-state:paused}
        .brand-slide{min-width:174px;height:92px;border:1px solid var(--line);border-radius:8px;background:#fff;display:flex;align-items:center;justify-content:center;padding:14px;transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease}
        .brand-slide:hover{border-color:#b8c7dc;box-shadow:0 10px 22px rgba(16,24,40,.08);transform:translateY(-1px)}
        .brand-slide img{max-width:126px;max-height:52px;object-fit:contain}
        .brand-slide .brand-initials{width:62px;height:48px}
        @keyframes brand-marquee{
            from{transform:translateX(0)}
            to{transform:translateX(calc(-50% - 6px))}
        }
        .breadcrumb{display:flex;gap:8px;align-items:center;color:var(--muted);font-size:13px;margin-bottom:12px}
        .breadcrumb a{color:var(--brand);font-weight:750}
        .brand-page-hero{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:18px;align-items:center;padding:22px;margin-bottom:16px}
        .brand-page-hero h1{margin:0;font-size:30px;line-height:1.15}
        .brand-page-hero p{margin:8px 0 0;color:var(--muted);line-height:1.6}
        .brand-page-stats{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .brand-page-stats div{padding:14px;background:#f8fbff;border:1px solid var(--line);border-radius:7px}
        .brand-page-stats strong{display:block;font-size:24px;color:var(--brand)}
        .brand-page-stats span{color:var(--muted);font-size:12px}
        .brand-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px}
        .brand-card{min-height:138px;padding:16px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;text-align:center;transition:.18s ease}
        .brand-card:hover{box-shadow:0 14px 30px rgba(16,24,40,.11);transform:translateY(-2px);border-color:#b8c7dc}
        .brand-logo-box{width:100%;height:58px;display:grid;place-items:center}
        .brand-logo-box img{max-width:110px;max-height:50px;object-fit:contain}
        .brand-initials{width:62px;height:48px;border-radius:8px;background:#eef5ff;color:var(--brand);display:grid;place-items:center;font-weight:950;font-size:18px}
        .brand-card strong{font-size:15px}
        .brand-card span{color:var(--muted);font-size:12px}
        .category-tiles{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px}
        .category-tile{padding:15px;min-height:120px;display:flex;flex-direction:column;gap:10px}
        .category-tile:hover{border-color:#b8c7dc;box-shadow:0 10px 22px rgba(16,24,40,.08)}
        .category-tile-img{width:100%;height:84px;border-radius:6px;overflow:hidden;background:#f3f6fb;flex:0 0 auto}
        .category-tile-img img{width:100%;height:100%;object-fit:cover;display:block}
        .category-tile strong{font-size:16px;color:var(--brand)}
        .category-tile span{color:var(--muted);line-height:1.45;font-size:12px}
        .catalog{display:grid;grid-template-columns:256px minmax(0,1fr);gap:14px}
        .filter{align-self:start;position:sticky;top:118px;padding:0;overflow:hidden}
        .filter-group{border-top:1px solid var(--line);padding:14px 8px}
        .filter-title{display:flex;justify-content:space-between;font-weight:850;margin-bottom:10px}
        .checks{display:grid;gap:8px;color:#475467}
        .checks label{display:flex;align-items:center;gap:8px;font-weight:600}
        .checks input{width:auto;margin:0}
        .toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;padding:11px 14px}
        .toolbar label{display:flex;align-items:center;gap:8px}
        .toolbar select{width:auto;margin:0}
        .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
        .catalog .grid{grid-template-columns:repeat(3,minmax(0,1fr))}
        .product-card{background:#fff;border:1px solid var(--line);border-radius:6px;overflow:hidden;display:flex;flex-direction:column;transition:.18s ease;min-width:0}
        .product-card:hover{box-shadow:0 12px 26px rgba(16,24,40,.1);transform:translateY(-2px);border-color:#c8d5e5}
        .product-media{height:176px;background:#fff;display:grid;place-items:center;padding:14px;position:relative;border-bottom:1px solid #eef2f7}
        .product-media img{max-height:146px;object-fit:contain;border-radius:4px}
        .badge{position:absolute;left:10px;top:10px;background:var(--danger);color:#fff;border-radius:4px;padding:5px 7px;font-size:11px;font-weight:900}
        .product-body{padding:11px;display:flex;flex-direction:column;gap:7px;flex:1}
        .price{font-size:19px;font-weight:950;color:#111827}
        .old-price{color:#98a2b3;text-decoration:line-through;font-size:13px;margin-left:6px}
        .sku{font-size:12px;color:#667085}
        .stock{color:var(--success);font-size:12px;font-weight:850;text-transform:uppercase}
        .product-title{font-weight:850;line-height:1.35;min-height:38px}
        .spec{color:#667085;line-height:1.45;font-size:12px;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden;margin:0}
        .card-meta{display:flex;align-items:center;justify-content:space-between;gap:8px}
        .card-actions{display:grid;grid-template-columns:1fr 42px;gap:8px;margin-top:auto}
        .promo-band{display:grid;grid-template-columns:1.1fr .9fr;gap:16px;align-items:center;padding:20px;background:#fff;border:1px solid var(--line);border-radius:6px;margin-top:22px}
        .promo-band h2{margin:0 0 8px;font-size:22px}
        .promo-band p{margin:0;color:var(--muted);line-height:1.6}
        .store-hours{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
        .store-hours div{padding:12px;background:#f8fbff;border:1px solid var(--line);border-radius:7px}
        .detail{display:grid;grid-template-columns:minmax(0,1fr) 430px;gap:18px}
        .detail-media{padding:24px;display:grid;place-items:center;min-height:430px;background:#fff}
        .detail-media img{max-height:360px;object-fit:contain}
        .detail-info{padding:22px}
        .detail-info h1{font-size:28px;line-height:1.2;margin:0 0 10px}
        .spec-table{display:grid;gap:8px;margin:16px 0}
        .spec-row{display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--line);padding-bottom:8px;color:var(--muted)}
        .spec-row strong{color:var(--ink)}
        .checkout{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:16px}
        .auth-shell{display:grid;grid-template-columns:minmax(0,1fr) 420px;gap:18px;align-items:stretch}
        .auth-hero{padding:28px;background:linear-gradient(135deg,#07376f,#0057a8);color:#fff}
        .auth-hero h1{margin:0;font-size:30px;line-height:1.15}
        .auth-hero p{color:#e8f4ff;line-height:1.6}
        .auth-card{padding:22px}
        .auth-card h2{margin:0 0 14px}
        .auth-actions{display:flex;gap:10px;align-items:center;justify-content:space-between;margin-top:16px}
        .modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.54);display:none;align-items:center;justify-content:center;padding:18px;z-index:80}
        .modal-backdrop.is-open{display:flex}
        .payment-modal{width:min(460px,100%);background:#fff;border-radius:8px;border:1px solid var(--line);box-shadow:0 24px 60px rgba(16,24,40,.24);overflow:hidden}
        .payment-modal-head{padding:16px 18px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;gap:12px;align-items:center}
        .payment-modal-head h3{margin:0;font-size:19px}
        .payment-modal-body{padding:18px;display:grid;gap:12px}
        .payment-row{display:flex;justify-content:space-between;gap:12px;color:var(--muted)}
        .payment-row strong{color:var(--ink)}
        .payment-note{padding:12px;border-radius:6px;background:#eef5ff;color:#24466e;line-height:1.55}
        .payment-qr{display:none;justify-items:center;gap:10px;padding:12px;border:1px solid var(--line);border-radius:6px;background:#f8fbff}
        .payment-qr.is-visible{display:grid}
        .payment-qr img{width:min(250px,100%);border-radius:6px;border:1px solid var(--line);background:#fff}
        .payment-qr span{color:var(--muted);font-size:12px;text-align:center}
        .payment-app-link{display:none}
        .payment-app-link.is-visible{display:inline-flex}
        .modal-close{width:34px;height:34px;border:1px solid var(--line);border-radius:6px;background:#fff;color:var(--muted);cursor:pointer;font-weight:900}
        .modal-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 18px 18px}
        .footer{background:#082f5f;color:#d9e7f7;margin-top:28px}
        .footer .wrap{display:grid;grid-template-columns:1.3fr repeat(3,1fr);gap:22px;padding-top:28px}
        .footer h4{margin:0 0 10px;color:#fff}
        .footer p,.footer a{color:#bed1e7;line-height:1.7}
        @media (max-width:1120px){
            .header-inner{grid-template-columns:1fr;gap:10px;padding-top:14px;padding-bottom:14px}
            .quick-actions{justify-content:flex-start;overflow:auto}
            .hero{grid-template-columns:1fr}
            .hero-main{grid-template-columns:1fr}
            .device-scene{display:none}
            .service-row,.category-tiles,.brand-grid,.brand-page-hero{grid-template-columns:repeat(2,minmax(0,1fr))}
            .catalog{grid-template-columns:1fr}
            .filter{position:static}
            .grid,.catalog .grid{grid-template-columns:repeat(2,minmax(0,1fr))}
            .footer .wrap,.detail,.checkout,.promo-band,.brand-page-hero,.auth-shell{grid-template-columns:1fr}
        }
        @media (max-width:640px){
            .topbar-inner{align-items:flex-start;flex-direction:column;padding-top:8px;padding-bottom:8px}
            .search{grid-template-columns:1fr 46px}
            .search select{display:none}
            .hero-main{padding:22px;min-height:270px}
            .hero-main h1{font-size:29px}
            .service-row,.category-tiles,.brand-grid,.grid,.catalog .grid,.store-hours{grid-template-columns:1fr}
            .section-head{align-items:flex-start;flex-direction:column}
            .brand-showcase-head{align-items:flex-start;flex-direction:column}
            .brand-slide{min-width:148px}
            .toolbar{align-items:flex-start;flex-direction:column}
        }
    </style>
    @stack('head')
</head>
<body>
    @php
        $navCategories = [
            ['slug' => 'laptops', 'name' => 'Laptop'],
            ['slug' => 'desktops', 'name' => 'Desktop PC'],
            ['slug' => 'gaming', 'name' => 'Gaming'],
            ['slug' => 'monitors', 'name' => 'Monitor'],
            ['slug' => 'components', 'name' => 'Components'],
            ['slug' => 'accessories', 'name' => 'Accessories'],
            ['slug' => 'printers', 'name' => 'Printer'],
        ];
    @endphp

    <div class="topbar">
        <div class="topbar-inner">
            <div class="topbar-left">
                <span>CEC Electronic Cambodia</span>
                <span>012 220 152 / 093 456 747</span>
                <span>Same-day delivery in Phnom Penh</span>
            </div>
            <div class="topbar-right">
                <span>Track Order</span>
                <span>Warranty Check</span>
                <span>Service Center</span>
            </div>
        </div>
    </div>

    <header class="header">
        <div class="header-inner">
            <a href="{{ route('shop.home') }}" class="logo" aria-label="CEC Electronic home">
                <span class="logo-mark"><img src="{{ asset('images/brand-logo.jpg') }}" alt="CEC Electronic logo"></span>
                <span class="logo-text">
                    <strong>CEC Electronic</strong>
                    <span>Computer, laptop & IT store</span>
                </span>
            </a>

            <form class="search" action="{{ route('shop.search') }}" method="get">
                <select name="type" aria-label="Search type">
                    <option value="name">Product name</option>
                    <option value="sku">SKU code</option>
                    <option value="brand">Brand</option>
                </select>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search laptop, desktop, monitor, printer...">
                <button type="submit" aria-label="Search">Go</button>
            </form>

            <div class="quick-actions">
                @auth
                    <a class="quick" href="{{ route('account.dashboard') }}"><span class="quick-icon"><img src="{{ asset('images/ProfileAndOrder/boy-account.png') }}" alt="Account"></span><span><strong>Account</strong><span>{{ auth()->user()->name }}</span></span></a>
                @else
                    <a class="quick" href="{{ route('customer.login') }}"><span class="quick-icon"><img src="{{ asset('images/ProfileAndOrder/login-icon.png') }}" alt="Login"></span><span><strong>Login</strong><span>Customer account</span></span></a>
                    <a class="quick" href="{{ route('customer.register') }}"><span class="quick-icon"><img src="{{ asset('images/ProfileAndOrder/register-icon.jpeg') }}" alt="Register"></span><span><strong>Register</strong><span>New customer</span></span></a>
                @endauth
                <a class="quick" href="{{ route('shop.cart') }}"><span class="quick-icon"><img src="{{ asset('images/ProfileAndOrder/card.jpeg') }}" alt="Cart"></span><span><strong>Cart</strong><span>Checkout</span></span></a>
            </div>
        </div>
        <nav class="nav">
            <div class="nav-inner">
                <a href="{{ route('shop.home') }}">Home</a>
                @foreach($navCategories as $category)
                    <a href="{{ route('shop.category', $category['slug']) }}">{{ $category['name'] }}</a>
                @endforeach
                <a href="{{ route('shop.brands') }}">Brands</a>
                <a href="{{ route('account.dashboard') }}">Account</a>
                <a href="{{ route('checkout.create') }}">Checkout</a>
            </div>
        </nav>
    </header>

    <main class="wrap">
        @yield('content')
    </main>

    <footer class="footer">
        <div class="wrap">
            <div>
                <h4>CEC Electronic</h4>
                <p>Retail-ready electronics website for computers, laptops, monitors, components, printers, and accessories with catalog, cart, checkout, and admin product management.</p>
            </div>
            <div>
                <h4>Customer Care</h4>
                <a href="#">Help Center</a><br>
                <a href="#">Order & Payment</a><br>
                <a href="#">Returns & Refund</a>
            </div>
            <div>
                <h4>Store Services</h4>
                <a href="#">Warranty Check</a><br>
                <a href="#">Repair Tracking</a><br>
                <a href="#">Business Quote</a>
            </div>
            <div>
                <h4>Contact</h4>
                <p>Phnom Penh, Cambodia<br>012 220 152<br>sales@cecelectronic.test</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
    <script>
        (function () {
            document.addEventListener('click', function (event) {
                var link = event.target.closest('a[href^="#"], a[href*="#"]');
                if (! link || ! link.hash) return;
                if (link.origin !== window.location.origin || link.pathname !== window.location.pathname) return;

                var target = document.querySelector(link.hash);
                if (! target) return;

                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                history.pushState(null, '', link.hash);
            });
        })();
    </script>
</body>
</html>
