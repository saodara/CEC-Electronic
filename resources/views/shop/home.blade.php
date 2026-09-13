@extends('shop.layout')

@section('title','CEC Electronic - Computer, Laptop & IT Store')

@section('content')
    @php
        $brands = collect(config('brands'))->filter(fn ($brand) => $brand['logo'])->values();
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
