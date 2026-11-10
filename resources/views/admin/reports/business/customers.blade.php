@extends('admin.layout')

@section('title', 'Customer Report - CEC Electronic Admin')
@section('heading', 'Customer Report')

@section('content')
    @include('admin.reports._tabs')
    @include('admin.reports.business._range', ['pdfRoute' => 'admin.reports.customers.pdf'])

    <h2 class="rp-title">{{ number_format($totals->customers) }} {{ \Illuminate\Support\Str::plural('customer', (int) $totals->customers) }}, {{ number_format($totals->orders_count) }} {{ \Illuminate\Support\Str::plural('order', (int) $totals->orders_count) }}, ${{ number_format($totals->total_spent, 2) }}</h2>
    <p class="rp-sub">{{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}. Top spenders first, from paid orders.</p>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>Customer</th><th>Orders</th><th>Total spent</th><th>Last order</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    @php $share = (float) $totals->total_spent > 0 ? $row->total_spent / $totals->total_spent * 100 : 0; @endphp
                    <tr>
                        <td><strong>{{ $row->customer_name }}</strong><div class="muted">{{ $row->customer_phone }}</div></td>
                        <td class="rp-num">{{ number_format($row->orders_count) }}</td>
                        <td class="rp-num">
                            <strong>${{ number_format($row->total_spent, 2) }}</strong>
                            <div class="rp-meter" aria-hidden="true">
                                <div class="rp-meter-track"><b class="rp-in" style="width:{{ round($share, 2) }}%"></b></div>
                                <small>{{ round($share) }}% of spending</small>
                            </div>
                        </td>
                        <td>{{ \Illuminate\Support\Carbon::parse($row->last_order_at, 'UTC')->setTimezone(config('app.shop_timezone'))->format('d M Y') }}</td>
                        <td><div class="actions">
                            <a class="btn secondary" href="{{ route('admin.reports.customers.pdf', ['phone' => $row->customer_phone, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" download>PDF</a>
                            <a class="btn secondary" href="{{ route('admin.customers.show', $row->customer_phone) }}">Open</a>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No customer orders in this period. Choose a wider date range above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $rows->links() }}</div>
@endsection
