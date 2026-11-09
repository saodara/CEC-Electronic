@extends('admin.layout')

@section('title', 'Bills - CEC Electronic Admin')
@section('heading', 'Bills')

@section('content')
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
        <a @class(['btn', 'secondary' => $tab !== 'suppliers']) href="{{ route('admin.bills.index') }}">Supplier bills</a>
        <a @class(['btn', 'secondary' => $tab !== 'customers']) href="{{ route('admin.bills.index', ['tab' => 'customers']) }}">Customer bills</a>
    </div>

    @if($tab === 'customers')
        <div class="toolbar" style="flex-wrap:wrap">
            <div>
                <h2 style="margin:0">Customer bills</h2>
                <p class="muted" style="margin:6px 0 0">What each customer bought, what they paid, their invoice and receipt. Cancelled orders are not shown.</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <span class="status">Total paid: ${{ number_format($paidTotal, 2) }}</span>
                <span class="status warning">Waiting for payment: ${{ number_format($unpaidCustomerTotal, 2) }}</span>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.bills.index') }}" class="panel" style="padding:14px;margin-bottom:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;align-items:end">
            <input type="hidden" name="tab" value="customers">
            <label class="field" style="grid-column:span 2">Search<input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Customer name, phone or order no."></label>
            <label class="field">Payment
                <select name="payment">
                    <option value="">All</option>
                    <option value="paid" @selected($filters['payment'] === 'paid')>Paid</option>
                    <option value="unpaid" @selected($filters['payment'] === 'unpaid')>Unpaid</option>
                </select>
            </label>
            <label class="field">From<input type="date" name="from" value="{{ $filters['from'] }}"></label>
            <label class="field">To<input type="date" name="to" value="{{ $filters['to'] }}"></label>
            <div style="display:flex;gap:8px"><button class="btn" type="submit">Filter</button>@if(array_filter($filters))<a class="btn secondary" href="{{ route('admin.bills.index', ['tab' => 'customers']) }}">Clear</a>@endif</div>
        </form>

        <div class="panel" style="overflow:hidden">
            <table>
                <thead><tr><th>Customer</th><th>Bought</th><th>Payment</th><th>Total</th><th style="text-align:right">Actions</th></tr></thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><strong>{{ $order->customer_name }}</strong><div class="muted">{{ $order->customer_phone }}</div><div class="muted">{{ $order->order_number }} &middot; {{ $order->created_at->copy()->setTimezone(config('app.shop_timezone'))->format('d M Y') }}</div>@php($customerSpent = (float) ($order->user_id ? ($spentByUser[$order->user_id] ?? 0) : ($spentByPhone[$order->customer_phone] ?? 0)))<div class="muted">Customer total spent: <strong>${{ number_format($customerSpent, 2) }}</strong></div></td>
                            <td>
                                @foreach($order->items as $item)
                                    <div>{{ $item->product_name }} <span class="muted">&times; {{ number_format($item->quantity) }} @ ${{ number_format($item->unit_price, 2) }} = ${{ number_format($item->line_total, 2) }}</span></div>
                                @endforeach
                            </td>
                            <td><span class="status-text {{ $order->payment_status === 'paid' ? 'success' : 'danger' }}">{{ ucfirst($order->payment_status) }}</span></td>
                            <td><strong>${{ number_format($order->grand_total, 2) }}</strong></td>
                            <td><div class="actions">@if($order->invoice)<a class="btn" href="{{ route('admin.invoices.show', $order->invoice) }}">Invoice</a>@else<form method="POST" action="{{ route('admin.invoices.store', $order) }}" style="display:inline">@csrf<button class="btn" type="submit">Make invoice</button></form>@endif @if($order->payment_status === 'paid')<a class="btn secondary" href="{{ route('admin.orders.receipt', $order) }}" download>Receipt</a>@endif<a class="btn secondary" href="{{ route('admin.orders.show', $order) }}">Open</a></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">{{ array_filter($filters) ? 'No customer bills match this filter.' : 'No customer bills yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $orders->links() }}</div>
    @else
        <div class="toolbar" style="flex-wrap:wrap">
            <div>
                <h2 style="margin:0">Supplier bills</h2>
                <p class="muted" style="margin:6px 0 0">What you owe suppliers. Create a bill from a received purchase order.</p>
            </div>
            <span class="status warning">Unpaid: ${{ number_format($unpaidTotal, 2) }}</span>
        </div>

        <div class="panel" style="overflow:hidden">
            <table>
                <thead><tr><th>Bill</th><th>Supplier</th><th>Purchase order</th><th>Due</th><th>Status</th><th>Total</th><th style="text-align:right">Actions</th></tr></thead>
                <tbody>
                    @forelse($bills as $bill)
                        <tr>
                            <td><strong>{{ $bill->bill_number }}</strong><div class="muted">{{ $bill->supplier_reference }}</div></td>
                            <td>{{ $bill->supplier->name }}</td>
                            <td>{{ $bill->purchaseOrder->po_number }}</td>
                            <td>{{ $bill->due_date->format('d M Y') }}</td>
                            <td>@include('admin.bills._status')</td>
                            <td>${{ number_format($bill->total, 2) }}</td>
                            <td><div class="actions">@if($bill->status === 'paid')<a class="btn secondary" href="{{ route('admin.bills.receipt', $bill) }}" download>Receipt</a>@endif<a class="btn secondary" href="{{ route('admin.bills.show', $bill) }}">Open</a></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">No bills yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $bills->links() }}</div>
    @endif
@endsection
