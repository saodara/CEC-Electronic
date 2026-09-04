@extends('shop.layout')

@section('title', 'My Orders - CEC Electronic')

@section('content')
    <div class="section-head">
        <h2>My orders</h2>
        <a class="btn secondary" href="{{ route('account.dashboard') }}">Account</a>
    </div>

    <div class="panel" style="padding:18px">
        @forelse($orders as $order)
            <a href="{{ route('account.orders.show', $order) }}" style="display:grid;grid-template-columns:1fr 130px 120px;gap:12px;border-bottom:1px solid var(--line);padding:12px 0">
                <strong>{{ $order->order_number }}</strong>
                <span>{{ ucfirst($order->status) }}</span>
                <strong style="text-align:right">${{ number_format($order->grand_total, 2) }}</strong>
            </a>
        @empty
            <p style="color:var(--muted)">No orders yet.</p>
        @endforelse

        <div style="margin-top:16px">{{ $orders->links() }}</div>
    </div>
@endsection
