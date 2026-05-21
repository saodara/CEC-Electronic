<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class CartService
{
    public function items(Request $request): Collection
    {
        return CartItem::query()
            ->with('product')
            ->where($this->ownerColumn($request), $this->ownerValue($request))
            ->latest()
            ->get();
    }

    public function add(Request $request, Product $product, int $quantity = 1): CartItem
    {
        $ownerColumn = $this->ownerColumn($request);
        $ownerValue = $this->ownerValue($request);

        $cartItem = CartItem::firstOrNew([
            $ownerColumn => $ownerValue,
            'product_id' => $product->id,
        ]);

        $cartItem->unit_price = $product->price;
        $cartItem->quantity = (int) $cartItem->quantity + max(1, $quantity);
        $cartItem->save();

        return $cartItem;
    }

    public function updateQuantity(Request $request, CartItem $cartItem, int $quantity): void
    {
        $this->guardOwner($request, $cartItem);

        if ($quantity <= 0) {
            $cartItem->delete();
            return;
        }

        $cartItem->update(['quantity' => $quantity]);
    }

    public function remove(Request $request, CartItem $cartItem): void
    {
        $this->guardOwner($request, $cartItem);
        $cartItem->delete();
    }

    public function subtotal(Request $request): int
    {
        return $this->items($request)->sum(fn (CartItem $item) => $item->line_total);
    }

    public function clear(Request $request): void
    {
        CartItem::query()
            ->where($this->ownerColumn($request), $this->ownerValue($request))
            ->delete();
    }

    private function ownerColumn(Request $request): string
    {
        return $request->user() ? 'user_id' : 'session_id';
    }

    private function ownerValue(Request $request): int|string
    {
        return $request->user()?->id ?: $request->session()->getId();
    }

    private function guardOwner(Request $request, CartItem $cartItem): void
    {
        abort_unless(
            $cartItem->{$this->ownerColumn($request)} === $this->ownerValue($request),
            403
        );
    }
}
