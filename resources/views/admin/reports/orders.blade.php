@extends('admin.layout')

@section('title', 'Order Report - CEC Electronic Admin')
@section('heading', 'Reports')

@section('content')
    @php
        $statusClasses = [
            'pending' => 'orange',
            'processing' => 'info',
            'shipped' => 'info',
            'completed' => '',
            'cancelled' => 'danger',
        ];
        $paymentClasses = [
            'unpaid' => 'danger',
            'paid' => 'success',
            'refunded' => 'info',
            'failed' => 'danger',
        ];
    @endphp

    <div class="toolbar" style="flex-wrap:wrap">
        <div>
            <h2 style="margin:0">{{ ['day' => 'Daily', 'week' => 'Weekly', 'month' => 'Monthly', 'year' => 'Yearly'][$period] }} orders: {{ $periodLabel }}</h2>
            <p class="muted" style="margin:6px 0 0">{{ $paidOnly ? 'Only paid orders are included.' : 'Revenue counts paid orders only.' }} Cancelled orders are left out of the money totals.</p>
        </div>
        <div style="display:flex;gap:8px">
            <a class="btn" href="{{ route('admin.reports.orders.pdf', ['period' => $period, 'date' => $selectedDate]) }}" download>Export PDF</a>
            <a class="btn secondary" href="{{ route('admin.reports.orders', ['period' => $period, 'date' => $previousDate]) }}">&larr; Previous</a>
            @if($nextDate)
                <a class="btn secondary" href="{{ route('admin.reports.orders', ['period' => $period, 'date' => $nextDate]) }}">Next &rarr;</a>
            @endif
        </div>
    </div>

    <form class="panel" method="get" action="{{ route('admin.reports.orders', ['period' => $period]) }}" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:12px;padding:14px;margin-bottom:18px">
        @if($period === 'year')
            <div class="field">
                <label for="date">Year</label>
                <select id="date" name="date">
                    @foreach($yearOptions as $year)
                        <option value="{{ $year }}-01-01" @selected(str_starts_with($selectedDate, $year.'-'))>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <div class="field">
                <label for="date">Date</label>
                <input id="date" type="date" name="date" value="{{ $selectedDate }}" max="{{ $today }}">
            </div>
        @endif
        <button class="btn" type="submit">Apply</button>
        <a class="btn secondary" href="{{ route('admin.reports.orders', ['period' => $period]) }}">Today</a>
    </form>

    <div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
        <div class="panel stat">
            <span class="stat-icon blue">&#129534;</span>
            <div class="stat-body"><span>{{ $paidOnly ? 'Paid orders' : 'Total orders' }}</span><strong>{{ number_format($summary['orders']) }}</strong></div>
        </div>
        <div class="panel stat">
            <span class="stat-icon green">&#128176;</span>
            <div class="stat-body"><span>Revenue (paid)</span><strong>${{ number_format($summary['revenue'], 2) }}</strong></div>
        </div>
        @unless($paidOnly)
        <div class="panel stat">
            <span class="stat-icon amber">&#9203;</span>
            <div class="stat-body"><span>Awaiting payment</span><strong>${{ number_format($summary['awaiting_payment'], 2) }}</strong></div>
        </div>
        @endunless
        <div class="panel stat">
            <span class="stat-icon red">&#10005;</span>
            <div class="stat-body"><span>Cancelled orders</span><strong>{{ number_format($summary['cancelled']) }}</strong></div>
        </div>
    </div>

    @if($summary['orders'] > 0)
        <div class="{{ $paidOnly ? '' : 'split' }}" style="margin-bottom:18px">
            <div class="panel">
                <div class="mini-list">
                    <strong>By order status</strong>
                    @foreach($byStatus as $status => $count)
                        <div class="mini-item">
                            <span class="status {{ $statusClasses[$status] ?? '' }}">{{ ucfirst($status) }}</span>
                            <strong>{{ number_format($count) }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
            @unless($paidOnly)
            <div class="panel">
                <div class="mini-list">
                    <strong>By payment status</strong>
                    @foreach($byPayment as $status => $count)
                        <div class="mini-item">
                            <span class="status-text {{ $paymentClasses[$status] ?? '' }}">{{ ucfirst($status) }}</span>
                            <strong>{{ number_format($count) }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
            @endunless
        </div>
    @endif

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Total</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <strong>{{ $order->order_number }}</strong>
                            <div class="muted">{{ $order->items_count }} items</div>
                        </td>
                        <td>{{ $order->created_at->copy()->setTimezone($timezone)->format('d M Y, H:i') }}</td>
                        <td>{{ $order->customer_name }}<div class="muted">{{ $order->customer_phone }}</div></td>
                        <td><span class="status {{ $statusClasses[$order->status] ?? '' }}">{{ ucfirst($order->status) }}</span></td>
                        <td>
                            <span class="status-text {{ $paymentClasses[$order->payment_status] ?? '' }}">{{ ucfirst($order->payment_status) }}</span>
                            <div class="muted">{{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</div>
                        </td>
                        <td>${{ number_format($order->grand_total, 2) }}</td>
                        <td><div class="actions"><a class="btn secondary" href="{{ route('admin.orders.show', $order) }}">Open</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No orders in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $orders->links() }}</div>
@endsection
