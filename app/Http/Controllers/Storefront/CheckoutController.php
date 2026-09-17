<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\BakongService;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // A double-submitted "Place order" (double-click, back-button resubmit,
        // slow-network retry) would otherwise reach CheckoutService with an
        // already-cleared cart from the first successful submission and crash
        // with a raw 422 — fail soft here instead, before doing any work.
        if ($this->cartService->items($request)->isEmpty()) {
            return redirect()->route('shop.cart')->with('status', 'Your cart is empty.');
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

        if ($order->payment_method === 'bakong') {
            // Generate a fixed-amount QR and store the string + md5 for payment polling.
            $qrData = app(BakongService::class)->generateQrForOrder($order);
            if ($qrData) {
                $order->update([
                    'bakong_qr_string' => $qrData['qr'],
                    'bakong_qr_md5'    => $qrData['md5'],
                ]);
            }
        }

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

        // Bakong's check-transaction API is rate-limited to a small number of
        // requests per day for the whole store. The checkout page polls this
        // route every 15s while a tab is open, so throttling outbound Bakong
        // calls to the same 15s window did nothing — a single customer
        // leaving a tab open for the ~10 minute polling window could burn
        // nearly half the daily budget alone. Throttle well below the poll
        // rate instead, so the UI can still poll for a fast response without
        // every poll spending part of the shared daily quota.
        $throttleKey = "bakong-check:{$order->id}";

        if ($order->payment_status === 'unpaid' && $order->bakong_qr_md5 && Cache::add($throttleKey, true, 60)) {
            $tx = app(BakongService::class)->checkTransactionByMd5($order->bakong_qr_md5);

            if ($tx !== null) {
                $order->update([
                    'payment_status'      => 'paid',
                    'payment_confirmed_at' => now(),
                ]);
                $order->refresh();
            }
        }

        return response()->json([
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
            'is_paid' => $order->payment_status === 'paid',
            'paid_at' => $order->payment_confirmed_at?->toIso8601String(),
        ]);
    }
}
