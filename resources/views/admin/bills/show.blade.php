@extends('admin.layout')

@section('title', $bill->bill_number.' - CEC Electronic Admin')
@section('heading', 'Bill')

@section('content')
    <div class="toolbar" style="flex-wrap:wrap">
        <div>
            <h2 style="margin:0">{{ $bill->bill_number }} @include('admin.bills._status')</h2>
            <p class="muted" style="margin:6px 0 0">{{ $bill->supplier->name }} &middot; Purchase order <a style="color:var(--brand);font-weight:800" href="{{ route('admin.purchase-orders.show', $bill->purchaseOrder) }}">{{ $bill->purchaseOrder->po_number }}</a></p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @if($bill->status === 'paid')
                <form action="{{ route('admin.bills.unpay', $bill) }}" method="post">@csrf<button class="btn secondary" type="submit">Mark as unpaid</button></form>
            @else
                <form action="{{ route('admin.bills.destroy', $bill) }}" method="post" onsubmit="return confirm('Delete this bill?')">@csrf @method('DELETE')<button class="btn danger" type="submit">Delete</button></form>
            @endif
            <a class="btn secondary" href="{{ route('admin.bills.index') }}">Back</a>
        </div>
    </div>

    <div class="split" style="margin-bottom:16px">
        <form class="panel" style="padding:16px;display:grid;gap:12px" action="{{ route('admin.bills.update', $bill) }}" method="post">
            @csrf
            @method('PUT')
            <strong>Bill details</strong>
            <div class="field">
                <label for="supplier_reference">Supplier's invoice number</label>
                <input id="supplier_reference" name="supplier_reference" value="{{ old('supplier_reference', $bill->supplier_reference) }}">
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="bill_date">Bill date</label>
                    <input id="bill_date" name="bill_date" type="date" value="{{ old('bill_date', $bill->bill_date->toDateString()) }}" required>
                    @error('bill_date') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label for="due_date">Due date</label>
                    <input id="due_date" name="due_date" type="date" value="{{ old('due_date', $bill->due_date->toDateString()) }}" required>
                    @error('due_date') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div><button class="btn secondary" type="submit">Save details</button></div>
        </form>

        <div class="panel" style="padding:16px;display:grid;gap:12px;align-content:start">
            <span class="muted">Total</span>
            <strong style="font-size:26px">${{ number_format($bill->total, 2) }}</strong>
            @if($bill->status === 'paid')
                <div><span class="muted">Paid on:</span> {{ $bill->paid_on?->format('d M Y') }}</div>
            @else
                <form action="{{ route('admin.bills.pay', $bill) }}" method="post" style="display:grid;gap:10px">
                    @csrf
                    <div class="field">
                        <label for="paid_on">Paid on</label>
                        <input id="paid_on" name="paid_on" type="date" value="{{ now(config('app.shop_timezone'))->toDateString() }}" required>
                        @error('paid_on') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div><button class="btn" type="submit">Mark as paid</button></div>
                </form>
            @endif
        </div>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>Product</th><th>Quantity</th><th>Unit cost</th><th style="text-align:right">Line total</th></tr></thead>
            <tbody>
                @foreach($bill->purchaseOrder->items as $item)
                    <tr>
                        <td>{{ $item->product?->name ?: 'Deleted product' }}</td>
                        <td>{{ number_format($item->quantity) }}</td>
                        <td>${{ number_format($item->unit_cost, 2) }}</td>
                        <td style="text-align:right">${{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
