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
                    <div style="display:grid;grid-template-columns:1fr 120px 90px;gap:14px;align-items:center;border-bottom:1px solid var(--line);padding:14px 0">
                        <div>
                            <strong>{{ $item->product?->name ?: 'Deleted product' }}</strong>
                            <div class="sku">${{ number_format($item->unit_price) }} each</div>
                        </div>
                        <form action="{{ route('cart.update', $item) }}" method="post">
                            @csrf
                            @method('PATCH')
                            <input name="quantity" type="number" min="0" max="99" step="1" inputmode="numeric" pattern="[0-9]*" value="{{ (int) $item->quantity }}" onchange="this.value = Math.floor(Number(this.value || 0)); this.form.submit()" style="width:84px">
                        </form>
                        <div style="text-align:right">
                            <strong>${{ number_format($item->line_total) }}</strong>
                            <form action="{{ route('cart.destroy', $item) }}" method="post" style="margin-top:8px">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="border:0;background:transparent;color:#e11d48;cursor:pointer">Remove</button>
                            </form>
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
                <span>${{ number_format($subtotal ?? 0) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;color:var(--muted);margin-bottom:18px">
                <span>Delivery</span>
                <span>{{ ($subtotal ?? 0) >= 500 ? 'Free' : '$5' }}</span>
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
