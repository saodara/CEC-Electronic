@extends('shop.layout')

@section('title', 'My Account - CEC Electronic')

@section('content')
    <div class="section-head">
        <div style="display:flex;align-items:center;gap:14px">
            @include('account._avatar', ['user' => auth()->user(), 'size' => 56])
            <div>
                <h2>My account</h2>
                <p>Track your CEC Electronic orders, payment status, and delivery progress.</p>
            </div>
        </div>
        <span style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn" href="{{ route('account.profile') }}">Edit profile</a>
            <a class="btn secondary" href="{{ route('account.orders') }}">Order history</a>
            <form action="{{ route('customer.logout') }}" method="post">
                @csrf
                <button class="btn secondary" type="submit">Logout</button>
            </form>
        </span>
    </div>

    @include('account._spending')

    <section class="service-row">
        <div class="panel service"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/order-icon.png') }}" alt="Orders"></span><span><strong>Orders</strong><span>{{ number_format($spending['orders']) }} {{ $spending['orders'] === 1 ? 'order' : 'orders' }} placed.</span></span></div>
        <div class="panel service"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/delivery-icon.png') }}" alt="Delivery"></span><span><strong>Delivery</strong><span>Track assigned delivery provider and status.</span></span></div>
        <div class="panel service"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/warranty-icon.jpeg') }}" alt="Warranty"></span><span><strong>Warranty</strong><span>Keep order numbers for service support.</span></span></div>
        <a class="panel service" href="https://t.me/CECELECTRONICS" target="_blank" rel="noopener"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/support-icon.avif') }}" alt="Support"></span><span><strong>Support</strong><span>Telegram @@CECELECTRONICS or call 012 220 152.</span></span></a>
    </section>

    <div class="panel" style="padding:18px">
        <h3 style="margin-top:0">Recent orders</h3>
        @forelse($orders as $order)
            <a href="{{ route('account.orders.show', $order) }}" style="display:flex;flex-wrap:wrap;gap:6px 16px;border-bottom:1px solid var(--line);padding:12px 0;align-items:center">
                <span style="flex:1 1 170px;min-width:0">
                    <strong>{{ $order->order_number }}</strong>
                    <span class="sku" style="display:block">{{ $order->created_at->format('M d, Y') }}</span>
                </span>
                <span style="flex:0 1 300px;min-width:0">
                    {{ ucfirst($order->status) }}
                    <span class="sku" style="display:block">{{ $order->statusMeaning() }}</span>
                </span>
                <strong style="min-width:70px;margin-left:auto;text-align:right">${{ number_format($order->grand_total, 2) }}</strong>
            </a>
        @empty
            <p style="color:var(--muted)">No orders yet.</p>
            <a class="btn" href="{{ route('shop.home') }}">Start shopping</a>
        @endforelse
    </div>
@endsection
