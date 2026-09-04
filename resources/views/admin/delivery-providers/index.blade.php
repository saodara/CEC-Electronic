@extends('admin.layout')

@section('title', 'Delivery Providers - CEC Electronic Admin')
@section('heading', 'Delivery Providers')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Delivery providers</h2>
            <p class="muted" style="margin:6px 0 0">Manage couriers, base fees, tracking links, and contact details.</p>
        </div>
        <a class="btn" href="{{ route('admin.delivery-providers.create') }}">Add provider</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>Provider</th><th>Contact</th><th>Base fee</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
                @forelse($providers as $provider)
                    <tr>
                        <td><strong>{{ $provider->name }}</strong><div class="muted">{{ $provider->tracking_url }}</div></td>
                        <td>{{ $provider->phone }}<div class="muted">{{ $provider->email }}</div></td>
                        <td>${{ number_format($provider->base_fee, 2) }}</td>
                        <td><span class="status">{{ $provider->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td><div class="actions"><a class="btn secondary" href="{{ route('admin.delivery-providers.edit', $provider) }}">Edit</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No delivery providers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $providers->links() }}</div>
@endsection
