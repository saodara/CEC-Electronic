@extends('admin.layout')

@section('title', 'Invoices - CEC Electronic Admin')
@section('heading', 'Invoices')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Customer invoices</h2>
            <p class="muted" style="margin:6px 0 0">Invoices for customers who have paid. Each one is created automatically when the payment is confirmed.</p>
        </div>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>Invoice</th><th>Sale order</th><th>Customer</th><th>Issued</th><th>Payment</th><th>Total</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td><strong>{{ $invoice->invoice_number }}</strong></td>
                        <td>{{ $invoice->order->order_number }}</td>
                        <td>{{ $invoice->order->customer_name }}<div class="muted">{{ $invoice->order->customer_phone }}</div></td>
                        <td>{{ $invoice->issued_on->format('d M Y') }}</td>
                        <td>{{ ucfirst($invoice->order->payment_status) }}</td>
                        <td>${{ number_format($invoice->order->grand_total, 2) }}</td>
                        <td><div class="actions"><a class="btn secondary" href="{{ route('admin.invoices.show', $invoice) }}">Open</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No invoices for paid orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $invoices->links() }}</div>
@endsection
