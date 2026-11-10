<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Customer report: {{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}</title>
    @include('admin.reports.business._pdf-styles')
</head>
<body>
    <table>
        <tr>
            <td>
                <div class="company">CEC Electronic</div>
                <div class="generated">Generated {{ $generatedAt->format('d M Y, H:i') }} (Phnom Penh time)</div>
            </td>
            <td>
                <div class="doc-title">Customer report</div>
                <div class="doc-period">{{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <div class="summary">{{ number_format($totals->customers) }} {{ \Illuminate\Support\Str::plural('customer', (int) $totals->customers) }}, {{ number_format($totals->orders_count) }} {{ \Illuminate\Support\Str::plural('order', (int) $totals->orders_count) }}, ${{ number_format($totals->total_spent, 2) }}</div>

    <table class="list">
        <thead>
            <tr><th>Customer</th><th class="num">Orders</th><th class="num">Total spent</th><th class="num">Share</th><th class="num">Last order</th></tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->customer_name }}<div class="sub">{{ $row->customer_phone }}</div></td>
                    <td class="num">{{ number_format($row->orders_count) }}</td>
                    <td class="num">${{ number_format($row->total_spent, 2) }}</td>
                    <td class="num">{{ (float) $totals->total_spent > 0 ? round($row->total_spent / $totals->total_spent * 100) : 0 }}%</td>
                    <td class="num">{{ \Illuminate\Support\Carbon::parse($row->last_order_at, 'UTC')->setTimezone($timezone)->format('d M Y') }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="5">No customer orders in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="note">Top spenders first, from paid orders. Cancelled orders are left out.</div>
</body>
</html>
