<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Order report: {{ $periodLabel }}</title>
    {{-- dompdf: table layout and DejaVu Sans only, same as the receipt. --}}
    <style>
        @page { margin: 34px 36px 46px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1f2937; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }

        .company { font-size: 16px; font-weight: bold; color: #063a74; }
        .generated { color: #6b7280; font-size: 9px; margin-top: 3px; }
        .doc-title { font-size: 15px; font-weight: bold; color: #0057a8; text-align: right; }
        .doc-period { font-size: 11px; font-weight: bold; text-align: right; margin-top: 3px; }
        .rule { border-top: 2px solid #0057a8; margin: 12px 0 14px; }

        .cards td { padding: 9px 10px; background: #f5f8fc; border: 3px solid #fff; }
        .cards .label { color: #6b7280; font-size: 8.5px; }
        .cards .value { font-size: 13px; font-weight: bold; color: #111827; margin-top: 3px; }

        .breakdown { margin-top: 12px; }
        .breakdown > tbody > tr > td { width: 50%; padding: 0 8px 0 0; }
        .breakdown > tbody > tr > td + td { padding: 0 0 0 8px; }
        .heading { font-weight: bold; color: #063a74; padding-bottom: 4px; }
        .mini td { padding: 3px 0; border-bottom: 1px solid #e5e7eb; }
        .mini .num { text-align: right; font-weight: bold; }

        .orders { margin-top: 16px; }
        .orders thead { display: table-header-group; }
        .orders tr { page-break-inside: avoid; }
        .orders th { background: #063a74; color: #fff; text-align: left; padding: 6px 7px; font-size: 8.5px; }
        .orders td { padding: 6px 7px; border-bottom: 1px solid #e5e7eb; }
        .orders .num { text-align: right; white-space: nowrap; }
        .orders .sub { color: #6b7280; font-size: 8.5px; margin-top: 1px; }
        .empty { color: #6b7280; }
        .limit-note { margin: 14px 0 -6px; padding: 7px 9px; background: #fff3cf; color: #8a5a00; font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td>
                <div class="company">CEC Electronic</div>
                <div class="generated">Generated {{ $generatedAt->format('d M Y, H:i') }} (Phnom Penh time)</div>
            </td>
            <td>
                <div class="doc-title">{{ ['day' => 'Daily', 'week' => 'Weekly', 'month' => 'Monthly', 'year' => 'Yearly'][$period] }} order report</div>
                <div class="doc-period">{{ $periodLabel }}</div>
                @if($paidOnly)
                    <div class="generated" style="text-align:right">Paid orders only</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    @php($cardWidth = $paidOnly ? '33.33%' : '25%')
    <table class="cards">
        <tr>
            <td style="width:{{ $cardWidth }}"><div class="label">{{ $paidOnly ? 'Paid orders' : 'Total orders' }}</div><div class="value">{{ number_format($summary['orders']) }}</div></td>
            <td style="width:{{ $cardWidth }}"><div class="label">Revenue (paid)</div><div class="value">${{ number_format($summary['revenue'], 2) }}</div></td>
            @unless($paidOnly)
                <td style="width:{{ $cardWidth }}"><div class="label">Awaiting payment</div><div class="value">${{ number_format($summary['awaiting_payment'], 2) }}</div></td>
            @endunless
            <td style="width:{{ $cardWidth }}"><div class="label">Cancelled orders</div><div class="value">{{ number_format($summary['cancelled']) }}</div></td>
        </tr>
    </table>

    @if($summary['orders'] > 0)
        <table class="breakdown">
            <tr>
                <td>
                    <div class="heading">By order status</div>
                    <table class="mini">
                        @foreach($byStatus as $status => $count)
                            <tr><td>{{ ucfirst($status) }}</td><td class="num">{{ number_format($count) }}</td></tr>
                        @endforeach
                    </table>
                </td>
                @unless($paidOnly)
                <td>
                    <div class="heading">By payment status</div>
                    <table class="mini">
                        @foreach($byPayment as $status => $count)
                            <tr><td>{{ ucfirst($status) }}</td><td class="num">{{ number_format($count) }}</td></tr>
                        @endforeach
                    </table>
                </td>
                @endunless
            </tr>
        </table>
    @endif

    @if($orders->count() < $summary['orders'])
        <p class="limit-note">Listing the latest {{ number_format($orders->count()) }} of {{ number_format($summary['orders']) }} orders. The totals above cover all {{ number_format($summary['orders']) }}.</p>
    @endif

    <table class="orders">
        <thead>
            <tr>
                <th>Order</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Payment</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td><strong>{{ $order->order_number }}</strong><div class="sub">{{ $order->items_count }} items</div></td>
                    <td>{{ $order->created_at->copy()->setTimezone($timezone)->format('d M Y, H:i') }}</td>
                    <td>{{ $order->customer_name }}<div class="sub">{{ $order->customer_phone }}</div></td>
                    <td>{{ ucfirst($order->status) }}</td>
                    <td>{{ ucfirst($order->payment_status) }}<div class="sub">{{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</div></td>
                    <td class="num">${{ number_format($order->grand_total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No orders in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
