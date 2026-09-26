# User Front — CEC Electronic

How the customer side of the shop works. Each section covers one feature and contains all of that feature's code.

| # | Feature | URL |
|---|---------|-----|
| 1 | Shop Layout | (all shop pages) |
| 2 | Home | `/` |
| 3 | Product & Catalog | `/product/{slug}`, `/category/{slug}`, `/brands`, `/search` |
| 4 | Cart | `/cart` |
| 5 | Login & Register | `/login`, `/register` |
| 6 | Checkout & Payment | `/checkout` |
| 7 | My Account & Receipt | `/account` |

---

## 1. Shop Layout

- Shared by all shop pages: header, search bar, category menu, Cart dropdown (Checkout / View history), account menu and footer.
- Also includes the loading overlay and the custom pagination view (set in `AppServiceProvider`).

### 1.1 `resources/views/shop/layout.blade.php`

```blade
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CEC Electronic')</title>
    <link rel="icon" href="{{ asset('images/brand-logo.jpg') }}" type="image/jpeg">
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
        .header-inner{min-height:78px;display:grid;grid-template-columns:230px minmax(280px,1fr) auto;align-items:center;gap:16px}
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
        .quick{position:relative;display:flex;align-items:center;gap:8px;padding:8px 9px;border:1px solid var(--line);border-radius:6px;background:#fff;min-height:42px}
        .quick:hover{border-color:#b8c7dc;background:#f8fbff}
        .quick-icon{width:25px;height:25px;border-radius:5px;background:#eef5ff;color:var(--brand);display:grid;place-items:center;font-weight:900;font-size:11px;flex:0 0 auto;overflow:hidden}
        .quick-icon img{width:100%;height:100%;object-fit:cover;display:block}
        .cart-badge{position:absolute;top:2px;left:26px;min-width:16px;height:16px;padding:0 4px;border-radius:999px;background:var(--danger);color:#fff;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;line-height:1;z-index:1}
        .quick-menu{position:relative}
        .quick-menu>.quick{cursor:pointer;font:inherit;color:inherit;text-align:left}
        .quick-caret{margin-left:2px;color:var(--muted);font-size:10px;transition:transform .15s ease}
        .quick-menu.is-open .quick-caret{transform:rotate(180deg)}
        .quick-dropdown{display:none;position:absolute;top:calc(100% + 6px);right:0;z-index:1100;min-width:210px;padding:6px;background:#fff;border:1px solid var(--line);border-radius:8px;box-shadow:0 10px 30px rgba(9,30,66,.18)}
        .quick-menu.is-open .quick-dropdown{display:block}
        .quick-dropdown a{display:flex;flex-direction:column;gap:2px;padding:10px 12px;border-radius:6px;color:var(--ink)}
        .quick-dropdown a:hover{background:#f0f6ff}
        .quick-dropdown a strong{font-size:13px}
        .quick-dropdown a span{color:var(--muted);font-size:11px}
        .qty-control{display:flex;align-items:center;gap:8px}
        .qty-btn{width:26px;height:26px;border:1px solid var(--line);border-radius:5px;background:#fff;color:var(--ink);font-weight:800;cursor:pointer;line-height:1}
        .qty-btn:hover{border-color:#b8c7dc;background:#f8fbff}
        .qty-btn:disabled{opacity:.5;cursor:not-allowed}
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
        .pager{display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap}
        .pager-nav{padding:9px 16px}
        .pager-nav.disabled{opacity:.45;cursor:not-allowed;pointer-events:none}
        .pager-pages{display:flex;align-items:center;gap:4px;flex-wrap:wrap}
        .pager-page{min-width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:7px;padding:0 6px;color:var(--ink);font-weight:700}
        .pager-page:hover{background:#eef5ff;color:var(--brand)}
        .pager-page.active{background:var(--brand);color:#fff}
        .pager-dots{min-width:24px;text-align:center;color:var(--muted)}
        .pager-summary{text-align:center;color:var(--muted);font-size:12.5px;margin-top:8px}
        .icon-btn{width:42px;height:40px;border-radius:6px;border:1px solid var(--line);background:#fff;color:var(--brand);font-size:18px;cursor:pointer}
        .filter h3{margin:0;padding:13px 14px;background:#f8fbff;border-bottom:1px solid var(--line);font-size:15px}
        .hero-slider{position:relative;margin-bottom:14px;overflow:hidden;height:500px;background:#eef5ff}
        .hero-slide{position:absolute;inset:0;opacity:0;transition:opacity .6s ease;pointer-events:none}
        .hero-slide.is-active{opacity:1;pointer-events:auto}
        .hero-slide img{width:100%;height:100%;object-fit:cover;display:block}
        .hero-slider-dots{position:absolute;left:50%;bottom:14px;transform:translateX(-50%);display:flex;gap:8px;z-index:2}
        .hero-slider-dot{width:9px;height:9px;padding:0;border-radius:999px;border:1px solid rgba(255,255,255,.8);background:rgba(255,255,255,.4);cursor:pointer}
        .hero-slider-dot.is-active{background:#fff}
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
        .checkout{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:16px;align-items:start}
        .checkout-steps{display:flex;align-items:center;justify-content:center;gap:0;list-style:none;margin:2px 0 22px;padding:0}
        .checkout-step{display:flex;align-items:center;gap:9px;color:var(--muted);font-weight:800;font-size:13px;white-space:nowrap}
        .checkout-step-num{width:26px;height:26px;border-radius:50%;background:#eef5ff;color:var(--brand);display:grid;place-items:center;font-weight:900;font-size:12px;flex:0 0 auto}
        .checkout-step.is-active{color:var(--ink)}
        .checkout-step.is-active .checkout-step-num{background:var(--brand);color:#fff}
        .checkout-step.is-done .checkout-step-num{background:var(--success);color:#fff}
        .checkout-step-line{width:64px;height:2px;background:var(--line);margin:0 10px;flex:0 0 auto}
        .checkout-step-line.is-done{background:var(--success)}
        .checkout-section{padding:20px 22px;margin-bottom:16px}
        .checkout-section+.checkout-section{margin-bottom:0}
        .checkout-section-head{display:flex;align-items:center;gap:11px;margin:0 0 18px}
        .checkout-section-head .icon{width:34px;height:34px;border-radius:8px;background:#eef5ff;color:var(--brand);display:grid;place-items:center;font-size:16px;flex:0 0 auto}
        .checkout-section-head h3{margin:0;font-size:16px}
        .field-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .payment-options{display:grid;gap:10px}
        .payment-option{position:relative;display:flex;align-items:center;gap:12px;border:1.5px solid var(--line);border-radius:8px;padding:13px 14px;cursor:pointer;transition:border-color .15s ease,background .15s ease}
        .payment-option:hover{border-color:#b8c7dc;background:#f8fbff}
        .payment-option input{position:absolute;opacity:0;width:0;height:0;margin:0}
        .payment-option-icon{width:38px;height:38px;border-radius:8px;background:#eef5ff;display:grid;place-items:center;font-size:18px;flex:0 0 auto}
        .payment-option-body{flex:1;min-width:0}
        .payment-option-body strong{display:block;font-size:13.5px}
        .payment-option-body span{display:block;color:var(--muted);font-size:12px;margin-top:2px}
        .payment-option.is-checked{border-color:var(--brand);background:#f2f7ff}
        .payment-option-check{width:18px;height:18px;border-radius:50%;border:2px solid var(--line);flex:0 0 auto}
        .payment-option.is-checked .payment-option-check{border-color:var(--brand);background:var(--brand);box-shadow:inset 0 0 0 3px #fff}
        .order-summary{position:sticky;top:118px}
        .order-summary-item{display:flex;gap:10px;align-items:center;margin-bottom:14px}
        .order-summary-thumb{width:48px;height:48px;border-radius:6px;border:1px solid var(--line);background:#fff;display:grid;place-items:center;overflow:hidden;flex:0 0 auto}
        .order-summary-thumb img{width:100%;height:100%;object-fit:contain}
        .order-summary-info{flex:1;min-width:0}
        .order-summary-info strong{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-size:13px;font-weight:750;line-height:1.35}
        .order-summary-info span{color:var(--muted);font-size:12px}
        .order-summary-price{font-weight:850;font-size:13px;white-space:nowrap}
        .trust-row{display:flex;align-items:center;justify-content:center;gap:7px;color:var(--muted);font-size:11.5px;margin-top:12px;text-align:center;line-height:1.4}
        .success-hero{padding:36px 28px;text-align:center;background:linear-gradient(135deg,#07376f,#0057a8);color:#fff;border-radius:6px 6px 0 0}
        .success-hero .check{width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,.16);display:grid;place-items:center;margin:0 auto 14px;font-size:30px;color:#fff}
        .success-hero h1{margin:0 0 6px;font-size:24px}
        .success-hero p{margin:0;color:#dcebfb}
        .receipt-card{max-width:640px;margin:0 auto;overflow:hidden}
        .receipt-body{padding:26px 28px}
        .receipt-order-no{text-align:center;color:var(--muted);font-size:13px;margin-bottom:18px}
        .receipt-order-no strong{color:var(--ink);font-size:15px}
        .receipt-row{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:13px 0;border-top:1px solid var(--line)}
        .receipt-row:first-child{border-top:0}
        .receipt-row span{color:var(--muted)}
        .receipt-row strong{font-weight:800}
        .receipt-row.is-total strong{font-size:18px;color:var(--brand)}
        .status-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;font-size:12px;font-weight:800}
        .status-pill.is-paid{background:#ecfdf3;color:var(--success)}
        .status-pill.is-pending{background:#fff8e6;color:#8a5a00}
        .next-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:20px}
        .next-steps div{padding:14px 10px;background:#f8fbff;border:1px solid var(--line);border-radius:7px;text-align:center}
        .next-steps .num{width:26px;height:26px;border-radius:50%;background:var(--brand);color:#fff;display:grid;place-items:center;margin:0 auto 8px;font-weight:900;font-size:12px}
        .next-steps strong{display:block;font-size:12.5px;margin-bottom:3px}
        .next-steps span{color:var(--muted);font-size:11px;line-height:1.4}
        .receipt-actions{display:flex;gap:10px;margin-top:22px}
        .receipt-actions .btn{flex:1}
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
        .cart-popup{display:none}
        @media (max-width:640px){
            .cart-popup{display:block;position:fixed;left:0;right:0;bottom:0;z-index:1200;padding:0 12px calc(12px + env(safe-area-inset-bottom));pointer-events:none;transform:translateY(120%);transition:transform .25s ease}
            .cart-popup.is-open{transform:translateY(0);pointer-events:auto}
            .cart-popup-card{background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 -6px 30px rgba(9,30,66,.22);padding:16px}
            .cart-popup-head{display:flex;align-items:flex-start;gap:12px}
            .cart-popup-check{flex:none;width:34px;height:34px;border-radius:50%;background:#ecfdf3;color:var(--success);display:grid;place-items:center;font-weight:900}
            .cart-popup-text{flex:1;min-width:0}
            .cart-popup-text strong{display:block;color:var(--ink)}
            .cart-popup-text span{display:block;color:var(--muted);font-size:13px;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
            .cart-popup-close{flex:none;width:30px;height:30px;border:0;background:transparent;color:var(--muted);font-size:20px;line-height:1;cursor:pointer}
            .cart-popup-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px}
        }
        @media (max-width:1120px){
            .header-inner{grid-template-columns:1fr;gap:10px;padding-top:14px;padding-bottom:14px}
            .quick-actions{justify-content:flex-start;flex-wrap:wrap}
            .quick-dropdown{right:auto;left:0}
            .service-row,.category-tiles,.brand-grid,.brand-page-hero{grid-template-columns:repeat(2,minmax(0,1fr))}
            .catalog{grid-template-columns:1fr}
            .header{position:static}
            .filter{position:static}
            .grid,.catalog .grid{grid-template-columns:repeat(2,minmax(0,1fr))}
            .footer .wrap,.detail,.checkout,.promo-band,.brand-page-hero,.auth-shell{grid-template-columns:1fr}
            .order-summary{position:static}
            .field-grid{grid-template-columns:1fr}
        }
        @media (max-width:640px){
            .checkout-step span:last-child{display:none}
            .checkout-step-line{width:26px;margin:0 6px}
            .next-steps{grid-template-columns:1fr}
            .receipt-actions{flex-direction:column}
            .success-hero{padding:28px 18px}
            .receipt-body{padding:20px 18px}
            .topbar-inner{align-items:flex-start;flex-direction:column;padding-top:8px;padding-bottom:8px}
            .search{grid-template-columns:1fr 46px}
            .search select{display:none}
            .hero-slider{height:220px}
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
        $cartCount = app(\App\Services\CartService::class)->count(request());
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
                <div class="quick-menu" data-quick-menu>
                    <button type="button" class="quick" data-quick-menu-toggle aria-haspopup="true" aria-expanded="false">
                        <span class="quick-icon">
                            <img src="{{ asset('images/ProfileAndOrder/card.jpeg') }}" alt="Cart">
                        </span>
                        <span id="cart-count" class="cart-badge" style="{{ $cartCount > 0 ? '' : 'display:none' }}">{{ $cartCount }}</span>
                        <span><strong>Cart</strong><span>Checkout &amp; history</span></span>
                        <span class="quick-caret" aria-hidden="true">&#9660;</span>
                    </button>
                    <div class="quick-dropdown" role="menu">
                        <a href="{{ route('shop.cart') }}" role="menuitem"><strong>Checkout</strong><span>Review your cart and pay</span></a>
                        <a href="{{ route('account.orders') }}" role="menuitem"><strong>View history</strong><span>Orders, receipts and downloads</span></a>
                    </div>
                </div>
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

    @include('partials.loading-overlay')

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
    <div class="cart-popup" data-cart-popup role="dialog" aria-label="Added to cart" aria-hidden="true">
        <div class="cart-popup-card">
            <div class="cart-popup-head">
                <span class="cart-popup-check">&#10003;</span>
                <div class="cart-popup-text">
                    <strong>Added to cart</strong>
                    <span data-cart-popup-detail></span>
                </div>
                <button type="button" class="cart-popup-close" data-cart-popup-close aria-label="Close">&times;</button>
            </div>
            <div class="cart-popup-actions">
                <button type="button" class="btn secondary" data-cart-popup-close>Continue shopping</button>
                <a class="btn" href="{{ route('shop.cart') }}">View cart</a>
            </div>
        </div>
    </div>

    <script>
        (function () {
            function csrfToken() {
                var meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.content : '';
            }

            function jsonFetch(url, options) {
                options = options || {};
                options.headers = Object.assign({
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                }, options.headers || {});

                if (window.PageLoader) window.PageLoader.show();

                return fetch(url, options).then(function (response) {
                    if (! response.ok) {
                        throw new Error('Request failed');
                    }
                    return response.json();
                }).finally(function () {
                    if (window.PageLoader) window.PageLoader.hide();
                });
            }

            function setCartCount(count) {
                document.querySelectorAll('#cart-count').forEach(function (badge) {
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'flex' : 'none';
                });
            }

            // Phone-only "added to cart" popup with a View cart button.
            var cartPopup = document.querySelector('[data-cart-popup]');
            var phoneQuery = window.matchMedia('(max-width: 640px)');
            var cartPopupTimer;

            function setCartPopup(open) {
                if (! cartPopup) return;
                cartPopup.classList.toggle('is-open', open);
                cartPopup.setAttribute('aria-hidden', open ? 'false' : 'true');
                window.clearTimeout(cartPopupTimer);
                if (open) cartPopupTimer = window.setTimeout(function () { setCartPopup(false); }, 8000);
            }

            function showCartPopup(data) {
                if (! cartPopup || ! phoneQuery.matches) return;
                var detail = cartPopup.querySelector('[data-cart-popup-detail]');
                if (detail) {
                    var items = data.count + (data.count === 1 ? ' item' : ' items') + ' in your cart';
                    detail.textContent = data.product ? data.product + ' · ' + items : items;
                }
                setCartPopup(true);
            }

            // Header Cart card: Checkout / View history dropdown.
            function closeQuickMenus(except) {
                document.querySelectorAll('[data-quick-menu].is-open').forEach(function (menu) {
                    if (menu === except) return;
                    menu.classList.remove('is-open');
                    menu.querySelector('[data-quick-menu-toggle]').setAttribute('aria-expanded', 'false');
                });
            }

            document.addEventListener('click', function (event) {
                var toggle = event.target.closest('[data-quick-menu-toggle]');
                var menu = toggle ? toggle.closest('[data-quick-menu]') : null;
                closeQuickMenus(menu);
                if (menu) {
                    var open = menu.classList.toggle('is-open');
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeQuickMenus(null);
            });

            document.addEventListener('click', function (event) {
                if (event.target.closest('[data-cart-popup-close]')) setCartPopup(false);
            });

            // Add to cart (product grid + product detail forms)
            document.addEventListener('submit', function (event) {
                var form = event.target.closest('form[data-cart-add]');
                if (! form) return;

                event.preventDefault();

                var button = form.querySelector('button[type="submit"]');
                var originalText = button ? button.textContent : '';

                if (button) {
                    button.disabled = true;
                    button.textContent = 'Adding...';
                }

                jsonFetch(form.getAttribute('action'), {
                    method: 'POST',
                    body: new FormData(form),
                }).then(function (data) {
                    setCartCount(data.count);
                    showCartPopup(data);
                    if (button) button.textContent = 'Added!';
                }).catch(function () {
                    if (button) button.textContent = 'Try again';
                }).finally(function () {
                    setTimeout(function () {
                        if (button) {
                            button.disabled = false;
                            button.textContent = originalText;
                        }
                    }, 1200);
                });
            });

            // Cart page: quantity +/- and remove
            function applyCartResponse(row, data) {
                setCartCount(data.count);

                var subtotalEl = document.getElementById('cart-subtotal');
                if (subtotalEl && data.subtotal !== undefined) {
                    subtotalEl.textContent = '$' + data.subtotal;
                }

                if (data.removed) {
                    row.remove();
                    if (data.count === 0) {
                        window.location.reload();
                    }
                    return;
                }

                row.dataset.quantity = data.quantity;

                var qtyEl = row.querySelector('[data-cart-qty-value]');
                if (qtyEl) qtyEl.textContent = data.quantity;

                var lineTotalEl = row.querySelector('[data-cart-line-total]');
                if (lineTotalEl) lineTotalEl.textContent = '$' + data.line_total;
            }

            function updateCartItem(row, quantity) {
                jsonFetch(row.dataset.updateUrl, {
                    method: 'POST',
                    body: new URLSearchParams({ _method: 'PATCH', quantity: quantity }),
                }).then(function (data) {
                    applyCartResponse(row, data);
                });
            }

            function removeCartItem(row) {
                jsonFetch(row.dataset.removeUrl, {
                    method: 'POST',
                    body: new URLSearchParams({ _method: 'DELETE' }),
                }).then(function (data) {
                    applyCartResponse(row, data);
                });
            }

            document.addEventListener('click', function (event) {
                var qtyBtn = event.target.closest('[data-cart-qty]');
                if (qtyBtn) {
                    event.preventDefault();
                    var row = qtyBtn.closest('[data-cart-item]');
                    var current = parseInt(row.dataset.quantity, 10) || 0;
                    var next = qtyBtn.dataset.cartQty === 'increase' ? current + 1 : current - 1;

                    if (next < 0) return;

                    updateCartItem(row, next);
                    return;
                }

                var removeBtn = event.target.closest('[data-cart-remove]');
                if (removeBtn) {
                    event.preventDefault();
                    removeCartItem(removeBtn.closest('[data-cart-item]'));
                }
            });
        })();
    </script>
</body>
</html>
```

