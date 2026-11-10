@extends('admin.layout')

@section('title', 'Purchase Orders - CEC Electronic Admin')
@section('heading', 'Purchase Orders')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Purchase orders</h2>
            <p class="muted" style="margin:6px 0 0">Order stock from suppliers. Receiving an order adds it to your stock.</p>
        </div>
        <a class="btn" href="{{ route('admin.purchase-orders.create') }}">New purchase order</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr><th>Number</th><th>Supplier</th><th>Expected</th><th>Status</th><th>Total</th><th style="text-align:right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($purchaseOrders as $purchaseOrder)
                    <tr>
                        <td><strong>{{ $purchaseOrder->po_number }}</strong><div class="muted">{{ $purchaseOrder->items_count }} items</div></td>
                        <td>{{ $purchaseOrder->supplier->name }}</td>
                        <td>{{ $purchaseOrder->expected_date?->format('d M Y') ?: 'Not set' }}</td>
                        <td>@include('admin.purchase-orders._status', ['status' => $purchaseOrder->status])</td>
                        <td>${{ number_format($purchaseOrder->grand_total, 2) }}</td>
                        <td><div class="actions"><a class="btn secondary" href="{{ route('admin.purchase-orders.show', $purchaseOrder) }}">Open</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No purchase orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $purchaseOrders->links() }}</div>
@endsection
