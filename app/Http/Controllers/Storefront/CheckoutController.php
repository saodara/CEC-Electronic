<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private CheckoutService $checkoutService
    ) {
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()
                ->guest(route('customer.login'))
                ->with('status', 'Please login or register before checkout.');
        }

        $items = $this->cartService->items($request);
        $subtotal = $this->cartService->subtotal($request);

        return view('checkout.create', compact('items', 'subtotal'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()) {
            return redirect()
                ->guest(route('customer.login'))
                ->with('status', 'Please login or register before checkout.');
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'shipping_method' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $order = $this->checkoutService->createOrder($request, $data);

        return redirect()->route('checkout.success', $order)->with('status', 'Order placed.');
    }

    public function success(Request $request, Order $order): View
    {
        abort_unless($request->user() && $order->user_id === $request->user()->id, 403);

        $order->load('items');

        return view('checkout.success', compact('order'));
    }

    public function paymentStatus(Request $request, Order $order): JsonResponse
    {
        abort_unless($request->user() && $order->user_id === $request->user()->id, 403);

        return response()->json([
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
            'is_paid' => $order->payment_status === 'paid',
            'paid_at' => $order->payment_confirmed_at?->toIso8601String(),
        ]);
    }
}
