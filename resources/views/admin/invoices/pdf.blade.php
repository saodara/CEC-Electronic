<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    {{-- dompdf: table layout and DejaVu Sans only, same as the receipt. --}}
    <style>
        @page { margin: 36px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1f2937; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .company { font-size: 16px; font-weight: bold; color: #063a74; }
        .muted { color: #6b7280; font-size: 9.5px; line-height: 1.6; }
        .title { font-size: 22px; font-weight: bold; color: #0057a8; text-align: right; }
        .right { text-align: right; }
        .rule { border-top: 2px solid #0057a8; margin: 12px 0 14px; }
        .items { margin-top: 16px; }
        .items th { background: #063a74; color: #fff; text-align: left; padding: 7px 8px; font-size: 9px; }
        .items td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; }
        .items .num { text-align: right; white-space: nowrap; }
        .totals td { border-bottom: 0; padding: 4px 8px; }
        .grand td { background: #0057a8; color: #fff; font-weight: bold; font-size: 12px; padding: 8px; }
    </style>
</head>
<body>
    @php($order = $invoice->order)
    <table>
        <tr>
            <td>
                <div class="company">CEC Electronic</div>
                <div class="muted">Phnom Penh, Cambodia<br>012 220 152</div>
            </td>
            <td>
                <div class="title">INVOICE</div>
                <div class="muted right"><strong>{{ $invoice->invoice_number }}</strong><br>Issued {{ $invoice->issued_on->format('d M Y') }}<br>Sale order {{ $order->order_number }}<br>Payment: {{ ucfirst($order->payment_status) }}</div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <strong>Bill to</strong>
    <div class="muted">{{ $order->customer_name }}<br>{{ $order->customer_phone }}{{ $order->customer_email ? ' · '.$order->customer_email : '' }}</div>

    <table class="items">
        <thead><tr><th>Product</th><th class="num">Quantity</th><th class="num">Unit price</th><th class="num">Line total</th></tr></thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}<div class="muted">{{ $item->sku }}</div></td>
                    <td class="num">{{ number_format($item->quantity) }}</td>
                    <td class="num">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="num">${{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="margin-top:8px">
        <tr><td style="width:65%"></td><td>Subtotal</td><td class="right">${{ number_format($order->subtotal, 2) }}</td></tr>
        <tr><td></td><td>Shipping</td><td class="right">${{ number_format($order->shipping_total, 2) }}</td></tr>
        <tr class="grand"><td style="background:#fff"></td><td>Total</td><td class="right">${{ number_format($order->grand_total, 2) }}</td></tr>
    </table>
</body>
</html>
