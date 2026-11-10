<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Profit &amp; Loss: {{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}</title>
    {{-- dompdf: table layout and DejaVu Sans only, same as the order report. --}}
    <style>
        @page { margin: 34px 36px 46px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1f2937; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }

        .company { font-size: 16px; font-weight: bold; color: #063a74; }
        .generated { color: #6b7280; font-size: 9px; margin-top: 3px; }
        .doc-title { font-size: 15px; font-weight: bold; color: #0057a8; text-align: right; }
        .doc-period { font-size: 11px; font-weight: bold; text-align: right; margin-top: 3px; }
        .rule { border-top: 2px solid #0057a8; margin: 12px 0 16px; }

        .warning { margin-bottom: 14px; padding: 8px 10px; background: #fff3cf; color: #8a5a00; font-weight: bold; }
        .statement td { padding: 9px 10px; border-bottom: 1px solid #e5e7eb; }
        .statement .num { text-align: right; white-space: nowrap; }
        .statement .minor td { color: #6b7280; }
        .statement .minor td:first-child { padding-left: 24px; }
        .statement .total td { background: #f5f8fc; font-weight: bold; }
        .statement .profit td { background: #e7f8ef; color: #065f38; font-weight: bold; font-size: 12px; }
        .statement .loss td { background: #fee4e2; color: #8f1d14; font-weight: bold; font-size: 12px; }
        .note { color: #6b7280; font-size: 9px; margin-top: 12px; }

        .section-title { font-size: 12px; font-weight: bold; color: #063a74; margin: 22px 0 8px; }
        .products thead { display: table-header-group; }
        .products tr { page-break-inside: avoid; }
        .products th { background: #063a74; color: #fff; text-align: left; padding: 6px 8px; font-size: 9px; }
        .products td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; }
        .products .num { text-align: right; white-space: nowrap; }
        .products .empty { color: #6b7280; }
    </style>
</head>
<body>
    @php($money = fn (float $value) => ($value < 0 ? '-' : '').'$'.number_format(abs($value), 2))

    <table>
        <tr>
            <td>
                <div class="company">CEC Electronic</div>
                <div class="generated">Generated {{ $generatedAt->format('d M Y, H:i') }} (Phnom Penh time)</div>
            </td>
            <td>
                <div class="doc-title">Profit &amp; Loss</div>
                <div class="doc-period">{{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    @if($missingCost > 0)
        <div class="warning">{{ number_format($missingCost) }} sold {{ $missingCost === 1 ? 'item has' : 'items have' }} no cost price, so {{ $missingCost === 1 ? 'it counts' : 'they count' }} as $0 cost and profit is overstated.</div>
    @endif

    <table class="statement">
        <tr><td>Product sales</td><td class="num">{{ $money($revenue) }}</td></tr>
        <tr><td>Shipping charged</td><td class="num">{{ $money($shipping) }}</td></tr>
        <tr><td>Cost of items sold</td><td class="num">{{ $money(-$cost) }}</td></tr>
        <tr class="total"><td>Gross profit</td><td class="num">{{ $money($grossProfit) }}</td></tr>
        @foreach($expenses as $expense)
            <tr class="minor"><td>{{ $expense->category }}</td><td class="num">{{ $money(-(float) $expense->total) }}</td></tr>
        @endforeach
        <tr><td>Total expenses</td><td class="num">{{ $money(-$expenseTotal) }}</td></tr>
        <tr class="{{ $netProfit < 0 ? 'loss' : 'profit' }}"><td>Net {{ $netProfit < 0 ? 'loss' : 'profit' }}</td><td class="num">{{ $money($netProfit) }}</td></tr>
    </table>

    <div class="note">Counts paid orders, the cost of the items sold, and your expenses. Cancelled orders are left out.</div>

    <div class="section-title">Products sold</div>
    <table class="products">
        <thead>
            <tr><th>Product</th><th class="num">Sold</th><th class="num">Revenue</th><th class="num">Cost</th><th class="num">Profit</th></tr>
        </thead>
        <tbody>
            @forelse($soldProducts as $product)
                <tr>
                    <td>{{ $product->product_name }}</td>
                    <td class="num">{{ number_format($product->quantity_sold) }}</td>
                    <td class="num">{{ $money((float) $product->revenue) }}</td>
                    @if($product->missing_cost > 0)
                        <td class="num empty" colspan="2">No cost price set</td>
                    @else
                        <td class="num">{{ $money((float) $product->cost) }}</td>
                        <td class="num">{{ $money($product->revenue - $product->cost) }}</td>
                    @endif
                </tr>
            @empty
                <tr><td class="empty" colspan="5">No products sold in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="note">Best sellers first, before any order discounts.</div>
</body>
</html>