### 1.2 `resources/views/partials/loading-overlay.blade.php`

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

### 1.3 `resources/views/vendor/pagination/custom.blade.php`

```blade
@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        @if ($paginator->onFirstPage())
            <span class="btn secondary pager-nav disabled" aria-disabled="true">&larr; Back</span>
        @else
            <a class="btn secondary pager-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev">&larr; Back</a>
        @endif

        <div class="pager-pages">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager-dots">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager-page active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pager-page" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        @if ($paginator->hasMorePages())
            <a class="btn pager-nav" href="{{ $paginator->nextPageUrl() }}" rel="next">Next &rarr;</a>
        @else
            <span class="btn pager-nav disabled" aria-disabled="true">Next &rarr;</span>
        @endif
    </nav>

    <p class="pager-summary">
        Showing {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }} of {{ $paginator->total() }}
    </p>
@endif
```

---

## 2. Home

- Shows active categories, active brands that have a logo, and the 12 newest active products.
- The result is cached for 5 minutes (`catalog.home`).

**Routes** (`routes/web.php`)

```php
Route::get('/', HomeController::class)->name('shop.home');
```

### 2.1 `app/Http/Controllers/Storefront/HomeController.php`

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

### 2.2 `resources/views/shop/home.blade.php`

```blade
@extends('shop.layout')

@section('title','CEC Electronic - Computer, Laptop & IT Store')

@section('content')
    @php
        $categoryTiles = [
            ['slug' => 'laptops', 'name' => 'Laptops', 'copy' => 'Business, student, creator, and gaming notebooks.', 'image' => 'MSFT-All-in-One_1040x585.avif'],
            ['slug' => 'desktops', 'name' => 'Desktop PC', 'copy' => 'Ready office PCs and custom build quotation.', 'image' => 'desktop-pc.webp'],
            ['slug' => 'gaming', 'name' => 'Gaming Gear', 'copy' => 'Gaming laptops, keyboards, mice, headsets, and chairs.', 'image' => 'gamming-gear.jpeg'],
            ['slug' => 'monitors', 'name' => 'Monitors', 'copy' => 'Office, gaming, ultrawide, and creator displays.', 'image' => 'monitor.png'],
            ['slug' => 'components', 'name' => 'Components', 'copy' => 'CPU, motherboard, RAM, SSD, GPU, PSU, and cases.', 'image' => 'component.jpeg'],
            ['slug' => 'printers', 'name' => 'Printers', 'copy' => 'Inkjet, laser, scanner, copier, and office supply.', 'image' => 'printer.jpeg'],
        ];
    @endphp

    <section class="panel hero-slider" data-hero-slider aria-label="Featured promotions" aria-roledescription="carousel">
        @foreach(['cover1', 'cover2', 'cover3', 'cover4'] as $i => $cover)
            <div class="hero-slide{{ $i === 0 ? ' is-active' : '' }}" aria-hidden="{{ $i === 0 ? 'false' : 'true' }}">
                <img src="{{ asset('images/Logo/' . $cover . '.png') }}" alt="CEC Electronic promotion {{ $i + 1 }}">
            </div>
        @endforeach

        <div class="hero-slider-dots">
            @for($i = 0; $i < 4; $i++)
                <button type="button" class="hero-slider-dot{{ $i === 0 ? ' is-active' : '' }}" data-hero-dot="{{ $i }}" aria-label="Go to slide {{ $i + 1 }}"></button>
            @endfor
        </div>
    </section>

    <section class="service-row" aria-label="Store services">
        <div class="panel service"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/delivery-icon.png') }}" alt="Same-day delivery"></span><span><strong>Same-day delivery</strong><span>Fast dispatch in Phnom Penh areas.</span></span></div>
        <div class="panel service"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/warranty-icon.jpeg') }}" alt="Warranty support"></span><span><strong>Warranty support</strong><span>Track service and product warranty.</span></span></div>
        <div class="panel service"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/quote-icon.jpeg') }}" alt="Custom PC quote"></span><span><strong>Custom PC quote</strong><span>Build lists for gaming or office work.</span></span></div>
        <div class="panel service"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/secure-checkout-icon.jpeg') }}" alt="Secure checkout"></span><span><strong>Secure checkout</strong><span>Cash on delivery and bank transfer.</span></span></div>
    </section>

    <section class="brand-showcase" aria-label="Featured brands">
        <div class="brand-showcase-head">
            <div>
                <h2>Shop by trusted brand</h2>
                <p>Browse official products and accessories from CEC Electronic partners.</p>
            </div>
            <div class="brand-controls">
                <button class="brand-control" type="button" data-brand-slide="-1" aria-label="Previous brands">&lt;</button>
                <button class="brand-control" type="button" data-brand-slide="1" aria-label="Next brands">&gt;</button>
                <a class="btn secondary" href="{{ route('shop.brands') }}">All brands</a>
            </div>
        </div>

        <div class="brand-slider-wrap">
            <div class="brand-slider is-marquee" data-brand-slider>
                @foreach($brands->concat($brands) as $brand)
                    <a class="brand-slide" href="{{ route('shop.brand', $brand->slug) }}" aria-label="View {{ $brand->name }} products">
                        <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }} logo">
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <div class="section-head">
        <div>
            <h2>Shop by category</h2>
            <p>Built for fast browsing in a computer store.</p>
        </div>
    </div>

    <section class="category-tiles">
        @foreach($categoryTiles as $tile)
            <a class="panel category-tile" href="{{ route('shop.category', $tile['slug']) }}">
                <span class="category-tile-img"><img src="{{ asset('images/ShopCategory/' . $tile['image']) }}" alt="{{ $tile['name'] }}"></span>
                <strong>{{ $tile['name'] }}</strong>
                <span>{{ $tile['copy'] }}</span>
            </a>
        @endforeach
    </section>

    <div class="section-head" id="featured">
        <div>
            <h2>Featured electronics</h2>
            <p>Latest products ready for retail orders.</p>
        </div>
        <span style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn secondary" href="{{ route('shop.brands') }}">All brands</a>
            <a class="btn secondary" href="{{ route('shop.category', 'laptops') }}">Browse catalog</a>
        </span>
    </div>

    <div class="grid">
        @foreach($products as $p)
            @include('shop.partials.product-card', ['p' => $p])
        @endforeach
    </div>

    <section class="promo-band">
        <div>
            <h2>Need products for a company, school, or gaming room?</h2>
            <p>CEC Electronic can prepare a full quotation for laptops, desktops, monitors, printers, networking, accessories, and delivery.</p>
        </div>
        <div class="store-hours">
            <div><strong>Open</strong><br><span class="sku">Mon-Sat, 8:30 AM - 7:00 PM</span></div>
            <div><strong>Hotline</strong><br><span class="sku">012 220 152</span></div>
            <div><strong>Location</strong><br><span class="sku">Phnom Penh</span></div>
        </div>
    </section>

    @push('scripts')
        <script>
            (function () {
                var heroSlider = document.querySelector('[data-hero-slider]');
                if (! heroSlider) return;

                var slides = Array.prototype.slice.call(heroSlider.querySelectorAll('.hero-slide'));
                var dots = Array.prototype.slice.call(heroSlider.querySelectorAll('[data-hero-dot]'));
                var current = 0;
                var timer = null;

                function goTo(index) {
                    slides[current].classList.remove('is-active');
                    slides[current].setAttribute('aria-hidden', 'true');
                    dots[current].classList.remove('is-active');

                    current = (index + slides.length) % slides.length;

                    slides[current].classList.add('is-active');
                    slides[current].setAttribute('aria-hidden', 'false');
                    dots[current].classList.add('is-active');
                }

                function start() {
                    timer = setInterval(function () { goTo(current + 1); }, 5000);
                }

                function stop() {
                    clearInterval(timer);
                }

                dots.forEach(function (dot, index) {
                    dot.addEventListener('click', function () {
                        goTo(index);
                        stop();
                        start();
                    });
                });

                heroSlider.addEventListener('mouseenter', stop);
                heroSlider.addEventListener('mouseleave', start);

                if (slides.length > 1) start();
            })();

            var brandSlider = document.querySelector('[data-brand-slider]');

            document.querySelectorAll('[data-brand-slide]').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (! brandSlider) return;
                    brandSlider.classList.remove('is-marquee');
                    brandSlider.style.transform = '';
                    brandSlider.style.width = '';
                    brandSlider.style.overflow = 'auto';
                    brandSlider.scrollBy({ left: Number(button.dataset.brandSlide) * 420, behavior: 'smooth' });
                });
            });
        </script>
    @endpush
@endsection
```

