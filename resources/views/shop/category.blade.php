@extends('shop.layout')

@section('title', $categoryName.' - CEC Electronic')

@section('content')
    @php
        $brands = config('brands');
        $filterGroups = [
            'Processor' => ['Intel Core i3','Intel Core i5','Intel Core i7','AMD Ryzen 5','AMD Ryzen 7','Apple M series'],
            'RAM Size' => ['8GB','16GB','32GB','64GB'],
            'Storage' => ['256GB SSD','512GB SSD','1TB SSD','2TB SSD'],
            'Screen Size' => ['13 inch','14 inch','15.6 inch','16 inch','17 inch'],
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
            <a class="brand-pill" href="{{ route('shop.brand', $brand['slug']) }}">{{ $brand['name'] }}</a>
        @endforeach
    </div>

    <section class="catalog">
        <aside class="panel filter">
            <h3>Filters</h3>
            <div class="filter-group">
                <div class="filter-title"><span>Categories</span><span>-</span></div>
                <div class="checks">
                    <label><input type="checkbox" checked> {{ $categoryName }}</label>
                    <label><input type="checkbox"> Gaming</label>
                    <label><input type="checkbox"> Business model</label>
                    <label><input type="checkbox"> 2-in-1 & tablet</label>
                </div>
            </div>
            <div class="filter-group">
                <div class="filter-title"><span>Price range</span><span>-</span></div>
                <div class="checks">
                    <label><input type="checkbox"> Under $500</label>
                    <label><input type="checkbox"> $500 - $999</label>
                    <label><input type="checkbox"> $1,000 - $1,499</label>
                    <label><input type="checkbox"> $1,500+</label>
                </div>
            </div>
            @foreach($filterGroups as $title => $options)
                <div class="filter-group">
                    <div class="filter-title"><span>{{ $title }}</span><span>-</span></div>
                    <div class="checks">
                        @foreach($options as $option)
                            <label><input type="checkbox"> {{ $option }}</label>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:8px">
                <button class="btn secondary" type="button">Clear</button>
                <button class="btn" type="button">Apply</button>
            </div>
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
