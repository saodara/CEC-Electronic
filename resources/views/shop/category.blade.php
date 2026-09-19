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
