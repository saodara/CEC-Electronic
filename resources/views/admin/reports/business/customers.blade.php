@extends('admin.layout')

@section('title', 'Customer Report - CEC Electronic Admin')
@section('heading', 'Customer Report')

@section('content')
    @include('admin.reports._tabs')

    <div class="toolbar">
        <div>
            <h2 style="margin:0">Customers: {{ $from->format('j M Y') }} - {{ $to->format('j M Y') }}</h2>
            <p class="muted" style="margin:6px 0 0">Top spenders first, from paid orders. Total: {{ number_format($totals->customers) }} customers, {{ number_format($totals->orders_count) }} orders, ${{ number_format($totals->total_spent, 2) }}.</p>
        </div>
    </div>

    @include('admin.reports.business._range')

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>Customer</th><th>Orders</th><th>Total spent</th><th>Last order</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><strong>{{ $row->customer_name }}</strong><div class="muted">{{ $row->customer_phone }}</div></td>
                        <td>{{ number_format($row->orders_count) }}</td>
                        <td>${{ number_format($row->total_spent, 2) }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($row->last_order_at, 'UTC')->setTimezone(config('app.shop_timezone'))->format('d M Y') }}</td>
                        <td><div class="actions"><a class="btn secondary" href="{{ route('admin.customers.show', $row->customer_phone) }}">Open</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No customer orders in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $rows->links() }}</div>
@endsection
