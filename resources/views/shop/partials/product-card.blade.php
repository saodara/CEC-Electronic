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
        <form class="card-actions" action="{{ route('cart.store', $p) }}" method="post">
            @csrf
            <button class="btn" type="submit">Add to cart</button>
            <a class="icon-btn" href="{{ route('shop.product', $p->slug) }}" aria-label="View {{ $p->name }}" style="display:grid;place-items:center">i</a>
        </form>
    </div>
</article>
