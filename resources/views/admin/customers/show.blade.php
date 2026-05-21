@extends('admin.layout')

@section('title', $customer->customer_name.' - CEC Electronic Admin')
@section('heading', 'Customer Profile')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">{{ $customer->customer_name }}</h2>
            <p class="muted" style="margin:6px 0 0">{{ $customer->customer_phone }} - {{ $customer->customer_email ?: 'No email' }}</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.customers.index') }}">Back to customers</a>
    </div>

    <section class="stats">
        <div class="panel stat">
            <span>Total orders</span>
            <strong>{{ number_format($stats['orders']) }}</strong>
        </div>
        <div class="panel stat">
            <span>Total spent</span>
            <strong>${{ number_format($stats['spent']) }}</strong>
        </div>
        <div class="panel stat">
            <span>Unpaid orders</span>
            <strong>{{ number_format($stats['unpaid']) }}</strong>
        </div>
        <div class="panel stat">
            <span>Latest order</span>
            <strong style="font-size:20px">{{ $stats['latest']?->format('M d, Y') }}</strong>
        </div>
    </section>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>
                            <strong>{{ $order->order_number }}</strong>
                            <div class="muted">{{ $order->created_at->format('M d, Y h:i A') }}</div>
                        </td>
                        <td><span class="status {{ $order->status === 'pending' ? 'warning' : '' }}">{{ ucfirst($order->status) }}</span></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</td>
                        <td>{{ $order->items_count }}</td>
                        <td>${{ number_format($order->grand_total) }}</td>
                        <td><div class="actions"><a class="btn secondary" href="{{ route('admin.orders.show', $order) }}">Open order</a></div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
