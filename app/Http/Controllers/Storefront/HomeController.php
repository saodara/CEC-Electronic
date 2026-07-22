<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $callback = function () {
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

            return [
                'categories' => $categories,
                'products' => Product::query()
                    ->where('is_active', true)
                    ->latest()
                    ->take(12)
                    ->get(),
            ];
        };

        // The file cache has no atomic lock between concurrent workers: two
        // simultaneous requests populating a cold key can race and corrupt the
        // write. unserialize() doesn't always throw on that corruption — it can
        // silently return the wrong shape — so validate before trusting it.
        try {
            $cached = Cache::get('catalog.home');
        } catch (\Throwable) {
            $cached = null;
        }

        $isValid = fn ($v) => is_array($v)
            && ($v['categories'] ?? null) instanceof Collection
            && ($v['products'] ?? null) instanceof Collection;

        if ($cached !== null && $isValid($cached)) {
            $data = $cached;
        } else {
            $data = $callback();

            try {
                Cache::put('catalog.home', $data, 300);
            } catch (\Throwable) {
                // Best-effort; if the write fails, the next request just recomputes too.
            }
        }

        ['categories' => $categories, 'products' => $products] = $data;

        return view('shop.home', compact('categories', 'products'));
    }
}
