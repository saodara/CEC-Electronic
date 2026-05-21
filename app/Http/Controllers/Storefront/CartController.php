<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cartService)
    {
    }

    public function index(Request $request): View
    {
        $items = $this->cartService->items($request);
        $subtotal = $this->cartService->subtotal($request);

        return view('shop.cart', compact('items', 'subtotal'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $request->merge([
            'quantity' => $this->normalizeQuantity($request->input('quantity', 1), 1),
        ]);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $this->cartService->add($request, $product, $data['quantity'] ?? 1);

        return back()->with('status', 'Product added to cart.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        $request->merge([
            'quantity' => $this->normalizeQuantity($request->input('quantity', 1), 0),
        ]);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $this->cartService->updateQuantity($request, $cartItem, $data['quantity']);

        return back()->with('status', 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->cartService->remove($request, $cartItem);

        return back()->with('status', 'Item removed.');
    }

    private function normalizeQuantity(mixed $value, int $minimum): int
    {
        if (! is_numeric($value)) {
            return $minimum;
        }

        return max($minimum, (int) floor((float) $value));
    }
}
