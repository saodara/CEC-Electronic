<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderStock
{
    /**
     * Stock leaves when an order is paid, or placed as cash on delivery, and comes back
     * if it's cancelled. stock_deducted_at stops any order counting twice, and means
     * orders from before this rule existed never add stock when cancelled.
     *
     * Stock can go below zero, since the shop sells out-of-stock items as pre-orders.
     */
    public function sync(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $locked = Order::query()->lockForUpdate()->with('items')->find($order->id);

            if (! $locked) {
                return;
            }

            $cancelled = $locked->status === 'cancelled';
            $sold = $locked->payment_status === 'paid' || $locked->payment_method === 'cash_on_delivery';

            if (! $locked->stock_deducted_at && $sold && ! $cancelled) {
                $this->move($locked, -1);
                $locked->forceFill(['stock_deducted_at' => now()])->saveQuietly();
            } elseif ($locked->stock_deducted_at && $cancelled) {
                $this->move($locked, 1);
                $locked->forceFill(['stock_deducted_at' => null])->saveQuietly();
            }
        });
    }

    /**
     * A direct update on purpose: saving a Product flushes the whole cache, which also
     * holds the Bakong rate limits. Shop pages catch up when their cached copy expires.
     */
    private function move(Order $order, int $direction): void
    {
        $order->items
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->sortKeys()
            ->each(fn ($items, $productId) => Product::query()
                ->whereKey($productId)
                ->increment('stock_quantity', $direction * $items->sum('quantity')));
    }
}
