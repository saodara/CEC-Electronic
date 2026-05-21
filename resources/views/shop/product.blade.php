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
                <span class="price">${{ number_format($product->price, 0) }}</span>
                @if($oldPrice)
                    <span class="old-price">${{ number_format($oldPrice, 0) }}</span>
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
                <form action="{{ route('cart.store', $product) }}" method="post">
                    @csrf
                    <button class="btn" style="width:100%" type="submit">Add to cart</button>
                </form>
                <a class="btn secondary" href="{{ route('shop.cart') }}">View cart</a>
            </div>
        </aside>
    </section>
@endsection
