<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
     * Products don't have dedicated processor/RAM/storage columns, so these
     * facets are matched against the name/description text.
     */
    private const PROCESSOR_KEYWORDS = [
        'Intel Core' => ['intel', 'core i'],
        'AMD Ryzen' => ['ryzen', 'amd'],
        'Apple M series' => ['apple', 'macbook', 'imac', 'm1', 'm2', 'm3', 'm4'],
    ];

    private const PRICE_RANGES = [
        'Under $500' => [null, 499.99],
        '$500 - $999' => [500, 999.99],
        '$1,000 - $1,499' => [1000, 1499.99],
        '$1,500+' => [1500, null],
    ];

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

    public function category(string $slug, Request $request): View
    {
        $filterKey = $this->filterCacheKey($request);

        ['categoryName' => $categoryName, 'products' => $products] = $this->cacheRemember(
            "catalog.category.{$slug}.{$filterKey}",
            function () use ($slug, $request) {
                $category = Category::where('slug', $slug)->first();
                $categorySlugs = $request->query('category');

                $query = Product::query()->where('is_active', true);

                if ($categorySlugs !== null) {
                    // Checkbox filters were submitted; they fully control which categories show.
                    $query->whereHas('categoryRelation', fn ($q) => $q->whereIn('slug', (array) $categorySlugs));
                } elseif ($category) {
                    $query->where('category_id', $category->id);
                } else {
                    // Legacy fallback for products still using the plain `category` string column.
                    $query->where('category', $slug);
                }

                $this->applyBrandFilter($query, $request);
                $this->applyFacetFilters($query, $request);

                return [
                    'categoryName' => $category?->name ?: ucfirst(str_replace('-', ' ', $slug)),
                    'products' => $query->latest()->get(),
                ];
            },
            fn ($v) => is_array($v) && isset($v['categoryName']) && is_string($v['categoryName'])
                && ($v['products'] ?? null) instanceof Collection
        );

        $categories = $this->activeCategories();
        $brands = $this->activeBrands();
        $selectedCategories = $request->query('category', [$slug]);
        $selectedBrands = (array) $request->query('brand', []);

        return view('shop.category', compact('categoryName', 'products', 'categories', 'brands', 'selectedCategories', 'selectedBrands'));
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
        $filterKey = $this->filterCacheKey($request);

        $products = $this->cacheRemember(
            'catalog.search.' . md5($query) . '.' . $filterKey,
            function () use ($query, $request) {
                $builder = Product::query()
                    ->where('is_active', true)
                    ->when($query, function ($builder) use ($query) {
                        $builder->where(function ($inner) use ($query) {
                            $inner->where('name', 'like', "%{$query}%")
                                ->orWhere('sku', 'like', "%{$query}%")
                                ->orWhere('description', 'like', "%{$query}%");
                        });
                    });

                $this->applyCategoryFilter($builder, $request);
                $this->applyBrandFilter($builder, $request);
                $this->applyFacetFilters($builder, $request);

                return $builder->latest()->get();
            },
            fn ($v) => $v instanceof Collection
        );

        $categories = $this->activeCategories();
        $brands = $this->activeBrands();
        $selectedCategories = $request->query('category', []);
        $selectedBrands = (array) $request->query('brand', []);

        return view('shop.category', compact('categoryName', 'products', 'categories', 'brands', 'selectedCategories', 'selectedBrands'));
    }

    public function brands(): View
    {
        $brands = $this->cacheRemember(
            'catalog.brands.list',
            fn () => Brand::query()
                ->active()
                ->ordered()
                ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
                ->get(),
            fn ($v) => $v instanceof Collection
        );

        return view('shop.brands', compact('brands'));
    }

    public function brand(string $slug, Request $request): View
    {
        $filterKey = $this->filterCacheKey($request);

        ['brandName' => $brandName, 'products' => $products] = $this->cacheRemember(
            "catalog.brand.{$slug}.{$filterKey}",
            function () use ($slug, $request) {
                $brand = Brand::query()->active()->where('slug', $slug)->first();

                if (! $brand) {
                    return ['brandName' => null, 'products' => new Collection()];
                }

                $builder = Product::query()->where('is_active', true);

                if ($request->query('brand') !== null) {
                    // Sidebar brand checkboxes were submitted; they fully control which brands show.
                    $this->applyBrandFilter($builder, $request);
                } else {
                    $builder->where('brand_id', $brand->id);
                }

                $this->applyCategoryFilter($builder, $request);
                $this->applyFacetFilters($builder, $request);

                return ['brandName' => $brand->name, 'products' => $builder->latest()->get()];
            },
            fn ($v) => is_array($v) && array_key_exists('brandName', $v)
                && ($v['brandName'] === null || is_string($v['brandName']))
                && ($v['products'] ?? null) instanceof Collection
        );

        abort_if($brandName === null, 404);

        $categoryName = $brandName . ' Products';
        $categories = $this->activeCategories();
        $brands = $this->activeBrands();
        $selectedCategories = $request->query('category', []);
        $selectedBrands = (array) $request->query('brand', [$slug]);

        return view('shop.category', compact('categoryName', 'products', 'categories', 'brands', 'selectedCategories', 'selectedBrands'));
    }

    private function applyCategoryFilter(Builder $query, Request $request): void
    {
        $categorySlugs = (array) $request->query('category', []);

        if ($categorySlugs) {
            $query->whereHas('categoryRelation', fn ($q) => $q->whereIn('slug', $categorySlugs));
        }
    }

    private function applyBrandFilter(Builder $query, Request $request): void
    {
        $brandSlugs = (array) $request->query('brand', []);

        if ($brandSlugs) {
            $query->whereHas('brand', fn ($q) => $q->whereIn('slug', $brandSlugs));
        }
    }

    private function applyFacetFilters(Builder $query, Request $request): void
    {
        $processors = (array) $request->query('processor', []);
        $ramSizes = (array) $request->query('ram', []);
        $storageSizes = (array) $request->query('storage', []);
        $priceRanges = (array) $request->query('price', []);

        if ($processors) {
            $query->where(function ($outer) use ($processors) {
                foreach ($processors as $label) {
                    foreach (self::PROCESSOR_KEYWORDS[$label] ?? [] as $keyword) {
                        $outer->orWhere('name', 'like', "%{$keyword}%")
                            ->orWhere('description', 'like', "%{$keyword}%");
                    }
                }
            });
        }

        if ($ramSizes) {
            $query->where(function ($outer) use ($ramSizes) {
                foreach ($ramSizes as $size) {
                    $outer->orWhere('name', 'like', "%{$size}%")
                        ->orWhere('description', 'like', "%{$size}%");
                }
            });
        }

        if ($storageSizes) {
            $query->where(function ($outer) use ($storageSizes) {
                foreach ($storageSizes as $size) {
                    $outer->orWhere('name', 'like', "%{$size}%")
                        ->orWhere('description', 'like', "%{$size}%");
                }
            });
        }

        if ($priceRanges) {
            $query->where(function ($outer) use ($priceRanges) {
                foreach ($priceRanges as $range) {
                    [$min, $max] = self::PRICE_RANGES[$range] ?? [null, null];
                    $outer->orWhere(function ($bounded) use ($min, $max) {
                        if ($min !== null) {
                            $bounded->where('price', '>=', $min);
                        }
                        if ($max !== null) {
                            $bounded->where('price', '<=', $max);
                        }
                    });
                }
            });
        }
    }

    /**
     * Distinguishes cached results across different filter combinations so
     * one visitor's applied filters can't be served back to another visitor
     * requesting the same category/search/brand with different filters.
     */
    private function filterCacheKey(Request $request): string
    {
        $relevant = collect(['processor', 'ram', 'storage', 'price', 'category', 'brand'])
            ->mapWithKeys(fn ($key) => [
                $key => collect((array) $request->query($key, []))->sort()->values()->all(),
            ])
            ->all();

        return md5(json_encode($relevant));
    }

    private function activeCategories(): Collection
    {
        return $this->cacheRemember(
            'catalog.categories.active',
            fn () => Category::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug']),
            fn ($v) => $v instanceof Collection
        );
    }

    private function activeBrands(): Collection
    {
        return $this->cacheRemember(
            'catalog.brands.active',
            fn () => Brand::query()->active()->ordered()->get(['id', 'name', 'slug']),
            fn ($v) => $v instanceof Collection
        );
    }
}
