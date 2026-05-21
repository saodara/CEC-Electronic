<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($categories->isEmpty()) {
            $categories = collect([
                (object) ['slug' => 'laptops', 'name' => 'Laptops'],
                (object) ['slug' => 'phones', 'name' => 'Phones'],
                (object) ['slug' => 'accessories', 'name' => 'Accessories'],
            ]);
        }

        $products = Product::query()
            ->where('is_active', true)
            ->latest()
            ->take(12)
            ->get();

        return view('shop.home', compact('categories', 'products'));
    }
}
