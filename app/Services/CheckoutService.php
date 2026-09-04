<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(private CartService $cartService)
    {
    }

    public function createOrder(Request $request, array $data): Order
    {
        $items = $this->cartService->items($request);
        abort_if($items->isEmpty(), 422, 'Cart is empty.');

        return DB::transaction(function () use ($request, $data, $items) {
            $subtotal = $items->sum(fn (CartItem $item) => $item->line_total);
            $shippingTotal = 0;
            $paymentMethod = $data['payment_method'] ?? 'bakong';

            $order = Order::create([
                'order_number' => $this->orderNumber(),
                'user_id' => $request->user()?->id,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? $request->user()?->email,
                'customer_phone' => $data['customer_phone'],
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_confirmed_at' => null,
                'admin_payment_seen_at' => null,
                'payment_method' => $paymentMethod,
                'shipping_method' => $data['shipping_method'] ?? 'standard',
                'subtotal' => $subtotal,
                'shipping_total' => $shippingTotal,
                'discount_total' => 0,
                'grand_total' => $subtotal + $shippingTotal,
                'shipping_address' => [
                    'address_line_1' => $data['address_line_1'],
                    'address_line_2' => $data['address_line_2'] ?? null,
                    'city' => $data['city'],
                    'province' => $data['province'] ?? null,
                    'country' => $data['country'] ?? 'Cambodia',
                ],
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?: 'Deleted product',
                    'sku' => $item->product?->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ]);
            }

            $this->cartService->clear($request);

            return $order;
        });
    }

    private function orderNumber(): string
    {
        do {
            $number = 'EH-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