---

## 3. Product & Catalog

- Category, brand, search and product detail pages. Only products with `is_active = true` are shown.
- Filters: `category[]`, `brand[]`, `processor[]`, `ram[]`, `storage[]`, `price[]`.
- Results are cached for 5 minutes. Each cache key includes a hash of the filters.

**Routes** (`routes/web.php`)

```php
Route::get('/search', [CatalogController::class, 'search'])->name('shop.search');
Route::get('/brands', [CatalogController::class, 'brands'])->name('shop.brands');
Route::get('/brands/{slug}', [CatalogController::class, 'brand'])->name('shop.brand');
Route::get('/category/{slug}', [CatalogController::class, 'category'])->name('shop.category');
Route::get('/product/{slug}', [CatalogController::class, 'product'])->name('shop.product');
```

### 3.1 `app/Http/Controllers/Storefront/CatalogController.php`

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

### 3.2 `app/Models/Product.php`

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

### 3.3 `app/Models/Category.php`

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

### 3.4 `app/Models/Brand.php`

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

### 3.5 `resources/views/shop/product.blade.php`

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

### 3.6 `resources/views/shop/category.blade.php`

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

### 3.7 `resources/views/shop/brands.blade.php`

```blade
@extends('shop.layout')

@section('title', 'All Brands - CEC Electronic')

@section('content')
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('shop.home') }}">Home</a>
        <span>/</span>
        <span>All Brands</span>
    </nav>

    <section class="panel brand-page-hero">
        <div>
            <h1>All Brands</h1>
            <p>Browse official laptops, desktops, monitors, printers, components, and accessories by brand. CEC Electronic keeps this page clean so customers can find products fast.</p>
        </div>
        <div class="brand-page-stats">
            <div>
                <strong>{{ $brands->count() }}</strong>
                <span>Available brands</span>
            </div>
            <div>
                <strong>{{ number_format($brands->sum('products_count')) }}</strong>
                <span>Matched products</span>
            </div>
        </div>
    </section>

    <div class="section-head">
        <div>
            <h2>Brand directory</h2>
            <p>Select a brand to view matching products.</p>
        </div>
        <a class="btn secondary" href="{{ route('shop.home') }}">Back home</a>
    </div>

    <section class="brand-grid">
        @foreach($brands as $brand)
            <a class="panel brand-card" href="{{ route('shop.brand', $brand->slug) }}">
                <span class="brand-logo-box">
                    @if($brand->logo_url)
                        <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }} logo">
                    @else
                        <span class="brand-initials">{{ $brand->initials }}</span>
                    @endif
                </span>
                <strong>{{ $brand->name }}</strong>
                <span>{{ $brand->products_count }} products</span>
            </a>
        @endforeach
    </section>
@endsection
```

### 3.8 `resources/views/shop/partials/product-card.blade.php`

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

---

## 4. Cart

- Guests: cart items are stored by `session_id`. Logged-in users: cart items are stored by `user_id`.
- Adding the same product again increases the quantity (1–99). Setting the quantity to 0 removes the item.
- Changing another user's cart item returns a 403 error. On login, the guest cart is merged into the user's cart.

**Routes** (`routes/web.php`)

```php
Route::get('/cart', [CartController::class, 'index'])->name('shop.cart');
Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');
```

### 4.1 `app/Http/Controllers/Storefront/CartController.php`

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

### 4.2 `app/Services/CartService.php`

```php
<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class CartService
{
    /**
     * Reassign a guest session's cart items to a newly authenticated user,
     * combining quantities where the user already has the same product.
     */
    public function mergeGuestCartIntoUser(string $sessionId, User $user): void
    {
        CartItem::query()
            ->where('session_id', $sessionId)
            ->get()
            ->each(function (CartItem $guestItem) use ($user) {
                $userItem = CartItem::query()
                    ->where('user_id', $user->id)
                    ->where('product_id', $guestItem->product_id)
                    ->first();

                if ($userItem) {
                    $userItem->increment('quantity', $guestItem->quantity);
                    $guestItem->delete();
                } else {
                    $guestItem->update(['user_id' => $user->id, 'session_id' => null]);
                }
            });
    }

    public function items(Request $request): Collection
    {
        return CartItem::query()
            ->with('product')
            ->where($this->ownerColumn($request), $this->ownerValue($request))
            ->latest()
            ->get();
    }

    public function add(Request $request, Product $product, int $quantity = 1): CartItem
    {
        $ownerColumn = $this->ownerColumn($request);
        $ownerValue = $this->ownerValue($request);

        $cartItem = CartItem::firstOrNew([
            $ownerColumn => $ownerValue,
            'product_id' => $product->id,
        ]);

        $cartItem->unit_price = $product->price;
        $cartItem->quantity = (int) $cartItem->quantity + max(1, $quantity);
        $cartItem->save();

        return $cartItem;
    }

    public function updateQuantity(Request $request, CartItem $cartItem, int $quantity): void
    {
        $this->guardOwner($request, $cartItem);

        if ($quantity <= 0) {
            $cartItem->delete();
            return;
        }

        $cartItem->update(['quantity' => $quantity]);
    }

    public function remove(Request $request, CartItem $cartItem): void
    {
        $this->guardOwner($request, $cartItem);
        $cartItem->delete();
    }

    public function subtotal(Request $request): float
    {
        return $this->items($request)->sum(fn (CartItem $item) => $item->line_total);
    }

    public function count(Request $request): int
    {
        return (int) $this->items($request)->sum('quantity');
    }

    public function clear(Request $request): void
    {
        CartItem::query()
            ->where($this->ownerColumn($request), $this->ownerValue($request))
            ->delete();
    }

    private function ownerColumn(Request $request): string
    {
        return $request->user() ? 'user_id' : 'session_id';
    }

    private function ownerValue(Request $request): int|string
    {
        return $request->user()?->id ?: $request->session()->getId();
    }

    private function guardOwner(Request $request, CartItem $cartItem): void
    {
        abort_unless(
            $cartItem->{$this->ownerColumn($request)} === $this->ownerValue($request),
            403
        );
    }
}
```

### 4.3 `app/Models/CartItem.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLineTotalAttribute(): float
    {
        return $this->quantity * (float) $this->unit_price;
    }
}
```

### 4.4 `resources/views/shop/cart.blade.php`

```blade
@extends('shop.layout')

@section('title','Your Cart - CEC Electronic')

