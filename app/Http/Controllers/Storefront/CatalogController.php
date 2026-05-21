<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function category(string $slug): View
    {
        $category = Category::where('slug', $slug)->first();
        $categoryName = $category?->name ?: ucfirst(str_replace('-', ' ', $slug));

        $products = Product::query()
            ->where('is_active', true)
            ->when($category, fn ($query) => $query->where('category_id', $category->id))
            ->when(! $category, fn ($query) => $query->where('category', $slug))
            ->latest()
            ->get();

        return view('shop.category', compact('categoryName', 'products'));
    }

    public function product(string $slug): View
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        return view('shop.product', compact('product'));
    }

    public function search(Request $request): View
    {
        $query = trim((string) $request->query('q'));
        $categoryName = $query ? 'Search: ' . $query : 'Search';

        $products = Product::query()
            ->where('is_active', true)
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($inner) use ($query) {
                    $inner->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                });
            })
            ->latest()
            ->get();

        return view('shop.category', compact('categoryName', 'products'));
    }

    public function brands(): View
    {
        $brands = $this->brandCollection();

        return view('shop.brands', compact('brands'));
    }

    public function brand(string $slug): View
    {
        $brand = $this->brandCollection()->firstWhere('slug', $slug);

        abort_if(! $brand, 404);

        $categoryName = $brand['name'] . ' Products';
        $products = Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($brand) {
                $query->where('name', 'like', '%' . $brand['name'] . '%')
                    ->orWhere('description', 'like', '%' . $brand['name'] . '%');
            })
            ->latest()
            ->get();

        return view('shop.category', compact('categoryName', 'products'));
    }

    private function brandCollection(): Collection
    {
        return collect(config('brands'))
            ->map(function (array $brand) {
                $count = Product::query()
                    ->where('is_active', true)
                    ->where(function ($query) use ($brand) {
                        $query->where('name', 'like', '%' . $brand['name'] . '%')
                            ->orWhere('description', 'like', '%' . $brand['name'] . '%');
                    })
                    ->count();

                return $brand + [
                    'initials' => Str::of($brand['name'])->substr(0, 2)->upper()->toString(),
                    'products_count' => $count,
                ];
            })
            ->sortBy('name')
            ->values();
    }
}
