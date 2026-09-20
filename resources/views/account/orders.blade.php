@extends('shop.layout')

@section('title', 'Order History - CEC Electronic')

@section('content')
    <div class="section-head">
        <h2>Order history</h2>
        <a class="btn secondary" href="{{ route('account.dashboard') }}">Account</a>
    </div>

    <div class="panel" style="padding:18px">
        @forelse($orders as $order)
            <div style="border-bottom:1px solid var(--line);padding:14px 0">
                <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:6px 16px">
                    <div style="flex:1 1 170px;min-width:0">
                        <strong style="white-space:nowrap">{{ $order->order_number }}</strong>
                        <div class="sku">{{ $order->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    <span>{{ ucfirst($order->status) }} &middot; {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</span>
                    <strong style="min-width:70px;margin-left:auto;text-align:right">${{ number_format($order->grand_total, 2) }}</strong>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
                    <a class="btn secondary" href="{{ route('account.orders.show', $order) }}">About order</a>
                    @if($order->payment_status === 'paid')
                        <a class="btn secondary" href="{{ route('account.orders.receipt.view', $order) }}" target="_blank" rel="noopener">Receipt</a>
                        <a class="btn" href="{{ route('account.orders.receipt', $order) }}">Download receipt</a>
                    @else
                        <span style="color:var(--muted);font-size:13px;align-self:center">Receipt available after payment</span>
                    @endif
                </div>
            </div>
        @empty
            <p style="color:var(--muted)">No orders yet.</p>
        @endforelse

        <div style="margin-top:16px">{{ $orders->links() }}</div>
    </div>
@endsection
