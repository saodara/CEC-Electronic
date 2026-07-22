<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * How long to cache catalog reads (category/product/brand listings).
     * The DB is geographically far from the app, so caching read-heavy,
     * rarely-changing catalog data avoids paying that round-trip on every view.
     */
    private const CACHE_TTL = 300;

    /**
     * The file cache store has no atomic lock between concurrent workers, so
     * two simultaneous requests populating the same cold key can race and
     * corrupt the write. Unlike a locked store, the corruption doesn't always
     * throw — unserialize() can silently return the wrong shape (e.g. a
     * __PHP_Incomplete_Class or a string where an object was expected), which
     * then breaks far downstream in the view. So we validate the shape of
     * whatever comes back and treat anything unexpected as a miss.
     */
    private function cacheRemember(string $key, \Closure $callback, \Closure $isValid): mixed
    {
        try {
            $cached = Cache::get($key);
        } catch (\Throwable) {
            $cached = null;
        }

        if ($cached !== null && $isValid($cached)) {
            return $cached;
        }

        $fresh = $callback();

        try {
            Cache::put($key, $fresh, self::CACHE_TTL);
        } catch (\Throwable) {
            // Best-effort; if the write fails, the next request just recomputes too.
        }

        return $fresh;
    }

    public function category(string $slug): View
    {
        ['categoryName' => $categoryName, 'products' => $products] = $this->cacheRemember(
            "catalog.category.{$slug}",
            function () use ($slug) {
                $category = Category::where('slug', $slug)->first();

                return [
                    'categoryName' => $category?->name ?: ucfirst(str_replace('-', ' ', $slug)),
                    'products' => Product::query()
                        ->where('is_active', true)
                        ->when($category, fn ($query) => $query->where('category_id', $category->id))
                        ->when(! $category, fn ($query) => $query->where('category', $slug))
                        ->latest()
                        ->get(),
                ];
            },
            fn ($v) => is_array($v) && isset($v['categoryName']) && is_string($v['categoryName'])
                && ($v['products'] ?? null) instanceof Collection
        );

        return view('shop.category', compact('categoryName', 'products'));
    }

    public function product(string $slug): View
    {
        $product = $this->cacheRemember(
            "catalog.product.{$slug}",
            fn () => Product::where('slug', $slug)->firstOrFail(),
            fn ($v) => $v instanceof Product
        );

        return view('shop.product', compact('product'));
    }

    public function search(Request $request): View
    {
        $query = trim((string) $request->query('q'));
        $categoryName = $query ? 'Search: ' . $query : 'Search';

        $products = $this->cacheRemember(
            'catalog.search.' . md5($query),
            fn () => Product::query()
                ->where('is_active', true)
                ->when($query, function ($builder) use ($query) {
                    $builder->where(function ($inner) use ($query) {
                        $inner->where('name', 'like', "%{$query}%")
                            ->orWhere('sku', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    });
                })
                ->latest()
                ->get(),
            fn ($v) => $v instanceof Collection
        );

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
        $products = $this->cacheRemember(
            "catalog.brand.{$slug}",
            fn () => Product::query()
                ->where('is_active', true)
                ->where(function ($query) use ($brand) {
                    $query->where('name', 'like', '%' . $brand['name'] . '%')
                        ->orWhere('description', 'like', '%' . $brand['name'] . '%');
                })
                ->latest()
                ->get(),
            fn ($v) => $v instanceof Collection
        );

        return view('shop.category', compact('categoryName', 'products'));
    }

    private function brandCollection(): Collection
    {
        return $this->cacheRemember(
            'catalog.brands',
            function () {
                // Fetch active products once and match brands in memory instead of
                // running one COUNT query per brand (was N+1, one round trip per brand).
                $products = Product::query()
                    ->where('is_active', true)
                    ->get(['name', 'description']);

                return collect(config('brands'))
                    ->map(function (array $brand) use ($products) {
                        $count = $products->filter(
                            fn (Product $product) => Str::contains($product->name, $brand['name'], ignoreCase: true)
                                || Str::contains((string) $product->description, $brand['name'], ignoreCase: true)
                        )->count();

                        return $brand + [
                            'initials' => Str::of($brand['name'])->substr(0, 2)->upper()->toString(),
                            'products_count' => $count,
                        ];
                    })
                    ->sortBy('name')
                    ->values();
            },
            fn ($v) => $v instanceof Collection
        );
    }
}
