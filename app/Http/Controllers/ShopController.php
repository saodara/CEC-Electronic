<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(private CartService $cartService)
    {
    }

    public function home()
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $products = Product::query()->latest()->take(12)->get();

        return view('shop.home', compact('categories','products'));
    }

    public function category($slug)
    {
        $categoryName = ucfirst(str_replace('-', ' ', $slug));
        $products = Product::where('category', $slug)->latest()->get();

        return view('shop.category', compact('categoryName','products'));
    }

    public function product($slug)
    {
        $product = Product::where('slug', $slug)->first();

        if (! $product) {
            abort(404);
        }

        return view('shop.product', compact('product'));
    }

    public function cart(Request $request)
    {
        $items = $this->cartService->items($request);
        $subtotal = $this->cartService->subtotal($request);

        return view('shop.cart', compact('items', 'subtotal'));
    }
}
