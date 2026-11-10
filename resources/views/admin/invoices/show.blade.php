@extends('admin.layout')

@section('title', $invoice->invoice_number.' - CEC Electronic Admin')
@section('heading', 'Invoice')

@section('content')
    @php
        $order = $invoice->order;
        $isPaid = $order->payment_status === 'paid';
        $address = $order->shipping_address ?? [];
        $addressLines = array_filter([
            $address['address_line_1'] ?? null,
            $address['address_line_2'] ?? null,
            trim(($address['city'] ?? '').(! empty($address['province']) ? ', '.$address['province'] : ''), ', '),
            $address['country'] ?? null,
        ]);
        $method = $order->payment_method === 'bakong'
            ? 'KHQR (Bakong)'
            : ($order->payment_method ? ucfirst(str_replace('_', ' ', (string) $order->payment_method)) : 'Not chosen');
        $delivery = $order->deliveryProvider?->name ?: ($order->shipping_method ? ucfirst(str_replace('_', ' ', (string) $order->shipping_method)) : null);
        $amountPaid = $isPaid ? (float) $order->grand_total : 0.0;
        $balanceDue = (float) $order->grand_total - $amountPaid;
    @endphp

    <div class="toolbar" style="flex-wrap:wrap">
        <div>
            <h2 style="margin:0">{{ $invoice->invoice_number }} <span @class(['status', 'danger' => ! $isPaid]) style="vertical-align:middle">{{ $isPaid ? 'Paid' : 'Unpaid' }}</span></h2>
            <p class="muted" style="margin:6px 0 0">Issued {{ $invoice->issued_on->format('d M Y') }} &middot; Sale order <a style="color:var(--brand);font-weight:800" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn secondary" href="{{ route('admin.invoices.pdf', [$invoice, 'view' => 1]) }}" target="_blank" rel="noopener">View / print</a>
            <a class="btn" href="{{ route('admin.invoices.pdf', $invoice) }}" download>Download PDF</a>
            @if($isPaid)
                <a class="btn secondary" href="{{ route('admin.orders.receipt', $order) }}" download>Receipt</a>
            @endif
            <a class="btn secondary" href="{{ route('admin.invoices.index') }}">Back</a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:16px">
        <div class="panel" style="padding:16px;line-height:1.7">
            <div class="muted" style="font-size:12px;font-weight:800;text-transform:uppercase">Bill to</div>
            <strong>{{ $order->customer_name }}</strong>
            @if($order->customer_phone)<div class="muted">{{ $order->customer_phone }}</div>@endif
            @if($order->customer_email)<div class="muted">{{ $order->customer_email }}</div>@endif
        </div>
        <div class="panel" style="padding:16px;line-height:1.7">
            <div class="muted" style="font-size:12px;font-weight:800;text-transform:uppercase">Ship to</div>
            @forelse($addressLines as $line)
                <div>{{ $line }}</div>
            @empty
                <div>Pick up at store</div>
            @endforelse
        </div>
        <div class="panel" style="padding:16px;line-height:1.7">
            <div class="muted" style="font-size:12px;font-weight:800;text-transform:uppercase">Order details</div>
            <div>Ordered: {{ ($order->placed_at ?? $order->created_at)->format('d M Y') }}</div>
            <div>Payment: {{ $method }}</div>
            @if($isPaid && $order->payment_confirmed_at)<div>Paid on: {{ $order->payment_confirmed_at->format('d M Y') }}</div>@endif
            @if($delivery)<div>Delivery: {{ $delivery }}</div>@endif
            @if($order->tracking_number)<div>Tracking: {{ $order->tracking_number }}</div>@endif
        </div>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Unit price</th><th style="text-align:right">Amount</th></tr></thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td class="muted">{{ $loop->iteration }}</td>
                        <td>{{ $item->product_name }}@if($item->sku)<div class="muted">SKU: {{ $item->sku }}</div>@endif</td>
                        <td>{{ number_format($item->quantity) }}</td>
                        <td>${{ number_format($item->unit_price, 2) }}</td>
                        <td style="text-align:right">${{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
                <tr><td colspan="4" style="text-align:right">Subtotal</td><td style="text-align:right">${{ number_format($order->subtotal, 2) }}</td></tr>
                <tr><td colspan="4" style="text-align:right">Delivery fee</td><td style="text-align:right">${{ number_format($order->shipping_total, 2) }}</td></tr>
                @if((float) $order->discount_total > 0)
                    <tr><td colspan="4" style="text-align:right">Discount</td><td style="text-align:right">-${{ number_format($order->discount_total, 2) }}</td></tr>
                @endif
                <tr><td colspan="4" style="text-align:right"><strong>Total</strong></td><td style="text-align:right"><strong>${{ number_format($order->grand_total, 2) }}</strong></td></tr>
                <tr><td colspan="4" style="text-align:right">Amount paid</td><td style="text-align:right">${{ number_format($amountPaid, 2) }}</td></tr>
                <tr><td colspan="4" style="text-align:right"><strong>Balance due</strong></td><td style="text-align:right"><strong class="status-text {{ $balanceDue > 0 ? 'danger' : 'success' }}">${{ number_format($balanceDue, 2) }}</strong></td></tr>
            </tbody>
        </table>
    </div>

    @if($order->notes)
        <div class="panel" style="padding:16px;margin-top:16px"><strong>Customer note:</strong> {{ $order->notes }}</div>
    @endif
@endsection
