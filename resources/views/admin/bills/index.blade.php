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
                <p class="muted" style="margin:6px 0 0">What each customer bought, what they paid, and their receipt. Cancelled orders are not shown.</p>
            </div>
        </div>

        <div class="panel" style="overflow:hidden">
            <table>
                <thead><tr><th>Customer</th><th>Bought</th><th>Payment</th><th>Total</th><th style="text-align:right">Actions</th></tr></thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><strong>{{ $order->customer_name }}</strong><div class="muted">{{ $order->customer_phone }}</div><div class="muted">{{ $order->order_number }} &middot; {{ $order->created_at->copy()->setTimezone(config('app.shop_timezone'))->format('d M Y') }}</div></td>
                            <td>
                                @foreach($order->items as $item)
                                    <div>{{ $item->product_name }} <span class="muted">&times; {{ number_format($item->quantity) }} @ ${{ number_format($item->unit_price, 2) }} = ${{ number_format($item->line_total, 2) }}</span></div>
                                @endforeach
                            </td>
                            <td><span class="status-text {{ $order->payment_status === 'paid' ? 'success' : 'danger' }}">{{ ucfirst($order->payment_status) }}</span></td>
                            <td><strong>${{ number_format($order->grand_total, 2) }}</strong></td>
                            <td><div class="actions"><a class="btn secondary" href="{{ route('admin.orders.receipt', $order) }}" download>Receipt</a><a class="btn secondary" href="{{ route('admin.orders.show', $order) }}">Open</a></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">No customer bills yet.</td></tr>
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
