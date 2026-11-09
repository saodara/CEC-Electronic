@extends('admin.layout')

@section('title', 'Customers - CEC Electronic Admin')
@section('heading', 'Customers')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Customer management</h2>
            <p class="muted" style="margin:6px 0 0">Every customer account and guest buyer. Admin accounts are not listed.</p>
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
                    <th>Account</th>
                    <th>Latest order</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td><strong>{{ $customer->customer_name }}</strong></td>
                        <td>
                            {{ $customer->customer_phone ?: 'No phone' }}
                            <div class="muted">{{ $customer->customer_email ?: 'No email' }}</div>
                        </td>
                        <td>{{ number_format($customer->orders_count) }}</td>
                        <td>${{ number_format($customer->total_spent, 2) }}</td>
                        <td>
                            <span @class(['status', 'info' => ! $customer->registered])>{{ $customer->registered ? 'Registered' : 'Guest' }}</span>
                            @if($customer->joined_at)<div class="muted">Joined {{ $customer->joined_at->format('M d, Y') }}</div>@endif
                        </td>
                        <td>{{ $customer->last_order_at?->format('M d, Y') ?? 'No orders yet' }}</td>
                        <td>
                            <div class="actions">
                                @if($customer->orders_count > 0)
                                    <a class="btn secondary" href="{{ route('admin.customers.show', $customer->customer_phone) }}">Open</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted">No customers yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $customers->links() }}</div>
@endsection