@section('content')
    <div class="section-head">
        <h2>Your cart</h2>
        <a class="btn secondary" href="{{ route('shop.home') }}">Continue shopping</a>
    </div>

    @if(session('status'))
        <div class="panel" style="padding:12px 14px;margin-bottom:14px;color:#087443;background:#e7f8ef">{{ session('status') }}</div>
    @endif

    <section class="checkout">
        <div class="panel" style="padding:18px">
            @if($items->isEmpty())
                <div style="min-height:260px;display:grid;place-items:center;text-align:center;color:var(--muted)">
                    <div>
                        <div style="font-size:44px;color:var(--brand);font-weight:900">0</div>
                        <h3 style="margin:8px 0;color:var(--ink)">Your cart is empty</h3>
                        <p style="margin:0 0 18px">Browse the catalog and add laptops, computers, or accessories.</p>
                        <a class="btn" href="{{ route('shop.category', 'laptops') }}">Shop laptops</a>
                    </div>
                </div>
            @else
                @foreach($items as $item)
                    <div
                        data-cart-item
                        data-quantity="{{ (int) $item->quantity }}"
                        data-update-url="{{ route('cart.update', $item) }}"
                        data-remove-url="{{ route('cart.destroy', $item) }}"
                        style="display:grid;grid-template-columns:1fr 120px 90px;gap:14px;align-items:center;border-bottom:1px solid var(--line);padding:14px 0"
                    >
                        <div>
                            <strong>{{ $item->product?->name ?: 'Deleted product' }}</strong>
                            <div class="sku">${{ number_format($item->unit_price, 2) }} each</div>
                        </div>
                        <div class="qty-control">
                            <button type="button" class="qty-btn" data-cart-qty="decrease" aria-label="Decrease quantity">−</button>
                            <span data-cart-qty-value style="min-width:20px;text-align:center">{{ (int) $item->quantity }}</span>
                            <button type="button" class="qty-btn" data-cart-qty="increase" aria-label="Increase quantity">+</button>
                        </div>
                        <div style="text-align:right">
                            <strong data-cart-line-total>${{ number_format($item->line_total, 2) }}</strong>
                            <button type="button" data-cart-remove style="border:0;background:transparent;color:#e11d48;cursor:pointer;display:block;margin-top:8px;margin-left:auto">Remove</button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <aside class="panel" style="padding:18px">
            <h3 style="margin-top:0">Order summary</h3>
            <p class="sku" style="margin-top:-4px;margin-bottom:14px">CEC Electronic retail order</p>
            <div style="display:flex;justify-content:space-between;color:var(--muted);margin-bottom:10px">
                <span>Subtotal</span>
                <span id="cart-subtotal">${{ number_format($subtotal ?? 0, 2) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;color:var(--muted);margin-bottom:18px">
                <span>Delivery</span>
                <span>Free</span>
            </div>
            @guest
                <div style="padding:11px 12px;margin-bottom:12px;border-radius:6px;background:#fff3cf;color:#8a5a00;font-weight:800">
                    Please login or register before checkout.
                </div>
            @endguest
            <a class="btn" style="width:100%" href="{{ route('checkout.create') }}">{{ auth()->check() ? 'Checkout' : 'Login to checkout' }}</a>
        </aside>
    </section>
@endsection
```

---

## 5. Login & Register

- **Login:** email + password. On success, the guest cart is merged and the user goes to `/account`.
- **Register:** name, unique email, password (at least 8 characters). Earlier guest orders with the same email are linked to the new account.
- **Logout:** clears the session and returns to the home page.

**Routes** (`routes/web.php`)

```php
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
```

### 5.1 `app/Http/Controllers/Customer/AuthController.php`

```php
<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private CartService $cartService)
    {
    }

    public function login(): View
    {
        return view('account.auth.login');
    }

    public function register(): View
    {
        return view('account.auth.register');
    }

    public function authenticate(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $sessionId = $request->session()->getId();

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Email or password is incorrect.',
                    'errors' => ['email' => ['Email or password is incorrect.']],
                ], 422);
            }

            return back()
                ->withErrors(['email' => 'Email or password is incorrect.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $this->cartService->mergeGuestCartIntoUser($sessionId, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged in successfully.',
                'redirect' => route('account.dashboard'),
            ]);
        }

        return redirect()->intended(route('account.dashboard'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $sessionId = $request->session()->getId();

        $user = User::create($data);

        Order::query()
            ->whereNull('user_id')
            ->where('customer_email', $user->email)
            ->update(['user_id' => $user->id]);

        Auth::login($user);
        $request->session()->regenerate();
        $this->cartService->mergeGuestCartIntoUser($sessionId, $user);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Account created.',
                'redirect' => route('account.dashboard'),
            ]);
        }

        return redirect()->intended(route('account.dashboard'))->with('status', 'Account created.');
    }

    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged out.',
                'redirect' => route('shop.home'),
            ]);
        }

        return redirect()->route('shop.home')->with('status', 'Logged out.');
    }
}
```

### 5.2 `app/Models/User.php`

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
```

### 5.3 `resources/views/account/auth/login.blade.php`

```blade
@extends('shop.layout')

@section('title', 'Customer Login - CEC Electronic')

@section('content')
    <section class="auth-shell">
        <div class="panel auth-hero">
            <h1>Welcome back to CEC Electronic</h1>
            <p>Login to view your order history, check delivery progress, keep warranty order numbers, and checkout faster next time.</p>
            <div class="service-row" style="grid-template-columns:1fr 1fr;margin-top:22px">
                <div class="service" style="background:rgba(255,255,255,.1);border-radius:8px"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/order-icon.png') }}" alt="Orders"></span><span><strong style="color:#fff">Orders</strong><span style="color:#d9e7f7">Track purchases.</span></span></div>
                <div class="service" style="background:rgba(255,255,255,.1);border-radius:8px"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/warranty-icon.jpeg') }}" alt="Warranty"></span><span><strong style="color:#fff">Warranty</strong><span style="color:#d9e7f7">Save records.</span></span></div>
            </div>
        </div>

        <form class="panel auth-card" action="{{ route('customer.login.store') }}" method="post">
            @csrf
            <h2>Customer login</h2>

            @if(session('status'))
                <div style="padding:11px 12px;margin-bottom:14px;border-radius:6px;background:#fff3cf;color:#8a5a00;font-weight:800">
                    {{ session('status') }}
                </div>
            @endif

            <label>Email
                <input name="email" type="email" value="{{ old('email') }}" required autofocus>
            </label>
            @error('email') <div style="color:var(--danger);margin-top:6px">{{ $message }}</div> @enderror

            <label style="display:block;margin-top:14px">Password
                <input name="password" type="password" required>
            </label>
            @error('password') <div style="color:var(--danger);margin-top:6px">{{ $message }}</div> @enderror

            <label style="display:flex;gap:8px;align-items:center;margin-top:14px">
                <input type="checkbox" name="remember" value="1" style="width:auto;margin:0">
                Remember me
            </label>

            <div class="auth-actions">
                <button class="btn" type="submit">Login</button>
                <a class="btn secondary" href="{{ route('customer.register') }}">Create account</a>
            </div>
        </form>
    </section>
@endsection
```

### 5.4 `resources/views/account/auth/register.blade.php`

```blade
@extends('shop.layout')

@section('title', 'Register - CEC Electronic')

@section('content')
    <section class="auth-shell">
        <div class="panel auth-hero">
            <h1>Create your CEC customer account</h1>
            <p>Register once and keep your order history, delivery details, and warranty support records connected to your email.</p>
            <div class="service-row" style="grid-template-columns:1fr 1fr;margin-top:22px">
                <div class="service" style="background:rgba(255,255,255,.1);border-radius:8px"><span class="service-icon">DL</span><span><strong style="color:#fff">Delivery</strong><span style="color:#d9e7f7">Track status.</span></span></div>
                <div class="service" style="background:rgba(255,255,255,.1);border-radius:8px"><span class="service-icon">SP</span><span><strong style="color:#fff">Support</strong><span style="color:#d9e7f7">Faster help.</span></span></div>
            </div>
        </div>

        <form class="panel auth-card" action="{{ route('customer.register.store') }}" method="post">
            @csrf
            <h2>Register account</h2>

            <label>Name
                <input name="name" value="{{ old('name') }}" required autofocus>
            </label>
            @error('name') <div style="color:var(--danger);margin-top:6px">{{ $message }}</div> @enderror

            <label style="display:block;margin-top:14px">Email
                <input name="email" type="email" value="{{ old('email') }}" required>
            </label>
            @error('email') <div style="color:var(--danger);margin-top:6px">{{ $message }}</div> @enderror

            <label style="display:block;margin-top:14px">Password
                <input name="password" type="password" required>
            </label>
            @error('password') <div style="color:var(--danger);margin-top:6px">{{ $message }}</div> @enderror

            <label style="display:block;margin-top:14px">Confirm password
                <input name="password_confirmation" type="password" required>
            </label>

            <div class="auth-actions">
                <button class="btn" type="submit">Create account</button>
                <a class="btn secondary" href="{{ route('customer.login') }}">Login</a>
            </div>
        </form>
    </section>
@endsection
```

---

## 6. Checkout & Payment

```
/checkout (login required) → POST /checkout → create Order (order number EH-YYYYMMDD-####) → clear the cart
   → generate a Bakong KHQR (valid 3 minutes) → /checkout/success shows the QR
   → the page checks GET /checkout/payment-status every 15s (Bakong is checked at most once a minute)
   → paid: payment_status = paid   |   expired: POST /checkout/regenerate-qr
```

**Routes** (`routes/web.php`)

```php
Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/checkout/regenerate-qr/{order}', [CheckoutController::class, 'regenerateQr'])->name('checkout.regenerate-qr');
Route::get('/checkout/payment-status/{order}', [CheckoutController::class, 'paymentStatus'])->name('checkout.payment-status');
```

### 6.1 `app/Http/Controllers/Storefront/CheckoutController.php`

```php
<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\BakongService;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private CheckoutService $checkoutService
    ) {
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()
                ->guest(route('customer.login'))
                ->with('status', 'Please login or register before checkout.');
        }

        $items = $this->cartService->items($request);
        $subtotal = $this->cartService->subtotal($request);

        return view('checkout.create', compact('items', 'subtotal'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()) {
            return redirect()
                ->guest(route('customer.login'))
                ->with('status', 'Please login or register before checkout.');
        }

        // A double-submitted "Place order" (double-click, back-button resubmit,
        // slow-network retry) would otherwise reach CheckoutService with an
        // already-cleared cart from the first successful submission and crash
        // with a raw 422 — fail soft here instead, before doing any work.
        if ($this->cartService->items($request)->isEmpty()) {
            return redirect()->route('shop.cart')->with('status', 'Your cart is empty.');
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'shipping_method' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $order = $this->checkoutService->createOrder($request, $data);

        if ($order->payment_method === 'bakong') {
            $this->issueQr($order);
        }

        return redirect()->route('checkout.success', $order)->with('status', 'Order placed.');
    }

    public function success(Request $request, Order $order): View
    {
        abort_unless($request->user() && $order->user_id === $request->user()->id, 403);

        $order->load('items');

        // The QR closes 3 minutes after it is issued (the deadline is baked into
        // the KHQR payload, so Bakong's app rejects it too). Never regenerate it
        // just because the page was revisited — that would reset the clock. Only
        // orders that have no QR deadline yet (created before the expiry existed)
        // get a fresh one; after expiry the customer asks for a new QR explicitly.
        if ($order->payment_method === 'bakong'
            && $order->payment_status === 'unpaid'
            && $order->bakong_qr_expires_at === null) {
            $this->issueQr($order);
        }

        return view('checkout.success', compact('order'));
    }

    public function regenerateQr(Request $request, Order $order): RedirectResponse
    {
        abort_unless($request->user() && $order->user_id === $request->user()->id, 403);

        if ($order->payment_method !== 'bakong' || $order->payment_status !== 'unpaid' || ! $order->bakongQrExpired()) {
            return redirect()->route('checkout.success', $order);
        }

        // A new QR has a different md5, so a payment made on the old QR in its
        // last minutes would never be seen again. Check the old one once first
        // (a customer-initiated action, so it may pass the automated daily cap).
        if ($order->bakong_qr_md5
            && app(BakongService::class)->checkTransactionByMd5($order->bakong_qr_md5, enforceBudget: false) !== null) {
            $order->update([
                'payment_status'       => 'paid',
                'payment_confirmed_at' => now(),
            ]);

            return redirect()->route('checkout.success', $order)->with('status', 'Payment received.');
        }

        $this->issueQr($order);

        return redirect()->route('checkout.success', $order)->with('status', 'A new QR code was generated.');
    }

    public function paymentStatus(Request $request, Order $order): JsonResponse
    {
        abort_unless($request->user() && $order->user_id === $request->user()->id, 403);

        // Bakong's check-transaction API is rate-limited to a small number of
        // requests per day for the whole store. The checkout page polls this
        // route every 15s while a tab is open, so throttling outbound Bakong
        // calls to the same 15s window did nothing — a single customer
        // leaving a tab open for the ~10 minute polling window could burn
        // nearly half the daily budget alone. Throttle well below the poll
        // rate instead, so the UI can still poll for a fast response without
        // every poll spending part of the shared daily quota.
        $throttleKey = "bakong-check:{$order->id}";

        if ($order->payment_status === 'unpaid' && $order->bakong_qr_md5) {
            $checkNow = app(BakongService::class)->allowOnce($throttleKey, 60);
            $finalCheck = false;

            // A customer can pay in the last seconds of the 3-minute QR window, after
            // the last throttled check and just before the page stops polling.
            // Give every order one extra check once its QR has expired so that
            // payment is still picked up (like regenerateQr, this is
            // customer-driven and once per order, so it may pass the daily cap).
            if (! $checkNow && $order->bakongQrExpired()) {
                $finalCheck = $checkNow = app(BakongService::class)->allowOnce("bakong-final-check:{$order->id}", 86400);
            }

            if ($checkNow) {
                $tx = app(BakongService::class)->checkTransactionByMd5(
                    $order->bakong_qr_md5,
                    enforceBudget: ! $finalCheck,
                );

                if ($tx !== null) {
                    $order->update([
                        'payment_status'      => 'paid',
                        'payment_confirmed_at' => now(),
                    ]);
                    $order->refresh();
                }
            }
        }

        return response()->json([
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
            'is_paid' => $order->payment_status === 'paid',
            'paid_at' => $order->payment_confirmed_at?->toIso8601String(),
            'qr_expired' => $order->payment_status !== 'paid' && $order->bakongQrExpired(),
        ]);
    }

    /**
     * Generate a fixed-amount QR (valid for the configured window) and store the
     * string, md5 (for payment polling) and deadline on the order.
     */
    private function issueQr(Order $order): void
    {
        $qrData = app(BakongService::class)->generateQrForOrder($order);

        if ($qrData) {
            $order->update([
                'bakong_qr_string'     => $qrData['qr'],
                'bakong_qr_md5'        => $qrData['md5'],
                'bakong_qr_expires_at' => $qrData['expires_at'],
            ]);
        }
    }
}
```

