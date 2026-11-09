<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BillController extends Controller
{
    public function index(Request $request): View
    {
        if ($request->query('tab') === 'customers') {
            $filters = [
                'q' => trim((string) $request->query('q', '')),
                'payment' => in_array($request->query('payment'), ['paid', 'unpaid'], true) ? $request->query('payment') : '',
                'from' => $this->dateFilter($request->query('from')),
                'to' => $this->dateFilter($request->query('to')),
            ];
            $timezone = config('app.shop_timezone');

            $orders = Order::query()
                ->with(['items', 'invoice'])
                ->where('status', '!=', 'cancelled')
                ->when($filters['q'] !== '', function ($query) use ($filters) {
                    $term = '%'.mb_strtolower($filters['q']).'%';
                    $query->where(fn ($q) => $q->whereRaw('LOWER(customer_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(order_number) LIKE ?', [$term])
                        ->orWhere('customer_phone', 'like', '%'.$filters['q'].'%'));
                })
                ->when($filters['payment'] !== '', fn ($query) => $query->where('payment_status', $filters['payment']))
                ->when($filters['from'], fn ($query) => $query->where('created_at', '>=', \Illuminate\Support\Carbon::parse($filters['from'], $timezone)->startOfDay()->utc()))
                ->when($filters['to'], fn ($query) => $query->where('created_at', '<=', \Illuminate\Support\Carbon::parse($filters['to'], $timezone)->endOfDay()->utc()))
                ->latest()
                ->paginate(10)
                ->withQueryString();

            $active = Order::query()->where('status', '!=', 'cancelled');
            $paidTotal = (float) (clone $active)->where('payment_status', 'paid')->sum('grand_total');
            $unpaidCustomerTotal = (float) (clone $active)->where('payment_status', 'unpaid')->sum('grand_total');

            $userIds = $orders->pluck('user_id')->filter()->unique();
            $phones = $orders->whereNull('user_id')->pluck('customer_phone')->filter()->unique();
            $spentByUser = $userIds->isEmpty() ? collect() : (clone $active)->where('payment_status', 'paid')
                ->whereIn('user_id', $userIds)->groupBy('user_id')
                ->selectRaw('user_id, SUM(grand_total) as spent')->pluck('spent', 'user_id');
            $spentByPhone = $phones->isEmpty() ? collect() : (clone $active)->where('payment_status', 'paid')
                ->whereNull('user_id')->whereIn('customer_phone', $phones)->groupBy('customer_phone')
                ->selectRaw('customer_phone, SUM(grand_total) as spent')->pluck('spent', 'customer_phone');

            return view('admin.bills.index', [
                'tab' => 'customers',
                'orders' => $orders,
                'filters' => $filters,
                'paidTotal' => $paidTotal,
                'unpaidCustomerTotal' => $unpaidCustomerTotal,
                'spentByUser' => $spentByUser,
                'spentByPhone' => $spentByPhone,
            ]);
        }

        $bills = Bill::query()->with(['supplier', 'purchaseOrder'])->latest()->paginate(10);
        $unpaidTotal = (float) Bill::query()->where('status', 'unpaid')->sum('total');

        return view('admin.bills.index', ['tab' => 'suppliers', 'bills' => $bills, 'unpaidTotal' => $unpaidTotal]);
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

    public function receipt(Bill $bill): Response|RedirectResponse
    {
        if ($bill->status !== 'paid') {
            return redirect()->route('admin.bills.show', $bill)
                ->withErrors(['bill' => 'A receipt is only available after the bill is paid.']);
        }

        $bill->load(['supplier', 'purchaseOrder.items.product']);

        return Pdf::loadView('admin.bills.receipt', compact('bill'))
            ->setPaper('a4')
            ->download('Receipt-'.$bill->bill_number.'.pdf');
    }

    public function destroy(Bill $bill): RedirectResponse
    {
        if ($bill->status === 'paid') {
            return back()->withErrors(['bill' => 'A paid bill cannot be deleted. Mark it as unpaid first.']);
        }

        $bill->delete();

        return redirect()->route('admin.bills.index')->with('status', 'Bill deleted.');
    }

    private function dateFilter(mixed $value): string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }
}
