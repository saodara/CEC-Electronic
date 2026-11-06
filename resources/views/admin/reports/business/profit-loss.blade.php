@extends('admin.layout')

@section('title', 'Profit & Loss - CEC Electronic Admin')
@section('heading', 'Profit & Loss')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Profit &amp; Loss: {{ $from->format('j M Y') }} - {{ $to->format('j M Y') }}</h2>
            <p class="muted" style="margin:6px 0 0">Counts paid orders, the cost of the items sold, and your expenses. Cancelled orders are left out.</p>
        </div>
    </div>

    @include('admin.reports.business._range')

    @if($missingCost > 0)
        <div class="notice" style="background:#fff3cf;color:#8a5a00;border-color:#f6dd8f">{{ number_format($missingCost) }} sold {{ $missingCost === 1 ? 'item has' : 'items have' }} no cost price, so {{ $missingCost === 1 ? 'it counts' : 'they count' }} as $0 cost and profit is overstated. Add cost prices on the products.</div>
    @endif

    <div class="panel" style="overflow:hidden;max-width:720px">
        <table>
            <tbody>
                <tr><td>Product sales</td><td style="text-align:right">${{ number_format($revenue, 2) }}</td></tr>
                <tr><td>Shipping charged</td><td style="text-align:right">${{ number_format($shipping, 2) }}</td></tr>
                <tr><td>Cost of items sold</td><td style="text-align:right">-${{ number_format($cost, 2) }}</td></tr>
                <tr style="background:#f8fbff"><td><strong>Gross profit</strong></td><td style="text-align:right"><strong>{{ $grossProfit < 0 ? '-' : '' }}${{ number_format(abs($grossProfit), 2) }}</strong></td></tr>
                @foreach($expenses as $expense)
                    <tr><td class="muted">{{ $expense->category }}</td><td style="text-align:right">-${{ number_format($expense->total, 2) }}</td></tr>
                @endforeach
                <tr><td>Total expenses</td><td style="text-align:right">-${{ number_format($expenseTotal, 2) }}</td></tr>
                <tr style="background:{{ $netProfit < 0 ? '#fee4e2' : '#e7f8ef' }}"><td><strong>Net {{ $netProfit < 0 ? 'loss' : 'profit' }}</strong></td><td style="text-align:right"><strong>{{ $netProfit < 0 ? '-' : '' }}${{ number_format(abs($netProfit), 2) }}</strong></td></tr>
            </tbody>
        </table>
    </div>
@endsection