### 6.2 `app/Services/CheckoutService.php`

```php
<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(private CartService $cartService)
    {
    }

    public function createOrder(Request $request, array $data): Order
    {
        $items = $this->cartService->items($request);
        abort_if($items->isEmpty(), 422, 'Cart is empty.');

        return DB::transaction(function () use ($request, $data, $items) {
            $subtotal = $items->sum(fn (CartItem $item) => $item->line_total);
            $shippingTotal = 0;
            $paymentMethod = $data['payment_method'] ?? 'bakong';

            $order = Order::create([
                'order_number' => $this->orderNumber(),
                'user_id' => $request->user()?->id,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? $request->user()?->email,
                'customer_phone' => $data['customer_phone'],
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_confirmed_at' => null,
                'admin_payment_seen_at' => null,
                'payment_method' => $paymentMethod,
                'shipping_method' => $data['shipping_method'] ?? 'standard',
                'subtotal' => $subtotal,
                'shipping_total' => $shippingTotal,
                'discount_total' => 0,
                'grand_total' => $subtotal + $shippingTotal,
                'shipping_address' => [
                    'address_line_1' => $data['address_line_1'],
                    'address_line_2' => $data['address_line_2'] ?? null,
                    'city' => $data['city'],
                    'province' => $data['province'] ?? null,
                    'country' => $data['country'] ?? 'Cambodia',
                ],
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?: 'Deleted product',
                    'sku' => $item->product?->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ]);
            }

            $this->cartService->clear($request);

            return $order;
        });
    }

    private function orderNumber(): string
    {
        do {
            $number = 'EH-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
```

### 6.3 `app/Services/BakongService.php`

```php
<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BakongService
{
    private string $baseUrl;
    private string $accountUsername;
    private string $accountName;
    private string $accessToken;
    private string $merchantCity;
    private int $dailyCheckLimit;
    private int $qrExpirySeconds;

    public function __construct()
    {
        $this->baseUrl         = rtrim((string) config('services.bakong.base_url', ''), '/');
        $this->accountUsername = (string) config('services.bakong.account_username', '');
        $this->accountName     = (string) config('services.bakong.account_name', 'CEC Electronic');
        $this->accessToken     = (string) config('services.bakong.access_token', '');
        $this->merchantCity    = (string) config('services.bakong.merchant_city', 'Phnom Penh');
        $this->dailyCheckLimit = (int) config('services.bakong.daily_check_limit', 90);
        $this->qrExpirySeconds = max(1, (int) config('services.bakong.qr_expiry_seconds', 180));
    }

    public function isConfigured(): bool
    {
        return $this->accountUsername !== '';
    }

    private function http(): PendingRequest
    {
        // DNS to Bakong's API intermittently fails to resolve inside this
        // network — retry a couple of times before giving up on a single check.
        // throw: false keeps a plain non-2xx response (e.g. "not found") as a
        // normal response object instead of turning it into an exception —
        // only connection-level failures (DNS, timeout) should be retried/thrown.
        return Http::timeout(15)->retry(3, 500, throw: false)->acceptJson()->withToken($this->accessToken);
    }

    /**
     * Generate a fixed-amount KHQR string for an order — computed locally
     * following the NBC KHQR SDK spec, no API call needed.
     * Returns ['qr' => string, 'md5' => string, 'expires_at' => Carbon] or null if not configured.
     * The QR is only valid for services.bakong.qr_expiry_seconds (default 180 = 3 minutes).
     */
    public function generateQrForOrder(Order $order): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $qr = KhqrGenerator::individual(
            accountId:      $this->accountUsername,
            merchantName:   $this->accountName,
            merchantCity:   $this->merchantCity,
            amount:         (float) $order->grand_total,
            currency:       'USD',
            billNumber:     $order->order_number,
            expirationSeconds: $this->qrExpirySeconds,
        );

        return [
            'qr'         => $qr['qr'],
            'md5'        => $qr['md5'],
            'expires_at' => Carbon::createFromTimestamp($qr['expires_at']),
        ];
    }

    /**
     * Verify a payment against the official Bakong Open API using the MD5
     * hash of the KHQR string. Returns the transaction data once paid, or
     * null while unpaid / not yet found.
     *
     * Bakong caps this endpoint at a small number of requests per day for
     * the whole account. A shared daily counter guards every caller (live
     * customer polling and the background job alike) so the app can never
     * exceed that cap and get every pending order stuck until it resets.
     */
    /**
     * $enforceBudget can be set false for a deliberate, human-initiated check
     * (e.g. an admin clicking "verify now" on one order) — those are
     * naturally rate-limited by a person clicking a button, unlike automated
     * polling, so they're allowed past the shared daily cap that protects
     * against runaway automated usage. The check still counts toward the
     * shared counter so automated callers see accurate usage.
     */
    public function checkTransactionByMd5(string $md5, bool $enforceBudget = true): ?array
    {
        if ($this->baseUrl === '' || $this->accessToken === '') {
            return null;
        }

        if ($enforceBudget && $this->dailyBudgetExceeded()) {
            return null;
        }

        $this->recordDailyCheck();

        try {
            $response = $this->http()->post("{$this->baseUrl}/check_transaction_by_md5", [
                'md5' => $md5,
            ]);
        } catch (ConnectionException $e) {
            // Network/DNS hiccup reaching Bakong — treat as "not confirmed yet"
            // rather than blowing up the request; the next poll/job run retries.
            Log::warning('Bakong check_transaction_by_md5 connection failed', [
                'md5' => $md5,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful() || $response->json('responseCode') !== 0) {
            return null;
        }

        return $response->json('data');
    }

    /**
     * True the first time it is called for $key within $seconds — a throttle
     * that protects the shared Bakong quota. It must never stop a payment being
     * confirmed, so if the cache is unavailable (e.g. an unwritable cache
     * folder) it falls back to a per-session throttle instead of throwing, and
     * allows the call outright where there is no session (console/queue).
     */
    public function allowOnce(string $key, int $seconds): bool
    {
        try {
            return Cache::add($key, true, $seconds);
        } catch (Throwable $e) {
            Log::warning('Bakong throttle cache unavailable, using session fallback', ['message' => $e->getMessage()]);
        }

        $request = request();

        if (! $request->hasSession()) {
            return true;
        }

        $sessionKey = 'bakong_throttle.'.md5($key);
        $last = (int) $request->session()->get($sessionKey, 0);

        if ($last > 0 && (time() - $last) < $seconds) {
            return false;
        }

        $request->session()->put($sessionKey, time());

        return true;
    }

    private function dailyBudgetExceeded(): bool
    {
        if ($this->dailyCheckLimit <= 0) {
            return false;
        }

        try {
            return (int) Cache::get($this->dailyBudgetKey(), 0) >= $this->dailyCheckLimit;
        } catch (Throwable $e) {
            // The counter only protects the daily quota. An unwritable cache
            // must never stop a customer's payment from being confirmed.
            Log::warning('Bakong daily budget counter unavailable', ['message' => $e->getMessage()]);

            return false;
        }
    }

    private function recordDailyCheck(): void
    {
        try {
            $key = $this->dailyBudgetKey();

            Cache::add($key, 0, now()->endOfDay()->addSecond());
            Cache::increment($key);
        } catch (Throwable $e) {
            Log::warning('Bakong daily budget counter unavailable', ['message' => $e->getMessage()]);
        }
    }

    private function dailyBudgetKey(): string
    {
        return 'bakong:daily-checks:' . now()->toDateString();
    }
}
```

### 6.4 `app/Services/KhqrGenerator.php`

```php
<?php

namespace App\Services;

/**
 * Generates KHQR (EMV QR) strings locally following the NBC KHQR SDK specification.
 * No external API call required — everything is computed on the server.
 */
class KhqrGenerator
{
    private static function field(string $tag, string $value): string
    {
        return $tag . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
    }

    private static function crc16(string $data): string
    {
        $crc = 0xFFFF;
        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000)
                    ? (($crc << 1) ^ 0x1021) & 0xFFFF
                    : ($crc << 1) & 0xFFFF;
            }
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Generate a dynamic individual KHQR string with a fixed amount.
     *
     * @return array{qr: string, md5: string, expires_at: int} expires_at is a unix timestamp (seconds)
     */
    public static function individual(
        string $accountId,
        string $merchantName,
        string $merchantCity = 'Phnom Penh',
        float  $amount = 0,
        string $currency = 'USD',
        string $billNumber = '',
        int    $expirationSeconds = 86400
    ): array {
        $currencyCode = strtoupper($currency) === 'KHR' ? '116' : '840';

        // Tag 29 — individual account info
        $merchantAccount = self::field('29', self::field('00', $accountId));

        // Tag 62 — additional data (bill number)
        $additional = $billNumber
            ? self::field('62', self::field('01', substr($billNumber, 0, 25)))
            : '';

        // Tag 99 — KHQR timestamps in milliseconds
        $nowMs = (int) (microtime(true) * 1000);
        $expMs = $nowMs + ($expirationSeconds * 1000);
        $timestamps = self::field('99',
            self::field('00', (string) $nowMs) .
            self::field('01', (string) $expMs)
        );

        // Amount string — strip trailing zeros after decimal
        $amountField = '';
        if ($amount > 0) {
            $formatted = number_format($amount, 2, '.', '');
            $amountField = self::field('54', rtrim(rtrim($formatted, '0'), '.'));
        }

        $qr  = self::field('00', '01');                          // Payload Format Indicator
        $qr .= self::field('01', '12');                          // Point of Initiation (dynamic)
        $qr .= $merchantAccount;                                  // Merchant Account
        $qr .= self::field('52', '5999');                        // MCC
        $qr .= self::field('53', $currencyCode);                 // Currency
        $qr .= $amountField;                                      // Amount
        $qr .= self::field('58', 'KH');                          // Country Code
        $qr .= self::field('59', mb_substr($merchantName, 0, 25)); // Merchant Name
        $qr .= self::field('60', mb_substr($merchantCity, 0, 15)); // Merchant City
        $qr .= $additional;                                       // Bill Number
        $qr .= $timestamps;                                       // Timestamps
        $qr .= '6304';                                            // CRC tag + length placeholder

        $crc    = self::crc16($qr);
        $qrFull = $qr . $crc;

        return [
            'qr'  => $qrFull,
            'md5' => md5($qrFull),
            'expires_at' => intdiv($expMs, 1000),
        ];
    }
}
```

