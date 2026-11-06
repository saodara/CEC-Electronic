<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillController extends Controller
{
    public function index(): View
    {
        $bills = Bill::query()->with(['supplier', 'purchaseOrder'])->latest()->paginate(10);
        $unpaidTotal = (float) Bill::query()->where('status', 'unpaid')->sum('total');

        return view('admin.bills.index', compact('bills', 'unpaidTotal'));
    }

    public function show(Bill $bill): View
    {
        $bill->load(['supplier', 'purchaseOrder.items.product']);

        return view('admin.bills.show', compact('bill'));
    }

    public function update(Request $request, Bill $bill): RedirectResponse
    {
        $bill->update($request->validate([
            'supplier_reference' => ['nullable', 'string', 'max:255'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:bill_date'],
        ]));

        return back()->with('status', 'Bill updated.');
    }

    public function markPaid(Request $request, Bill $bill): RedirectResponse
    {
        $data = $request->validate(['paid_on' => ['required', 'date']]);

        $bill->update(['status' => 'paid', 'paid_on' => $data['paid_on']]);

        return back()->with('status', 'Bill marked as paid.');
    }

    public function markUnpaid(Bill $bill): RedirectResponse
    {
        $bill->update(['status' => 'unpaid', 'paid_on' => null]);

        return back()->with('status', 'Bill marked as unpaid.');
    }

    public function destroy(Bill $bill): RedirectResponse
    {
        if ($bill->status === 'paid') {
            return back()->withErrors(['bill' => 'A paid bill cannot be deleted. Mark it as unpaid first.']);
        }

        $bill->delete();

        return redirect()->route('admin.bills.index')->with('status', 'Bill deleted.');
    }
}
