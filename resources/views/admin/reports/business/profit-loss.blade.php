@extends('admin.layout')

@section('title', 'Profit & Loss - CEC Electronic Admin')
@section('heading', 'Profit & Loss')

@php
    $money = fn (float $value) => ($value < 0 ? '-' : '').'$'.number_format(abs($value), 2);

    $lines = [
        ['label' => 'Product sales', 'change' => $revenue],
        ['label' => 'Shipping charged', 'change' => $shipping],
        ['label' => 'Cost of items sold', 'change' => -$cost],
        ['label' => 'Gross profit', 'total' => true],
    ];
    foreach ($expenses as $expense) {
        $lines[] = ['label' => $expense->category, 'change' => -(float) $expense->total, 'minor' => true];
    }
    $lines[] = ['label' => 'Net '.($netProfit < 0 ? 'loss' : 'profit'), 'total' => true, 'result' => true];

    // Each line's bar starts where the previous line's money left off.
    $running = 0.0;
    foreach ($lines as $index => $line) {
        $start = empty($line['total']) ? $running : 0.0;
        $running += $line['change'] ?? 0.0;
        $lines[$index] += ['start' => $start, 'end' => $running];
    }

    $low = min(0.0, ...array_column($lines, 'end'));
    $high = max(0.0, ...array_column($lines, 'end'));
    $span = $high - $low;
    $position = fn (float $value) => $span > 0 ? ($value - $low) / $span * 100 : 0;

    $hasActivity = $span > 0;
    $headline = match (true) {
        ! $hasActivity => 'No sales or expenses in this period',
        $netProfit < 0 => 'You lost '.($missingCost > 0 ? 'at least ' : '').$money(abs($netProfit)),
        default => 'You made '.($missingCost > 0 ? 'up to ' : '').$money($netProfit).' in profit',
    };
@endphp

@section('content')
    @include('admin.reports._tabs')
    @include('admin.reports.business._range', ['pdfRoute' => 'admin.reports.profit-loss.pdf'])

    <h2 class="rp-title">{{ $headline }}</h2>
    <p class="rp-sub">{{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}. Counts paid orders, the cost of the items sold, and your expenses. Cancelled orders are left out.</p>

    @if($missingCost > 0)
        <div class="notice" style="background:#fff3cf;color:#8a5a00;border-color:#f6dd8f;max-width:920px">
            <span>{{ number_format($missingCost) }} sold {{ $missingCost === 1 ? 'item has' : 'items have' }} no cost price, so {{ $missingCost === 1 ? 'it counts' : 'they count' }} as $0 cost and profit is overstated. <a class="rp-link" style="color:inherit" href="{{ route('admin.products.index') }}">Add cost prices on the products.</a></span>
        </div>
    @endif

    <style>
        .rp-bridge-panel{max-width:920px;padding:6px 4px;overflow:hidden}
        .rp-bridge{display:table;width:100%;border:0;border-radius:0;box-shadow:none}
        .rp-bridge td{padding:10px 14px;border-bottom:1px solid var(--line)}
        .rp-bridge tr:last-child td{border-bottom:0}
        .rp-bridge tbody tr:hover{background:none}
        .rp-bridge-label{width:190px}
        .rp-bridge-minor .rp-bridge-label{color:var(--muted);padding-left:28px}
        .rp-bridge-amount{width:124px;text-align:right;font-weight:700;white-space:nowrap}
        .rp-track{position:relative;height:18px}
        .rp-zero{position:absolute;top:-10px;bottom:-10px;width:1px;background:#c4cede}
        .rp-bar{position:absolute;top:1px;bottom:1px;min-width:2px;border-radius:3px}
        .rp-bridge-total td{background:#f8fbff;font-weight:800}
        .rp-result-profit td{background:#e7f8ef;color:#065f38}
        .rp-result-loss td{background:#fee4e2;color:#8f1d14}
        @media (max-width:640px){
            .rp-bridge,.rp-bridge tbody{display:block}
            .rp-bridge tr{display:grid;grid-template-columns:1fr auto}
            .rp-bridge td{border-bottom:0}
            .rp-bridge tr{border-bottom:1px solid var(--line)}
            .rp-bridge-label,.rp-bridge-amount{width:auto}
            .rp-bridge-chart{grid-column:1 / -1;grid-row:2;padding-top:0}
        }
    </style>

    <div class="rp-legend" aria-hidden="true">
        <span><i class="rp-in"></i>Money in</span>
        <span><i class="rp-out"></i>Money out</span>
        <span><i class="rp-total"></i>Total so far</span>
    </div>

    <div class="panel rp-bridge-panel">
        <table class="rp-bridge">
            <tbody>
                @foreach($lines as $line)
                    @php
                        $isTotal = ! empty($line['total']);
                        $left = $position(min($line['start'], $line['end']));
                        $width = abs($position($line['end']) - $position($line['start']));
                        $bar = $isTotal ? 'rp-total' : (($line['change'] ?? 0) < 0 ? 'rp-out' : 'rp-in');
                        $rowClasses = [
                            'rp-bridge-total' => $isTotal,
                            'rp-bridge-minor' => ! empty($line['minor']),
                            'rp-result-profit' => ! empty($line['result']) && $netProfit >= 0,
                            'rp-result-loss' => ! empty($line['result']) && $netProfit < 0,
                        ];
                    @endphp
                    <tr @class($rowClasses)>
                        <td class="rp-bridge-label">{{ $line['label'] }}</td>
                        <td class="rp-bridge-chart" aria-hidden="true">
                            <div class="rp-track">
                                <span class="rp-zero" style="left:{{ round($position(0.0), 2) }}%"></span>
                                @if($hasActivity && ($isTotal || ($line['change'] ?? 0) != 0))
                                    <span class="rp-bar {{ $bar }}" style="left:{{ round($left, 2) }}%;width:{{ round($width, 2) }}%" title="{{ $line['label'] }}: running total {{ $money($line['end']) }}"></span>
                                @endif
                            </div>
                        </td>
                        <td class="rp-bridge-amount rp-num">{{ $money($isTotal ? $line['end'] : $line['change']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="rp-sub" style="margin-top:12px">Total expenses: <strong class="rp-num">{{ $money($expenseTotal) }}</strong>. Add or change them on <a href="{{ route('admin.expenses.index') }}">Expenses</a>.</p>

    <h3 style="font-size:19px;margin:26px 0 4px">Products sold</h3>
    <p class="rp-sub" style="margin-bottom:12px">Best sellers first, before any order discounts.</p>

    <div class="panel" style="overflow:hidden;max-width:920px">
        <table>
            <thead><tr><th>Product</th><th>Sold</th><th>Revenue</th><th>Cost</th><th>Profit</th></tr></thead>
            <tbody>
                @forelse($soldProducts as $product)
                    <tr>
                        <td>{{ $product->product_name }}</td>
                        <td class="rp-num">{{ number_format($product->quantity_sold) }}</td>
                        <td class="rp-num">{{ $money((float) $product->revenue) }}</td>
                        @if($product->missing_cost > 0)
                            <td colspan="2" class="muted">No cost price set</td>
                        @else
                            <td class="rp-num">{{ $money((float) $product->cost) }}</td>
                            <td class="rp-num">{{ $money($product->revenue - $product->cost) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No products sold in this period. Choose a wider date range above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
