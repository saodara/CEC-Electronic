@extends('admin.layout')

@section('title', 'Stock Adjustments - CEC Electronic Admin')
@section('heading', 'Stock Adjustment')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Stock adjustments</h2>
            <p class="muted" style="margin:6px 0 0">Add or remove stock by hand. Every change is kept here as a record.</p>
        </div>
        <a class="btn" href="{{ route('admin.stock-adjustments.create') }}">New adjustment</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>Date</th><th>Product</th><th>Change</th><th>Stock after</th><th>Reason</th><th>By</th></tr></thead>
            <tbody>
                @forelse($adjustments as $adjustment)
                    <tr>
                        <td>{{ $adjustment->created_at->copy()->setTimezone(config('app.shop_timezone'))->format('d M Y, H:i') }}</td>
                        <td>{{ $adjustment->product_name }}</td>
                        <td><span class="status-text {{ $adjustment->quantity_change > 0 ? 'success' : 'danger' }}">{{ $adjustment->quantity_change > 0 ? '+' : '' }}{{ number_format($adjustment->quantity_change) }}</span></td>
                        <td>{{ number_format($adjustment->stock_after) }}</td>
                        <td>{{ $adjustment->reason }}<div class="muted">{{ $adjustment->notes }}</div></td>
                        <td>{{ $adjustment->user?->name ?: 'Unknown' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No stock adjustments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $adjustments->links() }}</div>
@endsection
