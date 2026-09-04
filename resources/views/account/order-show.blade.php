@extends('shop.layout')

@section('title', $order->order_number.' - CEC Electronic')

@section('content')
    <div class="section-head">
        <div>
            <h2>{{ $order->order_number }}</h2>
            <p>Placed {{ $order->created_at->format('M d, Y h:i A') }}</p>
        </div>
        <a class="btn secondary" href="{{ route('account.orders') }}">Back to orders</a>
    </div>

    <section class="checkout">
        <div class="panel" style="padding:18px">
            <h3 style="margin-top:0">Items</h3>
            @foreach($order->items as $item)
                <div style="display:grid;grid-template-columns:1fr 80px 110px;gap:12px;border-bottom:1px solid var(--line);padding:12px 0;align-items:center">
                    <div>
                        <strong>{{ $item->product_name }}</strong>
                        <div class="sku">{{ $item->sku ?: 'No SKU' }}</div>
                    </div>
                    <span>x {{ $item->quantity }}</span>
                    <strong style="text-align:right">${{ number_format($item->line_total, 2) }}</strong>
                </div>
            @endforeach
        </div>

        <aside class="panel" style="padding:18px">
            <h3 style="margin-top:0">Order status</h3>
            <div class="spec-table">
                <div class="spec-row"><span>Status</span><strong>{{ ucfirst($order->status) }}</strong></div>
                <div class="spec-row"><span>Payment</span><strong>{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</strong></div>
                <div class="spec-row"><span>Delivery</span><strong>{{ $order->deliveryProvider?->name ?: ucfirst((string) $order->shipping_method) }}</strong></div>
                <div class="spec-row"><span>Tracking</span><strong>{{ $order->tracking_number ?: 'Not assigned yet' }}</strong></div>
                <div class="spec-row"><span>Subtotal</span><strong>${{ number_format($order->subtotal, 2) }}</strong></div>
                <div class="spec-row"><span>Delivery fee</span><strong>${{ number_format($order->shipping_total, 2) }}</strong></div>
                <div class="spec-row"><span>Total</span><strong>${{ number_format($order->grand_total, 2) }}</strong></div>
            </div>

            @if($order->shipping_address)
                <h3>Delivery address</h3>
                <p style="color:var(--muted);line-height:1.7">
                    {{ $order->shipping_address['address_line_1'] ?? '' }}<br>
                    {{ $order->shipping_address['address_line_2'] ?? '' }}<br>
                    {{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['province'] ?? '' }}
                </p>
            @endif
        </aside>
    </section>
@endsection
