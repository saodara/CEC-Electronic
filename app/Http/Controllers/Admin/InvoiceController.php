<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $invoices = Invoice::query()
            ->with('order')
            ->whereHas('order', fn ($order) => $order->where('payment_status', 'paid'))
            ->latest()
            ->paginate(10);

        return view('admin.invoices.index', compact('invoices'));
    }

    public function store(Order $order): RedirectResponse
    {
        if ($order->status === 'cancelled') {
            return back()->withErrors(['invoice' => 'A cancelled order cannot have an invoice.']);
        }

        $invoice = Invoice::issueFor($order);

        return redirect()->route('admin.invoices.show', $invoice)->with('status', 'Invoice ready.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['order.items', 'order.deliveryProvider']);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function pdf(Request $request, Invoice $invoice): Response
    {
        $invoice->load(['order.items', 'order.deliveryProvider']);

        $pdf = Pdf::loadView('admin.invoices.pdf', compact('invoice'))->setPaper('a4');

        return $request->boolean('view')
            ? $pdf->stream($invoice->invoice_number.'.pdf')
            : $pdf->download($invoice->invoice_number.'.pdf');
    }
}
