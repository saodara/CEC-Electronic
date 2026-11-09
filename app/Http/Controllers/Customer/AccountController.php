<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ReceiptPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(Request $request): View|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('customer.login');
        }

        $orders = Order::query()
            ->when($request->user(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->take(10)
            ->get();
        $spending = $this->spending($request->user()->id);

        return view('account.dashboard', compact('orders', 'spending'));
    }

    public function orders(Request $request): View|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('customer.login');
        }

        $orders = Order::query()
            ->when($request->user(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(10);
        $spending = $this->spending($request->user()->id);

        return view('account.orders', compact('orders', 'spending'));
    }

    public function show(Request $request, Order $order): View|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('customer.login');
        }

        $order->load(['items.product', 'deliveryProvider', 'deliveryZone']);

        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        return view('account.order-show', compact('order'));
    }

    public function receipt(Request $request, Order $order, ReceiptPdf $receipts): Response|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('customer.login');
        }

        abort_unless($order->user_id === $request->user()->id, 403);

        return $receipts->download($order);
    }

    public function viewReceipt(Request $request, Order $order, ReceiptPdf $receipts): Response|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('customer.login');
        }

        abort_unless($order->user_id === $request->user()->id, 403);

        return $receipts->stream($order);
    }

    /**
     * What this customer has bought: paid total, order count and anything
     * still waiting for payment. Cancelled orders are not counted.
     */
    private function spending(int $userId): array
    {
        $orders = Order::query()
            ->where('user_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->get(['grand_total', 'payment_status']);

        return [
            'paid' => (float) $orders->where('payment_status', 'paid')->sum('grand_total'),
            'orders' => $orders->count(),
            'unpaid' => (float) $orders->where('payment_status', 'unpaid')->sum('grand_total'),
        ];
    }
}
