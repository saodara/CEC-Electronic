<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
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

    public function store(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $request->merge([
            'quantity' => $this->normalizeQuantity($request->input('quantity', 1), 1),
        ]);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $this->cartService->add($request, $product, $data['quantity'] ?? 1);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Product added to cart.',
                'product' => $product->name,
                'count' => $this->cartService->count($request),
            ]);
        }

        return back()->with('status', 'Product added to cart.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $request->merge([
            'quantity' => $this->normalizeQuantity($request->input('quantity', 1), 0),
        ]);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $this->cartService->updateQuantity($request, $cartItem, $data['quantity']);

        if ($request->wantsJson()) {
            $removed = $data['quantity'] <= 0;

            return response()->json([
                'removed' => $removed,
                'item_id' => $cartItem->id,
                'quantity' => $removed ? 0 : $cartItem->quantity,
                'line_total' => $removed ? null : number_format($cartItem->line_total, 2),
                'subtotal' => number_format($this->cartService->subtotal($request), 2),
                'count' => $this->cartService->count($request),
            ]);
        }

        return back()->with('status', 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $itemId = $cartItem->id;

        $this->cartService->remove($request, $cartItem);

        if ($request->wantsJson()) {
            return response()->json([
                'removed' => true,
                'item_id' => $itemId,
                'subtotal' => number_format($this->cartService->subtotal($request), 2),
                'count' => $this->cartService->count($request),
            ]);
        }

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
