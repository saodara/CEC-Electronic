@extends('admin.layout')

@section('title', $invoice->invoice_number.' - CEC Electronic Admin')
@section('heading', 'Invoice')

@section('content')
    @php($order = $invoice->order)
    <div class="toolbar" style="flex-wrap:wrap">
        <div>
            <h2 style="margin:0">{{ $invoice->invoice_number }}</h2>
            <p class="muted" style="margin:6px 0 0">Issued {{ $invoice->issued_on->format('d M Y') }} &middot; Sale order <a style="color:var(--brand);font-weight:800" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a> &middot; Payment: {{ ucfirst($order->payment_status) }}</p>
        </div>
        <div style="display:flex;gap:8px">
            <a class="btn" href="{{ route('admin.invoices.pdf', $invoice) }}" download>Download PDF</a>
            <a class="btn secondary" href="{{ route('admin.invoices.index') }}">Back</a>
        </div>
    </div>

    <div class="panel" style="padding:16px;margin-bottom:16px;line-height:1.7">
        <strong>{{ $order->customer_name }}</strong>
        <div class="muted">{{ $order->customer_phone }}{{ $order->customer_email ? ' · '.$order->customer_email : '' }}</div>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>Product</th><th>Quantity</th><th>Unit price</th><th style="text-align:right">Line total</th></tr></thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}<div class="muted">{{ $item->sku }}</div></td>
                        <td>{{ number_format($item->quantity) }}</td>
                        <td>${{ number_format($item->unit_price, 2) }}</td>
                        <td style="text-align:right">${{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
                <tr><td colspan="3" style="text-align:right">Subtotal</td><td style="text-align:right">${{ number_format($order->subtotal, 2) }}</td></tr>
                <tr><td colspan="3" style="text-align:right">Shipping</td><td style="text-align:right">${{ number_format($order->shipping_total, 2) }}</td></tr>
                <tr><td colspan="3" style="text-align:right"><strong>Total</strong></td><td style="text-align:right"><strong>${{ number_format($order->grand_total, 2) }}</strong></td></tr>
            </tbody>
        </table>
    </div>
@endsection
