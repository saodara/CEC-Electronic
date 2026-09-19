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

        return view('account.dashboard', compact('orders'));
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

        return view('account.orders', compact('orders'));
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
}
