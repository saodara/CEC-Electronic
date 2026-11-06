@extends('admin.layout')

@section('title', $purchaseOrder->po_number.' - CEC Electronic Admin')
@section('heading', 'Purchase Order')

@section('content')
    <div class="toolbar" style="flex-wrap:wrap">
        <div>
            <h2 style="margin:0">{{ $purchaseOrder->po_number }} @include('admin.purchase-orders._status', ['status' => $purchaseOrder->status])</h2>
            <p class="muted" style="margin:6px 0 0">{{ $purchaseOrder->supplier->name }} &middot; Created {{ $purchaseOrder->created_at->copy()->setTimezone(config('app.shop_timezone'))->format('d M Y') }}</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @if($purchaseOrder->status === 'draft')
                <a class="btn secondary" href="{{ route('admin.purchase-orders.edit', $purchaseOrder) }}">Edit</a>
                <form action="{{ route('admin.purchase-orders.order', $purchaseOrder) }}" method="post">@csrf<button class="btn" type="submit">Mark as ordered</button></form>
            @endif
            @if($purchaseOrder->status === 'ordered')
                <form action="{{ route('admin.purchase-orders.receive', $purchaseOrder) }}" method="post" onsubmit="return confirm('Receive these goods? This adds them to stock and updates product costs, and can\'t be undone.')">@csrf<button class="btn" type="submit">Receive goods</button></form>
            @endif
            @if($purchaseOrder->status === 'received')
                @if($purchaseOrder->bill)
                    <a class="btn" href="{{ route('admin.bills.show', $purchaseOrder->bill) }}">Open bill</a>
                @else
                    <form action="{{ route('admin.purchase-orders.bill', $purchaseOrder) }}" method="post">@csrf<button class="btn" type="submit">Create bill</button></form>
                @endif
            @endif
            @if(in_array($purchaseOrder->status, ['draft', 'ordered'], true))
                <form action="{{ route('admin.purchase-orders.cancel', $purchaseOrder) }}" method="post" onsubmit="return confirm('Cancel this purchase order?')">@csrf<button class="btn secondary" type="submit">Cancel order</button></form>
            @endif
            @if($purchaseOrder->status === 'draft')
                <form action="{{ route('admin.purchase-orders.destroy', $purchaseOrder) }}" method="post" onsubmit="return confirm('Delete this draft?')">@csrf @method('DELETE')<button class="btn danger" type="submit">Delete</button></form>
            @endif
            <a class="btn secondary" href="{{ route('admin.purchase-orders.index') }}">Back</a>
        </div>
    </div>

    <div class="panel" style="overflow:hidden;margin-bottom:16px">
        <table>
            <thead><tr><th>Product</th><th>Quantity</th><th>Unit cost</th><th style="text-align:right">Line total</th></tr></thead>
            <tbody>
                @foreach($purchaseOrder->items as $item)
                    <tr>
                        <td>{{ $item->product?->name ?: 'Deleted product' }}<div class="muted">{{ $item->product?->sku }}</div></td>
                        <td>{{ number_format($item->quantity) }}</td>
                        <td>${{ number_format($item->unit_cost, 2) }}</td>
                        <td style="text-align:right">${{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
                <tr><td colspan="3" style="text-align:right"><strong>Total</strong></td><td style="text-align:right"><strong>${{ number_format($purchaseOrder->grand_total, 2) }}</strong></td></tr>
            </tbody>
        </table>
    </div>

    <div class="panel" style="padding:16px;line-height:1.8">
        <div><span class="muted">Expected:</span> {{ $purchaseOrder->expected_date?->format('d M Y') ?: 'Not set' }}</div>
        <div><span class="muted">Received:</span> {{ $purchaseOrder->received_date?->format('d M Y') ?: 'Not yet' }}</div>
        @if($purchaseOrder->notes)
            <div><span class="muted">Notes:</span> {{ $purchaseOrder->notes }}</div>
        @endif
    </div>
@endsection
