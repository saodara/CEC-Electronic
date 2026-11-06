@extends('shop.layout')

@section('title', $product->name.' - CEC Electronic')

@push('head')
    <meta property="og:type" content="product">
    <meta property="og:site_name" content="CEC Electronic">
    <meta property="og:title" content="{{ $product->name }} - ${{ number_format($product->price, 2) }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($product->description ?: 'Available now at CEC Electronic.', 160) }}">
    <meta property="og:url" content="{{ route('shop.product', $product->slug) }}">
    <meta property="og:image" content="{{ str_replace(' ', '%20', url($product->image_url)) }}">
    <style>
        .share-buttons{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
        .share-buttons .btn{flex:1 1 90px}
        /* Sending a text message only works on phones and tablets. */
        @media (hover:hover) and (pointer:fine){.share-message{display:none}}
    </style>
@endpush

@push('scripts')
    <script>
        // On phones, open the share menu (Messages, Messenger and other apps). The sms: link is the fallback.
        document.querySelector('.share-message').addEventListener('click', function (event) {
            if (! navigator.share) return;

            event.preventDefault();
            navigator.share({
                title: this.dataset.shareTitle,
                text: this.dataset.shareText,
                url: this.dataset.shareUrl,
            }).catch(function () {});
        });
    </script>
@endpush

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

            @php
                $shareUrl = route('shop.product', $product->slug);
                $shareText = $product->name.' - $'.number_format($product->price, 2);
            @endphp
            <div style="margin-top:14px">
                <strong>Share</strong>
                <div class="share-buttons">
                    <a class="btn secondary" href="https://t.me/share/url?{{ http_build_query(['url' => $shareUrl, 'text' => $shareText], '', '&', PHP_QUERY_RFC3986) }}" target="_blank" rel="noopener">Telegram</a>
                    <a class="btn secondary" href="https://www.facebook.com/sharer/sharer.php?{{ http_build_query(['u' => $shareUrl], '', '&', PHP_QUERY_RFC3986) }}" target="_blank" rel="noopener">Facebook</a>
                    <a class="btn secondary share-message" href="sms:?&amp;body={{ rawurlencode($shareText.' '.$shareUrl) }}" data-no-loader data-share-title="{{ $product->name }}" data-share-text="{{ $shareText }}" data-share-url="{{ $shareUrl }}">Message</a>
                </div>
            </div>
        </aside>
    </section>
@endsection
