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

        if ($order->payment_method === 'bakong') {
            $bakong = app(BakongService::class);

            // Generate a fixed-amount QR and store the string + md5 for payment polling.
            $qrData = $bakong->generateQrForOrder($order);
            if ($qrData) {
                $updates = [
                    'bakong_qr_string' => $qrData['qr'],
                    'bakong_qr_md5'    => $qrData['md5'],
                ];

                // Optionally try hosted web checkout for a better mobile experience.
                $session = $bakong->createWebCheckout(
                    $order,
                    route('checkout.success', $order),
                    route('bakong.webhook')
                );
                if ($session) {
                    $updates['bakong_session_id']   = $session['session_id'];
                    $updates['bakong_checkout_url'] = $session['checkout_url'];
                }

                $order->update($updates);
            }
        }

        return redirect()->route('checkout.success', $order)->with('status', 'Order placed.');
    }

    public function success(Request $request, Order $order): View
    {
        abort_unless($request->user() && $order->user_id === $request->user()->id, 403);

        $order->load('items');

        $bakongQrImage = null;
        if ($order->payment_method === 'bakong'
            && $order->payment_status !== 'paid'
            && $order->bakong_qr_string
            && ! $order->bakong_checkout_url
        ) {
            $bakongQrImage = app(BakongService::class)->generateImage($order->bakong_qr_string);
        }

        return view('checkout.success', compact('order', 'bakongQrImage'));
    }

    public function paymentStatus(Request $request, Order $order): JsonResponse
    {
        abort_unless($request->user() && $order->user_id === $request->user()->id, 403);

        if ($order->payment_status === 'unpaid') {
            $bakong = app(BakongService::class);
            $paid = false;

            if ($order->bakong_session_id) {
                $details = $bakong->getCheckoutDetails($order->bakong_session_id);
                $paid = ($details['status'] ?? '') === 'PAID';
            } elseif ($order->bakong_qr_md5) {
                $tx = $bakong->checkTransactionByMd5($order->bakong_qr_md5);
                $paid = $tx !== null;
            }

            if ($paid) {
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

    /**
     * Bakong Relay webhook — called server-to-server when payment completes.
     * No CSRF token required (exempted in routes/web.php).
     */
    public function webhook(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id')
            ?? $request->input('data.session_id')
            ?? null;

        if (! $sessionId) {
            return response()->json(['ok' => false, 'error' => 'missing session_id'], 422);
        }

        $order = Order::where('bakong_session_id', $sessionId)->first();

        if (! $order || $order->payment_status === 'paid') {
            return response()->json(['ok' => true]);
        }

        // Verify with Bakong before trusting the webhook payload.
        $bakong = app(BakongService::class);
        $details = $bakong->getCheckoutDetails($sessionId);

        if (($details['status'] ?? '') === 'PAID') {
            $order->update([
                'payment_status' => 'paid',
                'payment_confirmed_at' => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
