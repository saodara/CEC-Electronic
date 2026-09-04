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
        <div class="panel service"><span class="service-icon">DL</span><span><strong>Delivery</strong><span>Track assigned delivery provider and status.</span></span></div>
        <div class="panel service"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/warranty-icon.jpeg') }}" alt="Warranty"></span><span><strong>Warranty</strong><span>Keep order numbers for service support.</span></span></div>
        <div class="panel service"><span class="service-icon">SP</span><span><strong>Support</strong><span>Call 012 220 152 for help.</span></span></div>
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
