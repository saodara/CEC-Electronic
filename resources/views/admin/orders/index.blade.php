@extends('admin.layout')

@section('title', 'Orders - CEC Electronic Admin')
@section('heading', 'Orders')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Order management</h2>
            <p class="muted" style="margin:6px 0 0">Review customer orders, payment state, and fulfillment progress.</p>
        </div>
        @if($paymentNotificationsCount > 0)
            <span class="status unread">{{ $paymentNotificationsCount }} new payment {{ $paymentNotificationsCount === 1 ? 'notification' : 'notifications' }}</span>
        @endif
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Delivery</th>
                    <th>Payment</th>
                    <th>Total</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <strong>{{ $order->order_number }}</strong>
                            @if($order->payment_confirmed_at && ! $order->admin_payment_seen_at)
                                <span class="status unread" style="margin-left:8px">New payment</span>
                            @endif
                            <div class="muted">{{ $order->items_count }} items</div>
                        </td>
                        <td>{{ $order->customer_name }}<div class="muted">{{ $order->customer_phone }}</div></td>
                        <td><span class="status">{{ ucfirst($order->status) }}</span></td>
                        <td>{{ $order->deliveryProvider?->name ?: 'Unassigned' }}<div class="muted">{{ $order->tracking_number }}</div></td>
                        <td>
                            {{ ucfirst($order->payment_status) }}
                            <div class="muted">{{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</div>
                        </td>
                        <td>${{ number_format($order->grand_total) }}</td>
                        <td><div class="actions"><a class="btn secondary" href="{{ route('admin.orders.show', $order) }}">Open</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $orders->links() }}</div>
@endsection
