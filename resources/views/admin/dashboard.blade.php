@extends('admin.layout')

@section('title', 'Admin Dashboard - CEC Electronic')
@section('heading', 'Dashboard')

@section('content')
    <section class="stats">
        <div class="panel stat">
            <span>Total products</span>
            <strong>{{ number_format($stats['products']) }}</strong>
        </div>
        <div class="panel stat">
            <span>Orders</span>
            <strong>{{ number_format($stats['orders']) }}</strong>
        </div>
        <div class="panel stat">
            <span>Customers</span>
            <strong>{{ number_format($stats['customers']) }}</strong>
        </div>
        <div class="panel stat">
            <span>Revenue</span>
            <strong>${{ number_format($stats['revenue']) }}</strong>
        </div>
    </section>

    <section class="stats">
        <div class="panel stat">
            <span>Categories</span>
            <strong>{{ number_format($stats['categories']) }}</strong>
        </div>
        <div class="panel stat">
            <span>Low stock</span>
            <strong>{{ number_format($stats['low_stock']) }}</strong>
        </div>
        <div class="panel stat">
            <span>Catalog value</span>
            <strong>${{ number_format($stats['value']) }}</strong>
        </div>
        <div class="panel stat">
            <span>Payment alerts</span>
            <strong>{{ number_format($stats['payment_notifications']) }}</strong>
        </div>
    </section>

    <section class="split">
        <div>
            <div class="toolbar">
                <h2 style="margin:0">Payment notifications</h2>
                <a class="btn secondary" href="{{ route('admin.orders.index') }}">Review orders</a>
            </div>
            <div class="panel mini-list">
                @forelse($paymentNotifications as $order)
                    <a class="mini-item" href="{{ route('admin.orders.show', $order) }}">
                        <div>
                            <strong>{{ $order->order_number }}</strong>
                            <div class="muted">{{ $order->customer_name }} paid by {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</div>
                        </div>
                        <div style="text-align:right">
                            <strong>${{ number_format($order->grand_total) }}</strong>
                            <div><span class="status unread">New payment</span></div>
                        </div>
                    </a>
                @empty
                    <p class="muted" style="margin:0">No unread payment notifications.</p>
                @endforelse
            </div>
        </div>

        <div>
            <div class="toolbar">
                <h2 style="margin:0">Latest orders</h2>
                <a class="btn secondary" href="{{ route('admin.orders.index') }}">View all</a>
            </div>
            <div class="panel mini-list">
                @forelse($latestOrders as $order)
                    <div class="mini-item">
                        <div>
                            <strong>{{ $order->order_number }}</strong>
                            <div class="muted">{{ $order->customer_name }} - {{ $order->customer_phone }}</div>
                        </div>
                        <div style="text-align:right">
                            <strong>${{ number_format($order->grand_total) }}</strong>
                            <div><span class="status {{ $order->status === 'pending' ? 'warning' : '' }}">{{ ucfirst($order->status) }}</span></div>
                        </div>
                    </div>
                @empty
                    <p class="muted" style="margin:0">No orders yet.</p>
                @endforelse
            </div>
        </div>

    </section>

    <section style="margin-top:16px">
        <div>
            <div class="toolbar">
                <h2 style="margin:0">Latest products</h2>
                <a class="btn secondary" href="{{ route('admin.products.index') }}">Manage</a>
            </div>
            <div class="panel" style="overflow:hidden">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Stock</th>
                            <th style="text-align:right">Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestProducts as $product)
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <img class="thumb" src="{{ $product->image_url }}" alt="{{ $product->name }}">
                                        <div>
                                            <strong>{{ $product->name }}</strong>
                                            <div class="muted">${{ number_format($product->price) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="status {{ $product->stock_quantity <= 5 ? 'danger' : '' }}">{{ $product->stock_quantity }} left</span></td>
                                <td><div class="actions"><a class="btn secondary" href="{{ route('admin.products.edit', $product) }}">Edit</a></div></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="muted">No products yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
