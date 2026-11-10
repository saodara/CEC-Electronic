@extends('admin.layout')

@section('title', 'Product Revenue - CEC Electronic Admin')
@section('heading', 'Product Revenue')

@section('content')
    @include('admin.reports._tabs')
    @include('admin.reports.business._range')

    <h2 class="rp-title">{{ number_format($totals->quantity_sold) }} sold for ${{ number_format($totals->revenue, 2) }}</h2>
    <p class="rp-sub">{{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}. Best sellers first, from paid orders.</p>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>Product</th><th>Sold</th><th>Revenue</th><th>Cost</th><th>Profit</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    @php $share = (float) $totals->revenue > 0 ? $row->revenue / $totals->revenue * 100 : 0; @endphp
                    <tr>
                        <td>{{ $row->product_name }}</td>
                        <td class="rp-num">{{ number_format($row->quantity_sold) }}</td>
                        <td class="rp-num">
                            <strong>${{ number_format($row->revenue, 2) }}</strong>
                            <div class="rp-meter" aria-hidden="true">
                                <div class="rp-meter-track"><b class="rp-in" style="width:{{ round($share, 2) }}%"></b></div>
                                <small>{{ round($share) }}% of sales</small>
                            </div>
                        </td>
                        @if($row->missing_cost > 0)
                            <td colspan="2" class="muted">No cost price set</td>
                        @else
                            <td class="rp-num">${{ number_format($row->cost, 2) }}</td>
                            <td class="rp-num">{{ $row->revenue - $row->cost < 0 ? '-' : '' }}${{ number_format(abs($row->revenue - $row->cost), 2) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No products sold in this period. Choose a wider date range above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $rows->links() }}</div>
@endsection
