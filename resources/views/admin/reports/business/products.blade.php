@extends('admin.layout')

@section('title', 'Product Revenue - CEC Electronic Admin')
@section('heading', 'Product Revenue')

@section('content')
    @include('admin.reports._tabs')

    <div class="toolbar">
        <div>
            <h2 style="margin:0">Product revenue: {{ $from->format('j M Y') }} - {{ $to->format('j M Y') }}</h2>
            <p class="muted" style="margin:6px 0 0">Best sellers first, from paid orders. Total: {{ number_format($totals->quantity_sold) }} sold for ${{ number_format($totals->revenue, 2) }}.</p>
        </div>
    </div>

    @include('admin.reports.business._range')

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>Product</th><th>Sold</th><th>Revenue</th><th>Cost</th><th>Profit</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->product_name }}</td>
                        <td>{{ number_format($row->quantity_sold) }}</td>
                        <td>${{ number_format($row->revenue, 2) }}</td>
                        @if($row->missing_cost > 0)
                            <td colspan="2" class="muted">No cost price set</td>
                        @else
                            <td>${{ number_format($row->cost, 2) }}</td>
                            <td>{{ $row->revenue - $row->cost < 0 ? '-' : '' }}${{ number_format(abs($row->revenue - $row->cost), 2) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No products sold in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $rows->links() }}</div>
@endsection
