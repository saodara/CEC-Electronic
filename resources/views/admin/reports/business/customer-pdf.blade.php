<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Customer statement: {{ $phone }}</title>
    @include('admin.reports.business._pdf-styles')
</head>
<body>
    @php($customer = $orders->first())

    <table>
        <tr>
            <td>
                <div class="company">CEC Electronic</div>
                <div class="generated">Generated {{ $generatedAt->format('d M Y, H:i') }} (Phnom Penh time)</div>
            </td>
            <td>
                <div class="doc-title">Customer statement</div>
                <div class="doc-period">{{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <div class="summary">{{ $customer?->customer_name ?: 'Customer' }}<div class="sub">{{ $phone }}@if($customer?->customer_email) &nbsp; {{ $customer->customer_email }}@endif</div></div>

    <div class="summary">{{ number_format($orders->count()) }} {{ \Illuminate\Support\Str::plural('order', $orders->count()) }}, ${{ number_format($orders->sum('grand_total'), 2) }}</div>

    <table class="list">
        <thead>
            <tr><th>Product</th><th class="num">Quantity</th><th class="num">Unit price</th><th class="num">Total</th></tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr class="order-head">
                    <td colspan="3">{{ $order->order_number }} &nbsp; {{ $order->created_at->setTimezone($timezone)->format('d M Y') }}</td>
                    <td class="num">${{ number_format($order->grand_total, 2) }}</td>
                </tr>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td class="num">{{ number_format($item->quantity) }}</td>
                        <td class="num">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="num">${{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td class="empty" colspan="4">No paid orders in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="note">Paid orders only. Order totals include shipping and discounts. Cancelled orders are left out.</div>
</body>
</html>