### 6.5 `app/Models/Order.php`

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

### 6.6 `app/Models/OrderItem.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'sku',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

### 6.7 `resources/views/checkout/create.blade.php`

```blade
@extends('shop.layout')

@section('title', 'Checkout - CEC Electronic')

@section('content')
    <div class="section-head">
        <h2>Checkout</h2>
        <a class="btn secondary" href="{{ route('shop.cart') }}">Back to cart</a>
    </div>

    @if($items->isEmpty())
        <div class="panel" style="padding:30px;text-align:center;color:var(--muted)">
            Your cart is empty. <a style="color:var(--brand);font-weight:800" href="{{ route('shop.home') }}">Continue shopping</a>
        </div>
    @else
        <ol class="checkout-steps">
            <li class="checkout-step is-done"><span class="checkout-step-num">&#10003;</span><span>Cart</span></li>
            <li class="checkout-step-line is-done"></li>
            <li class="checkout-step is-active"><span class="checkout-step-num">2</span><span>Checkout</span></li>
            <li class="checkout-step-line"></li>
            <li class="checkout-step"><span class="checkout-step-num">3</span><span>Confirmation</span></li>
        </ol>

        <form class="checkout" action="{{ route('checkout.store') }}" method="post" data-checkout-form>
            @csrf
            <div>
                <div class="panel checkout-section">
                    <div class="checkout-section-head">
                        <span class="icon">&#9993;</span>
                        <h3>Contact & delivery details</h3>
                    </div>
                    <div class="field-grid">
                        <label>Full name<input name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required></label>
                        <label>Phone<input name="customer_phone" value="{{ old('customer_phone') }}" required></label>
                        <label>Email<input name="customer_email" type="email" value="{{ old('customer_email', auth()->user()?->email) }}"></label>
                        <label>City<input name="city" value="{{ old('city', 'Phnom Penh') }}" required></label>
                        <label style="grid-column:1 / -1">Address line 1<input name="address_line_1" value="{{ old('address_line_1') }}" required></label>
                        <label style="grid-column:1 / -1">Address line 2<input name="address_line_2" value="{{ old('address_line_2') }}"></label>
                        <label>Province<input name="province" value="{{ old('province') }}"></label>
                        <label>Country<input name="country" value="{{ old('country', 'Cambodia') }}"></label>
                        <label style="grid-column:1 / -1">Notes<textarea name="notes" style="min-height:80px">{{ old('notes') }}</textarea></label>
                    </div>
                    @if($errors->any())
                        <div style="color:#e11d48;margin-top:12px;font-weight:700">Please check the form fields.</div>
                    @endif
                </div>

                <div class="panel checkout-section">
                    <div class="checkout-section-head">
                        <span class="icon">&#128666;</span>
                        <h3>Shipping method</h3>
                    </div>
                    <label>
                        <select name="shipping_method">
                            <option value="standard">Standard delivery — Free</option>
                            <option value="express">Same-day Phnom Penh</option>
                        </select>
                    </label>
                </div>

                <div class="panel checkout-section">
                    <div class="checkout-section-head">
                        <span class="icon">&#128179;</span>
                        <h3>Payment method</h3>
                    </div>
                    <div class="payment-options" data-payment-options>
                        <label class="payment-option is-checked">
                            <input type="radio" name="payment_method" value="bakong" data-payment-method checked>
                            <span class="payment-option-icon">&#128241;</span>
                            <span class="payment-option-body">
                                <strong>KHQR (Bakong)</strong>
                                <span>Pay instantly with ABA, ACLEDA, Wing, or any Bakong app</span>
                            </span>
                            <span class="payment-option-check"></span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cash_on_delivery" data-payment-method>
                            <span class="payment-option-icon">&#128176;</span>
                            <span class="payment-option-body">
                                <strong>Cash on delivery</strong>
                                <span>Pay with cash when your order arrives</span>
                            </span>
                            <span class="payment-option-check"></span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer" data-payment-method>
                            <span class="payment-option-icon">&#127974;</span>
                            <span class="payment-option-body">
                                <strong>Bank transfer</strong>
                                <span>Transfer to our bank account and we'll confirm manually</span>
                            </span>
                            <span class="payment-option-check"></span>
                        </label>
                    </div>
                </div>
            </div>

            <aside class="panel checkout-section order-summary">
                <div class="checkout-section-head">
                    <span class="icon">&#128722;</span>
                    <h3>Order summary</h3>
                </div>
                @foreach($items as $item)
                    <div class="order-summary-item">
                        <span class="order-summary-thumb">
                            <img src="{{ $item->product?->image_url }}" alt="{{ $item->product?->name }}">
                        </span>
                        <span class="order-summary-info">
                            <strong>{{ $item->product?->name }}</strong>
                            <span>Qty {{ $item->quantity }}</span>
                        </span>
                        <span class="order-summary-price">${{ number_format($item->line_total, 2) }}</span>
                    </div>
                @endforeach
                <hr style="border:0;border-top:1px solid var(--line);margin:14px 0">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                    <span>Subtotal</span><strong>${{ number_format($subtotal, 2) }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:16px">
                    <span>Delivery</span><strong style="color:var(--success)">Free</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:16px;font-size:18px;border-top:1px solid var(--line);padding-top:14px">
                    <span>Total</span><strong data-payment-total>${{ number_format($subtotal, 2) }}</strong>
                </div>
                <button class="btn" style="width:100%" type="submit">Place order</button>
                <div class="trust-row">&#128274; Secure checkout &nbsp;•&nbsp; Buyer protection on every order</div>
            </aside>
        </form>

        @push('scripts')
            <script>
                (function () {
                    var form = document.querySelector('[data-checkout-form]');
                    if (! form) return;

                    // Prevents a double-click (or slow network + impatient click)
                    // from submitting the order twice — the second submission
                    // would otherwise hit an already-cleared cart and error out.
                    form.addEventListener('submit', function () {
                        var button = form.querySelector('button[type="submit"]');
                        if (! button) return;

                        window.setTimeout(function () {
                            button.disabled = true;
                            button.textContent = 'Placing order…';
                        }, 0);
                    });

                    var options = document.querySelector('[data-payment-options]');
                    if (options) {
                        options.addEventListener('change', function (event) {
                            if (! event.target.matches('[data-payment-method]')) return;

                            options.querySelectorAll('.payment-option').forEach(function (option) {
                                var input = option.querySelector('[data-payment-method]');
                                option.classList.toggle('is-checked', !!(input && input.checked));
                            });
                        });
                    }
                })();
            </script>
        @endpush
    @endif
@endsection
```

### 6.8 `resources/views/checkout/success.blade.php`

