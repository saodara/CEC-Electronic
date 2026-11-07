<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $bill->bill_number }}</title>
    {{-- dompdf: table layout and DejaVu Sans only, same as the invoice. --}}
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
    <table>
        <tr>
            <td>
                <div class="company">CEC Electronic</div>
                <div class="muted">Phnom Penh, Cambodia<br>012 220 152</div>
            </td>
            <td>
                <div class="title">PAYMENT RECEIPT</div>
                <div class="muted right"><strong>{{ $bill->bill_number }}</strong><br>Paid on {{ $bill->paid_on?->format('d M Y') }}<br>Purchase order {{ $bill->purchaseOrder->po_number }}@if($bill->supplier_reference)<br>Supplier's invoice {{ $bill->supplier_reference }}@endif</div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <strong>Paid to</strong>
    <div class="muted">{{ $bill->supplier->name }}@if($bill->supplier->phone)<br>{{ $bill->supplier->phone }}@endif @if($bill->supplier->email)<br>{{ $bill->supplier->email }}@endif @if($bill->supplier->address)<br>{{ $bill->supplier->address }}@endif</div>

    <table class="items">
        <thead><tr><th>Product</th><th class="num">Quantity</th><th class="num">Unit cost</th><th class="num">Line total</th></tr></thead>
        <tbody>
            @foreach($bill->purchaseOrder->items as $item)
                <tr>
                    <td>{{ $item->product?->name ?: 'Deleted product' }}</td>
                    <td class="num">{{ number_format($item->quantity) }}</td>
                    <td class="num">${{ number_format($item->unit_cost, 2) }}</td>
                    <td class="num">${{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="margin-top:8px">
        <tr class="grand"><td style="background:#fff;width:65%"></td><td>Total paid</td><td class="right">${{ number_format($bill->total, 2) }}</td></tr>
    </table>
</body>
</html>
