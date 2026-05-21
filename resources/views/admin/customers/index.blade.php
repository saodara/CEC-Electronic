@extends('admin.layout')

@section('title', 'Customers - CEC Electronic Admin')
@section('heading', 'Customers')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Customer management</h2>
            <p class="muted" style="margin:6px 0 0">View order history, total spend, contact details, and payment risk by customer phone.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.orders.index') }}">Orders</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Orders</th>
                    <th>Total spent</th>
                    <th>Latest order</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td><strong>{{ $customer->customer_name }}</strong></td>
                        <td>
                            {{ $customer->customer_phone }}
                            <div class="muted">{{ $customer->customer_email ?: 'No email' }}</div>
                        </td>
                        <td>{{ number_format($customer->orders_count) }}</td>
                        <td>${{ number_format($customer->total_spent) }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($customer->last_order_at)->format('M d, Y') }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="{{ route('admin.customers.show', $customer->customer_phone) }}">Open</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">No customers yet. Customer records are created from checkout orders.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $customers->links() }}</div>
@endsection