```blade
@extends('shop.layout')

@section('title', 'Order Placed - CEC Electronic')

@section('content')
    @php
        $needsBakongPayment = $order->payment_method === 'bakong' && $order->payment_status !== 'paid';
        $hasQrString        = $needsBakongPayment && $order->bakong_qr_string;
        $isPaid             = $order->payment_status === 'paid';
        $qrExpired          = $hasQrString && $order->bakongQrExpired();
        // Remaining seconds are computed server-side so the countdown doesn't
        // depend on the customer's device clock being correct.
        $qrSecondsLeft      = $hasQrString && $order->bakong_qr_expires_at
            ? max(0, (int) now()->diffInSeconds($order->bakong_qr_expires_at, false))
            : null;
    @endphp

    <div class="panel receipt-card">
        <div class="success-hero">
            <div class="check">&#10003;</div>
            <h1>Order placed successfully!</h1>
            <p>Thank you, {{ $order->customer_name }}. We've received your order.</p>
        </div>

        <div class="receipt-body">
            <div class="receipt-order-no">Order number<br><strong>{{ $order->order_number }}</strong></div>

            <div class="receipt-row">
                <span>Payment method</span>
                <strong>{{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')) }}</strong>
            </div>
            <div class="receipt-row">
                <span>Payment status</span>
                <strong data-order-payment-label>
                    <span class="status-pill {{ $isPaid ? 'is-paid' : 'is-pending' }}">
                        {{ $isPaid ? '● Paid' : '● Pending' }}
                    </span>
                </strong>
            </div>
            <div class="receipt-row is-total">
                <span>Total</span>
                <strong>${{ number_format($order->grand_total, 2) }}</strong>
            </div>

            @if($needsBakongPayment)
                <div class="payment-note" style="margin-top:18px" data-payment-waiting-note>
                    Please scan the KHQR code and pay. This page updates automatically once payment is confirmed.
                </div>
            @endif

            <div class="next-steps">
                <div>
                    <span class="num">1</span>
                    <strong>Processing</strong>
                    <span>We're preparing your order</span>
                </div>
                <div>
                    <span class="num">2</span>
                    <strong>Shipping</strong>
                    <span>Out for delivery</span>
                </div>
                <div>
                    <span class="num">3</span>
                    <strong>Delivered</strong>
                    <span>Enjoy your purchase</span>
                </div>
            </div>

            <div class="receipt-actions">
                @if($needsBakongPayment)
                    <button type="button" class="btn" data-payment-reopen hidden>Pay with KHQR</button>
                @endif
                <a class="btn" href="{{ route('account.orders.receipt', $order) }}" data-receipt-link @unless($isPaid) hidden @endunless>Download receipt</a>
                <a class="btn secondary" href="{{ route('shop.home') }}">Continue shopping</a>
                <a class="btn" href="{{ route('account.orders') }}">View orders</a>
            </div>
        </div>
    </div>

    @if($needsBakongPayment)
        <div class="modal-backdrop is-open" data-payment-modal aria-hidden="false">
            <div class="payment-modal" role="dialog" aria-modal="true" aria-labelledby="payment-title">
                <div class="payment-modal-head" data-payment-modal-head>
                    <h3 id="payment-title">Pay with KHQR — ${{ number_format($order->grand_total, 2) }}</h3>
                </div>
                <div class="payment-modal-body" data-payment-modal-body>

                    @if($hasQrString)
                        {{-- Raw KHQR string rendered as a plain QR code in-browser --}}
                        <div class="payment-row">
                            <span>Order</span><strong>{{ $order->order_number }}</strong>
                        </div>
                        <div class="payment-row">
                            <span>Amount</span><strong>${{ number_format($order->grand_total, 2) }}</strong>
                        </div>
                        <div data-qr-active @if($qrExpired) hidden @endif style="text-align:center;padding:16px 0 8px">
                            <div id="khqr-canvas" style="max-width:260px;width:260px;display:inline-block;margin:0 auto;border-radius:8px;overflow:hidden;padding:10px;background:#fff;border:1px solid var(--line)"></div>
                            @if($qrSecondsLeft !== null)
                                <p style="font-size:14px;font-weight:700;margin:10px 0 0">
                                    QR expires in <span data-qr-countdown style="font-variant-numeric:tabular-nums">--:--</span>
                                </p>
                            @endif
                            <p style="font-size:13px;color:var(--muted);margin:10px 0 0">
                                Scan with ABA, ACLEDA, Wing, Bakong, or any KHQR-supported app.<br>
                                <strong>Amount ${{ number_format($order->grand_total, 2) }} is fixed — cannot be changed.</strong>
                            </p>
                            <button type="button" class="btn secondary" data-payment-close style="width:100%;margin-top:14px">Close</button>
                        </div>
                        <div data-qr-expired @if(! $qrExpired) hidden @endif style="text-align:center;padding:24px 12px">
                            <p style="font-weight:700;margin:0 0 6px">This QR code has expired</p>
                            <p style="color:var(--muted);margin:0 0 16px;font-size:13px">
                                QR codes are valid for {{ round((int) config('services.bakong.qr_expiry_seconds', 180) / 60) }} minutes. Do not pay an expired code — generate a new one.
                            </p>
                            <div style="display:flex;gap:10px">
                                <form method="POST" action="{{ route('checkout.regenerate-qr', $order) }}" style="flex:1;margin:0">
                                    @csrf
                                    <button type="submit" class="btn" style="width:100%">Generate new QR</button>
                                </form>
                                <button type="button" class="btn secondary" data-payment-close style="flex:1">Close</button>
                            </div>
                        </div>
                        @push('scripts')
                        {{-- Self-hosted: don't depend on an external CDN to render a payment QR --}}
                        <script src="{{ asset('vendor/qrcodejs/qrcode.min.js') }}"></script>
                        <script>
                            (function () {
                                var canvas = document.getElementById('khqr-canvas');
                                if (! canvas || @json($qrExpired)) return;
                                var qrString = @json($order->bakong_qr_string);
                                var qr = new QRCode(canvas, {
                                    text:   qrString,
                                    width:  240,
                                    height: 240,
                                    correctLevel: QRCode.CorrectLevel.M,
                                });
                            })();
                        </script>
                        @endpush

                    @else
                        <div style="text-align:center;padding:24px 12px">
                            <p style="font-weight:700;margin:0 0 6px">KHQR payment is not configured.</p>
                            <p style="color:var(--muted);margin:0 0 16px;font-size:13px">
                                Set <code>BAKONG_ACCOUNT_USERNAME</code> and <code>BAKONG_ACCESS_TOKEN</code> in your .env file.
                            </p>
                            <div style="background:var(--surface,#f4f4f5);border-radius:8px;padding:14px;text-align:left;font-size:14px">
                                <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                                    <span style="color:var(--muted)">Order</span>
                                    <strong>{{ $order->order_number }}</strong>
                                </div>
                                <div style="display:flex;justify-content:space-between">
                                    <span style="color:var(--muted)">Amount due</span>
                                    <strong>${{ number_format($order->grand_total, 2) }}</strong>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="payment-note" data-payment-status-message style="margin-top:12px">
                        &#8987; Waiting for payment confirmation…
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                (function () {
                    var modal         = document.querySelector('[data-payment-modal]');
                    var modalHead     = document.querySelector('[data-payment-modal-head]');
                    var modalBody     = document.querySelector('[data-payment-modal-body]');
                    var statusMessage = document.querySelector('[data-payment-status-message]');
                    var waitingNote   = document.querySelector('[data-payment-waiting-note]');
                    var paymentLabel  = document.querySelector('[data-order-payment-label]');
                    var statusUrl     = @json(route('checkout.payment-status', $order));
                    var ordersUrl     = @json(route('account.orders'));
                    var receiptUrl    = @json(route('account.orders.receipt', $order));
                    var receiptLink   = document.querySelector('[data-receipt-link]');
                    var orderNumber   = @json($order->order_number);
                    var amountLabel   = @json('$' . number_format($order->grand_total, 2));
                    var qrActive      = document.querySelector('[data-qr-active]');
                    var qrExpiredBox  = document.querySelector('[data-qr-expired]');
                    var countdownEl   = document.querySelector('[data-qr-countdown]');
                    var secondsLeft   = @json($qrSecondsLeft);
                    var attempts = 0;
                    var timer;
                    var countdownTimer;

                    if (! modal || ! statusUrl) return;

                    var reopenBtn = document.querySelector('[data-payment-reopen]');

                    var isPaid = false;

                    function setModalOpen(open) {
                        modal.classList.toggle('is-open', open);
                        modal.setAttribute('aria-hidden', open ? 'false' : 'true');
                        if (reopenBtn) reopenBtn.hidden = open || isPaid;
                    }

                    modal.querySelectorAll('[data-payment-close]').forEach(function (btn) {
                        btn.addEventListener('click', function () { setModalOpen(false); });
                    });
                    if (reopenBtn) reopenBtn.addEventListener('click', function () { setModalOpen(true); });

                    function showExpired() {
                        window.clearInterval(timer);
                        window.clearInterval(countdownTimer);
                        if (qrActive) qrActive.hidden = true;
                        if (qrExpiredBox) qrExpiredBox.hidden = false;
                        if (statusMessage) statusMessage.textContent = 'This QR code has expired.';
                        if (waitingNote) waitingNote.textContent = 'The QR code expired. Generate a new one to pay.';
                    }

                    function renderCountdown() {
                        if (! countdownEl) return;
                        var m = Math.floor(secondsLeft / 60);
                        var sec = secondsLeft % 60;
                        countdownEl.textContent = (m < 10 ? '0' : '') + m + ':' + (sec < 10 ? '0' : '') + sec;
                    }

                    function markPaid() {
                        window.clearInterval(timer);
                        // Polling continues while the popup is closed, so show the
                        // success popup (with the receipt button) even then.
                        isPaid = true;
                        setModalOpen(true);

                        if (modalHead) {
                            modalHead.innerHTML = '<h3 id="payment-title">Payment successful</h3>';
                        }
                        if (modalBody) {
                            modalBody.innerHTML =
                                '<div style="text-align:center;padding:12px 4px 4px">' +
                                    '<div style="width:64px;height:64px;border-radius:50%;background:#ecfdf3;color:#087443;' +
                                        'display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:32px;line-height:1">&#10003;</div>' +
                                    '<h4 style="margin:0 0 6px;font-size:18px;color:var(--ink)">Payment successful!</h4>' +
                                    '<p style="margin:0 0 18px;color:var(--muted)">Order <strong>' + orderNumber + '</strong> — ' + amountLabel + ' paid via KHQR.</p>' +
                                    '<a class="btn" href="' + receiptUrl + '" style="width:100%;display:block;box-sizing:border-box;margin-bottom:10px">Download receipt</a>' +
                                    '<button type="button" class="btn secondary" data-payment-success-close style="width:100%">Continue</button>' +
                                '</div>';

                            var closeBtn = modalBody.querySelector('[data-payment-success-close]');
                            if (closeBtn) {
                                closeBtn.addEventListener('click', function () {
                                    setModalOpen(false);
                                    window.location.href = ordersUrl;
                                });
                            }
                        }

                        if (receiptLink) receiptLink.hidden = false;

                        if (paymentLabel) {
                            paymentLabel.innerHTML = '<span class="status-pill is-paid">&#9679; Paid</span>';
                        }
                        if (waitingNote) {
                            waitingNote.textContent = 'Payment received. Your order is being processed.';
                            waitingNote.style.background = '#ecfdf3';
                            waitingNote.style.color = '#087443';
                        }
                    }

                    // Bakong's transaction-check API allows very few requests per
                    // day for the whole store, so this polls slowly and stops
                    // after a while rather than hammering it while a tab sits open.
                    var POLL_INTERVAL_MS = 15000;
                    var MAX_ATTEMPTS     = 40; // ~10 minutes

                    function checkPayment() {
                        attempts += 1;

                        if (attempts > MAX_ATTEMPTS) {
                            window.clearInterval(timer);
                            if (statusMessage) {
                                statusMessage.textContent = 'Still not confirmed. If you already paid, refresh this page in a minute — no need to pay again.';
                            }
                            return;
                        }

                        fetch(statusUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                        .then(function (data) {
                            if (data.is_paid) { window.clearInterval(countdownTimer); markPaid(); return; }
                            if (data.qr_expired) { showExpired(); return; }
                            if (statusMessage && attempts % 2 === 0) {
                                statusMessage.textContent = 'Still waiting — keep this page open after scanning.';
                            }
                        })
                        .catch(function () {
                            if (statusMessage) statusMessage.textContent = 'Checking payment status…';
                        });
                    }

                    // The QR window is over. A payment made in its last seconds (or
                    // just before this page was reloaded) is still worth one last
                    // look before telling the customer it expired.
                    function finalCheck() {
                        window.clearInterval(timer);
                        window.clearInterval(countdownTimer);
                        if (statusMessage) statusMessage.textContent = 'Checking your payment…';

                        window.setTimeout(function () {
                            fetch(statusUrl, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                            .then(function (data) { if (data.is_paid) { markPaid(); } else { showExpired(); } })
                            .catch(showExpired);
                        }, 2000);
                    }

                    window.addEventListener('message', function (e) {
                        if (e.data && e.data.event === 'payment_success') checkPayment();
                    });

                    if (secondsLeft === null) {
                        timer = window.setInterval(checkPayment, POLL_INTERVAL_MS);
                        checkPayment();
                    } else if (secondsLeft <= 0) {
                        finalCheck();
                    } else {
                        renderCountdown();
                        countdownTimer = window.setInterval(function () {
                            secondsLeft -= 1;
                            if (secondsLeft <= 0) { finalCheck(); return; }
                            renderCountdown();
                        }, 1000);
                        timer = window.setInterval(checkPayment, POLL_INTERVAL_MS);
                        checkPayment();
                    }
                })();
            </script>
        @endpush
    @endif
@endsection
```

---

## 7. My Account & Receipt

- `/account` shows the 10 newest orders. `/account/orders` shows all orders, 10 per page. Customers can only see their own orders.
- Receipts are PDFs (download or view in the browser) made from `receipts/order.blade.php`.

**Routes** (`routes/web.php`)

```php
Route::prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AccountController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/receipt', [AccountController::class, 'receipt'])->name('orders.receipt');
    Route::get('/orders/{order}/receipt/view', [AccountController::class, 'viewReceipt'])->name('orders.receipt.view');
});
```

### 7.1 `app/Http/Controllers/Customer/AccountController.php`

```php
<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ReceiptPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(Request $request): View|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('customer.login');
        }

        $orders = Order::query()
            ->when($request->user(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->take(10)
            ->get();

        return view('account.dashboard', compact('orders'));
    }

    public function orders(Request $request): View|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('customer.login');
        }

        $orders = Order::query()
            ->when($request->user(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(10);

        return view('account.orders', compact('orders'));
    }

    public function show(Request $request, Order $order): View|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('customer.login');
        }

        $order->load(['items.product', 'deliveryProvider', 'deliveryZone']);

        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        return view('account.order-show', compact('order'));
    }

    public function receipt(Request $request, Order $order, ReceiptPdf $receipts): Response|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('customer.login');
        }

        abort_unless($order->user_id === $request->user()->id, 403);

        return $receipts->download($order);
    }

    public function viewReceipt(Request $request, Order $order, ReceiptPdf $receipts): Response|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('customer.login');
        }

        abort_unless($order->user_id === $request->user()->id, 403);

        return $receipts->stream($order);
    }
}
```

### 7.2 `app/Services/ReceiptPdf.php`

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

### 7.3 `resources/views/account/dashboard.blade.php`

```blade
@extends('shop.layout')

@section('title', 'My Account - CEC Electronic')

@section('content')
    <div class="section-head">
        <div>
            <h2>My account</h2>
            <p>Track your CEC Electronic orders, payment status, and delivery progress.</p>
        </div>
        <span style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn secondary" href="{{ route('account.orders') }}">Order history</a>
            <form action="{{ route('customer.logout') }}" method="post">
                @csrf
                <button class="btn secondary" type="submit">Logout</button>
            </form>
        </span>
    </div>

    <section class="service-row">
        <div class="panel service"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/order-icon.png') }}" alt="Orders"></span><span><strong>Orders</strong><span>{{ $orders->count() }} recent orders found.</span></span></div>
        <div class="panel service"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/delivery-icon.png') }}" alt="Delivery"></span><span><strong>Delivery</strong><span>Track assigned delivery provider and status.</span></span></div>
        <div class="panel service"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/warranty-icon.jpeg') }}" alt="Warranty"></span><span><strong>Warranty</strong><span>Keep order numbers for service support.</span></span></div>
        <div class="panel service"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/support-icon.avif') }}" alt="Support"></span><span><strong>Support</strong><span>Call 012 220 152 for help.</span></span></div>
    </section>

    <div class="panel" style="padding:18px">
        <h3 style="margin-top:0">Recent orders</h3>
        @forelse($orders as $order)
            <a href="{{ route('account.orders.show', $order) }}" style="display:grid;grid-template-columns:1fr 150px 120px;gap:12px;border-bottom:1px solid var(--line);padding:12px 0;align-items:center">
                <span>
                    <strong>{{ $order->order_number }}</strong>
                    <span class="sku" style="display:block">{{ $order->created_at->format('M d, Y') }}</span>
                </span>
                <span>{{ ucfirst($order->status) }}</span>
                <strong style="text-align:right">${{ number_format($order->grand_total, 2) }}</strong>
            </a>
        @empty
            <p style="color:var(--muted)">No orders yet.</p>
            <a class="btn" href="{{ route('shop.home') }}">Start shopping</a>
        @endforelse
    </div>
@endsection
```

