@extends('shop.layout')

@section('title','CEC Electronic - Computer, Laptop & IT Store')

@section('content')
    @php
        $brands = collect(config('brands'))->filter(fn ($brand) => $brand['logo'])->values();
        $categoryTiles = [
            ['slug' => 'laptops', 'name' => 'Laptops', 'copy' => 'Business, student, creator, and gaming notebooks.'],
            ['slug' => 'desktops', 'name' => 'Desktop PC', 'copy' => 'Ready office PCs and custom build quotation.'],
            ['slug' => 'gaming', 'name' => 'Gaming Gear', 'copy' => 'Gaming laptops, keyboards, mice, headsets, and chairs.'],
            ['slug' => 'monitors', 'name' => 'Monitors', 'copy' => 'Office, gaming, ultrawide, and creator displays.'],
            ['slug' => 'components', 'name' => 'Components', 'copy' => 'CPU, motherboard, RAM, SSD, GPU, PSU, and cases.'],
            ['slug' => 'printers', 'name' => 'Printers', 'copy' => 'Inkjet, laser, scanner, copier, and office supply.'],
        ];
    @endphp

    <section class="hero">
        <aside class="panel category-menu">
            <h3>All Categories</h3>
            @foreach($categories as $cat)
                <a class="category-link" href="{{ route('shop.category', $cat->slug) }}">
                    <span>{{ $cat->name }}</span>
                    <span>></span>
                </a>
            @endforeach
            <a class="category-link" href="{{ route('shop.category', 'desktops') }}"><span>Desktop PC</span><span>></span></a>
            <a class="category-link" href="{{ route('shop.category', 'monitors') }}"><span>Monitor & Display</span><span>></span></a>
            <a class="category-link" href="{{ route('shop.category', 'components') }}"><span>Computer Components</span><span>></span></a>
            <a class="category-link" href="{{ route('shop.category', 'printers') }}"><span>Printer & Scanner</span><span>></span></a>
        </aside>

        <div class="panel hero-main">
            <div>
                <div class="hero-points">
                    <span>Official warranty</span>
                    <span>Real shop support</span>
                    <span>Fast local delivery</span>
                </div>
                <h1>Computer, laptop, printer, and IT products for every setup</h1>
                <p>Browse clear product specs, real stock status, local warranty support, and fast CEC Electronic delivery.</p>
                <a class="btn accent" href="{{ route('shop.category', 'laptops') }}">Shop laptops</a>
                <a class="btn secondary" href="#featured">View best sellers</a>
            </div>
            <div class="device-scene" aria-hidden="true">
                <div>
                    <div class="laptop-art"><div class="laptop-screen"></div></div>
                    <div class="laptop-base"></div>
                </div>
            </div>
        </div>

        <aside class="deal-stack">
            <div class="panel deal">
                <small>Hot promotion</small>
                <b>New arrivals</b>
                <p>Latest laptop, printer, monitor, and office IT products.</p>
            </div>
            <div class="panel deal">
                <small>Business service</small>
                <b>Office IT quote</b>
                <p>Quote desktops, monitors, printers, and network devices for your team.</p>
            </div>
        </aside>
    </section>

    <section class="service-row" aria-label="Store services">
        <div class="panel service"><span class="service-icon">1H</span><span><strong>Same-day delivery</strong><span>Fast dispatch in Phnom Penh areas.</span></span></div>
        <div class="panel service"><span class="service-icon">WR</span><span><strong>Warranty support</strong><span>Track service and product warranty.</span></span></div>
        <div class="panel service"><span class="service-icon">PC</span><span><strong>Custom PC quote</strong><span>Build lists for gaming or office work.</span></span></div>
        <div class="panel service"><span class="service-icon">$</span><span><strong>Secure checkout</strong><span>Cash on delivery and bank transfer.</span></span></div>
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
                    <a class="brand-slide" href="{{ route('shop.brand', $brand['slug']) }}" aria-label="View {{ $brand['name'] }} products">
                        <img src="{{ asset($brand['logo']) }}" alt="{{ $brand['name'] }} logo">
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
