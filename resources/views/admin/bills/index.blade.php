@extends('admin.layout')

@section('title', 'Bills - CEC Electronic Admin')
@section('heading', 'Bills')

@section('content')
    <div class="toolbar" style="flex-wrap:wrap">
        <div>
            <h2 style="margin:0">Supplier bills</h2>
            <p class="muted" style="margin:6px 0 0">What you owe suppliers. Create a bill from a received purchase order.</p>
        </div>
        <span class="status warning">Unpaid: ${{ number_format($unpaidTotal, 2) }}</span>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>Bill</th><th>Supplier</th><th>Purchase order</th><th>Due</th><th>Status</th><th>Total</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
                @forelse($bills as $bill)
                    <tr>
                        <td><strong>{{ $bill->bill_number }}</strong><div class="muted">{{ $bill->supplier_reference }}</div></td>
                        <td>{{ $bill->supplier->name }}</td>
                        <td>{{ $bill->purchaseOrder->po_number }}</td>
                        <td>{{ $bill->due_date->format('d M Y') }}</td>
                        <td>@include('admin.bills._status')</td>
                        <td>${{ number_format($bill->total, 2) }}</td>
                        <td><div class="actions"><a class="btn secondary" href="{{ route('admin.bills.show', $bill) }}">Open</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No bills yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $bills->links() }}</div>
@endsection