### 7.4 `resources/views/account/orders.blade.php`

```blade
@extends('shop.layout')

@section('title', 'Order History - CEC Electronic')

@section('content')
    <div class="section-head">
        <h2>Order history</h2>
        <a class="btn secondary" href="{{ route('account.dashboard') }}">Account</a>
    </div>

    <div class="panel" style="padding:18px">
        @forelse($orders as $order)
            <div style="border-bottom:1px solid var(--line);padding:14px 0">
                <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:6px 16px">
                    <div style="flex:1 1 170px;min-width:0">
                        <strong style="white-space:nowrap">{{ $order->order_number }}</strong>
                        <div class="sku">{{ $order->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    <span>{{ ucfirst($order->status) }} &middot; {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</span>
                    <strong style="min-width:70px;margin-left:auto;text-align:right">${{ number_format($order->grand_total, 2) }}</strong>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
                    <a class="btn secondary" href="{{ route('account.orders.show', $order) }}">About order</a>
                    @if($order->payment_status === 'paid')
                        <a class="btn secondary" href="{{ route('account.orders.receipt.view', $order) }}" target="_blank" rel="noopener">Receipt</a>
                        <a class="btn" href="{{ route('account.orders.receipt', $order) }}">Download receipt</a>
                    @else
                        <span style="color:var(--muted);font-size:13px;align-self:center">Receipt available after payment</span>
                    @endif
                </div>
            </div>
        @empty
            <p style="color:var(--muted)">No orders yet.</p>
        @endforelse

        <div style="margin-top:16px">{{ $orders->links() }}</div>
    </div>
@endsection
```

### 7.5 `resources/views/account/order-show.blade.php`

```blade
@extends('shop.layout')

@section('title', $order->order_number.' - CEC Electronic')

@section('content')
    <div class="section-head">
        <div>
            <h2>{{ $order->order_number }}</h2>
            <p>Placed {{ $order->created_at->format('M d, Y h:i A') }}</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @if($order->payment_status === 'paid')
                <a class="btn secondary" href="{{ route('account.orders.receipt.view', $order) }}" target="_blank" rel="noopener">View receipt</a>
                <a class="btn" href="{{ route('account.orders.receipt', $order) }}">Download receipt</a>
            @endif
            <a class="btn secondary" href="{{ route('account.orders') }}">Back to orders</a>
        </div>
    </div>

    <section class="checkout">
        <div class="panel" style="padding:18px">
            <h3 style="margin-top:0">Items</h3>
            @foreach($order->items as $item)
                <div style="display:grid;grid-template-columns:1fr 80px 110px;gap:12px;border-bottom:1px solid var(--line);padding:12px 0;align-items:center">
                    <div>
                        <strong>{{ $item->product_name }}</strong>
                        <div class="sku">{{ $item->sku ?: 'No SKU' }}</div>
                    </div>
                    <span>x {{ $item->quantity }}</span>
                    <strong style="text-align:right">${{ number_format($item->line_total, 2) }}</strong>
                </div>
            @endforeach
        </div>

        <aside class="panel" style="padding:18px">
            <h3 style="margin-top:0">Order status</h3>
            <div class="spec-table">
                <div class="spec-row"><span>Status</span><strong>{{ ucfirst($order->status) }}</strong></div>
                <div class="spec-row"><span>Payment</span><strong>{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</strong></div>
                <div class="spec-row"><span>Delivery</span><strong>{{ $order->deliveryProvider?->name ?: ucfirst((string) $order->shipping_method) }}</strong></div>
                <div class="spec-row"><span>Tracking</span><strong>{{ $order->tracking_number ?: 'Not assigned yet' }}</strong></div>
                <div class="spec-row"><span>Subtotal</span><strong>${{ number_format($order->subtotal, 2) }}</strong></div>
                <div class="spec-row"><span>Delivery fee</span><strong>${{ number_format($order->shipping_total, 2) }}</strong></div>
                <div class="spec-row"><span>Total</span><strong>${{ number_format($order->grand_total, 2) }}</strong></div>
            </div>

            @if($order->shipping_address)
                <h3>Delivery address</h3>
                <p style="color:var(--muted);line-height:1.7">
                    {{ $order->shipping_address['address_line_1'] ?? '' }}<br>
                    {{ $order->shipping_address['address_line_2'] ?? '' }}<br>
                    {{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['province'] ?? '' }}
                </p>
            @endif
        </aside>
    </section>
@endsection
```

### 7.6 `resources/views/receipts/order.blade.php`

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $order->order_number }}</title>
    {{-- dompdf: table layout and DejaVu Sans only (no flex/grid, and DejaVu has the "$" glyphs). --}}
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }

        .topbar { height: 10px; background: #0057a8; }
        .topbar-accent { height: 3px; background: #f6b300; }
        .page { padding: 26px 44px 0; }

        .company-name { font-size: 18px; font-weight: bold; color: #063a74; margin: 0 0 3px; }
        .company-line { color: #6b7280; font-size: 10px; line-height: 1.6; }
        .doc-title { font-size: 24px; font-weight: bold; color: #0057a8; letter-spacing: 2px; text-align: right; margin: 0 0 6px; }
        .doc-meta { text-align: right; line-height: 1.7; color: #4b5563; }
        .doc-meta strong { color: #1f2937; }
        .badge { display: inline-block; margin-top: 6px; padding: 3px 14px; background: #ecfdf3; border: 1px solid #087443;
                 color: #087443; font-weight: bold; letter-spacing: 2px; font-size: 11px; }

        .rule { border-top: 1px solid #d1d5db; margin: 20px 0; }

        .boxes td { width: 50%; padding: 0; }
        .boxes td.box { padding: 12px 14px; }
        .box { background: #f5f8fc; border-left: 3px solid #0057a8; line-height: 1.65; }
        .box-gap { width: 16px !important; }
        .label { font-size: 9px; font-weight: bold; color: #0057a8; text-transform: uppercase; letter-spacing: 1.4px; margin-bottom: 4px; }
        .box .name { font-size: 12px; font-weight: bold; color: #111827; }
        .kv td { padding: 1px 0; }
        .kv td:first-child { color: #6b7280; width: 42%; }

        .items { margin-top: 22px; }
        .items th { background: #063a74; color: #fff; text-align: left; padding: 9px 10px; font-size: 10px;
                    text-transform: uppercase; letter-spacing: 1px; }
        .items td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        .items tr.alt td { background: #f9fafb; }
        .items .num { text-align: right; white-space: nowrap; }
        .items .idx { width: 28px; color: #9ca3af; }
        .sku { color: #6b7280; font-size: 9px; margin-top: 2px; }

        .summary td { padding: 0; }
        .totals { margin-top: 14px; }
        .totals td { padding: 5px 10px; }
        .totals .val { text-align: right; white-space: nowrap; }
        .totals .grand td { background: #0057a8; color: #fff; font-size: 14px; font-weight: bold; padding: 10px; }
        .note { margin-top: 14px; padding-right: 20px; color: #4b5563; line-height: 1.7; font-size: 10px; }
        .note strong { color: #087443; }

        .footer { position: fixed; left: 0; right: 0; bottom: 0; }
        .footer-inner { padding: 12px 44px 14px; border-top: 1px solid #d1d5db; text-align: center; color: #6b7280; font-size: 9px; line-height: 1.7; }
        .footer-inner strong { color: #063a74; font-size: 10px; }
    </style>
</head>
<body>
    @php
        $address = $order->shipping_address ?? [];
        $addressLines = array_filter([
            $address['address_line_1'] ?? null,
            $address['address_line_2'] ?? null,
            trim(($address['city'] ?? '').(! empty($address['province']) ? ', '.$address['province'] : ''), ', '),
            $address['country'] ?? null,
        ]);
        $paidAt = $order->payment_confirmed_at ?? $order->updated_at;
        $method = $order->payment_method === 'bakong'
            ? 'KHQR (Bakong)'
            : ucfirst(str_replace('_', ' ', (string) $order->payment_method));
    @endphp

    <div class="topbar"></div>
    <div class="topbar-accent"></div>

    <div class="footer">
        <div class="footer-inner">
            <strong>Thank you for shopping with CEC Electronic!</strong><br>
            This is a computer-generated receipt and does not require a signature.<br>
            Hotline 012 220 152 / 093 456 747 &nbsp;&middot;&nbsp; Phnom Penh, Cambodia
        </div>
    </div>

    <div class="page">
        <table>
            <tr>
                <td style="width:56%">
                    <table>
                        <tr>
                            <td style="width:92px">
                                <img src="{{ public_path('images/receipt-logo.png') }}" alt="CEC" style="width:80px;height:40px">
                            </td>
                            <td>
                                <div class="company-name">CEC Electronic</div>
                                <div class="company-line">
                                    Computer, laptop &amp; IT store<br>
                                    Phnom Penh, Cambodia<br>
                                    012 220 152 / 093 456 747
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:44%">
                    <div class="doc-title">RECEIPT</div>
                    <div class="doc-meta">
                        Receipt no. <strong>{{ $order->order_number }}</strong><br>
                        Date paid <strong>{{ $paidAt->format('M d, Y') }}</strong><br>
                        <span class="badge">PAID</span>
                    </div>
                </td>
            </tr>
        </table>

        <div class="rule"></div>

        <table class="boxes">
            <tr>
                <td class="box">
                    <div>
                        <div class="label">Billed to</div>
                        <div class="name">{{ $order->customer_name }}</div>
                        @if($order->customer_phone){{ $order->customer_phone }}<br>@endif
                        @if($order->customer_email){{ $order->customer_email }}<br>@endif
                        @foreach($addressLines as $line){{ $line }}<br>@endforeach
                    </div>
                </td>
                <td class="box-gap"></td>
                <td class="box">
                    <div>
                        <div class="label">Payment details</div>
                        <table class="kv">
                            <tr><td>Method</td><td>{{ $method }}</td></tr>
                            <tr><td>Paid on</td><td>{{ $paidAt->format('M d, Y h:i A') }}</td></tr>
                            <tr><td>Status</td><td><strong style="color:#087443">Paid in full</strong></td></tr>
                            @if($order->deliveryProvider?->name || $order->shipping_method)
                                <tr><td>Delivery</td><td>{{ $order->deliveryProvider?->name ?: ucfirst((string) $order->shipping_method) }}</td></tr>
                            @endif
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th class="idx">#</th>
                    <th>Description</th>
                    <th class="num">Qty</th>
                    <th class="num">Unit price</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr class="{{ $loop->even ? 'alt' : '' }}">
                        <td class="idx">{{ $loop->iteration }}</td>
                        <td>
                            {{ $item->product_name }}
                            @if($item->sku)<div class="sku">SKU: {{ $item->sku }}</div>@endif
                        </td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="num">${{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td style="width:54%">
                    <div class="note">
                        <strong>Payment received.</strong> This receipt confirms that your payment for order
                        {{ $order->order_number }} was received and verified. Please keep it for your records.
                    </div>
                </td>
                <td style="width:46%">
                    <table class="totals">
                        <tr><td>Subtotal</td><td class="val">${{ number_format($order->subtotal, 2) }}</td></tr>
                        <tr><td>Delivery fee</td><td class="val">${{ number_format($order->shipping_total, 2) }}</td></tr>
                        @if((float) $order->discount_total > 0)
                            <tr><td>Discount</td><td class="val">-${{ number_format($order->discount_total, 2) }}</td></tr>
                        @endif
                        <tr class="grand"><td>Total paid</td><td class="val">${{ number_format($order->grand_total, 2) }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
```
