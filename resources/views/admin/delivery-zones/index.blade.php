@extends('admin.layout')

@section('title', 'Delivery - CEC Electronic Admin')
@section('heading', 'Delivery')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Delivery</h2>
            <p class="muted" style="margin:6px 0 0">Configure delivery areas, lead time, and availability.</p>
        </div>
        <a class="btn" href="{{ route('admin.delivery-zones.create') }}">Add New Delivery</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>Zone</th><th>Location</th><th>Estimate</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
                @forelse($zones as $zone)
                    <tr>
                        <td><strong>{{ $zone->name }}</strong></td>
                        <td>{{ $zone->city ?: 'Any city' }}<div class="muted">{{ $zone->province }}</div></td>
                        <td>{{ $zone->estimated_days }} day(s)</td>
                        <td><span class="status">{{ $zone->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td><div class="actions"><a class="btn secondary" href="{{ route('admin.delivery-zones.edit', $zone) }}">Edit</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No delivery entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $zones->links() }}</div>
@endsection
