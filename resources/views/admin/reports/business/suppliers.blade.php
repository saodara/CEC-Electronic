@extends('admin.layout')

@section('title', 'Supplier Report - CEC Electronic Admin')
@section('heading', 'Supplier Report')

@section('content')
    @include('admin.reports._tabs')

    <div class="toolbar">
        <div>
            <h2 style="margin:0">Suppliers: {{ $from->format('j M Y') }} - {{ $to->format('j M Y') }}</h2>
            <p class="muted" style="margin:6px 0 0">Goods received by received date, and bills by bill date.</p>
        </div>
    </div>

    @include('admin.reports.business._range')

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>Supplier</th><th>Orders received</th><th>Goods received</th><th>Billed</th><th>Paid</th><th>Unpaid</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><strong>{{ $row->name }}</strong><div class="muted">{{ $row->company_name }}</div></td>
                        <td>{{ number_format($row->received_count) }}</td>
                        <td>${{ number_format((float) $row->received_total, 2) }}</td>
                        <td>${{ number_format((float) $row->billed_total, 2) }}</td>
                        <td>${{ number_format((float) $row->paid_total, 2) }}</td>
                        <td>${{ number_format((float) $row->unpaid_total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No suppliers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
