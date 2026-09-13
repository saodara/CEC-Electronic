<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

// Note: Category does not use the HasFactory trait, so Category::factory() is
// unavailable. The corresponding Factory class is invoked directly instead
// (same workaround already used elsewhere in this suite, e.g.
// tests/Feature/Admin/CustomerManagementTest.php for Order).
class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_home_page_returns_active_categories_ordered_by_sort_order(): void
    {
        $second = Category::factory()->create(['name' => 'Phones', 'slug' => 'phones', 'sort_order' => 2, 'is_active' => true]);
        $first = Category::factory()->create(['name' => 'Laptops', 'slug' => 'laptops', 'sort_order' => 1, 'is_active' => true]);
        Category::factory()->create(['name' => 'Hidden', 'slug' => 'hidden', 'sort_order' => 0, 'is_active' => false]);

        $response = $this->get(route('shop.home'));

        $response->assertOk();
        $response->assertViewIs('shop.home');

        $categories = $response->viewData('categories');

        $this->assertCount(2, $categories);
        $this->assertSame($first->id, $categories->first()->id);
        $this->assertSame($second->id, $categories->last()->id);
    }

    public function test_home_page_falls_back_to_hardcoded_categories_when_none_exist(): void
    {
        $this->assertSame(0, Category::count());

        $response = $this->get(route('shop.home'));

        $response->assertOk();
        $categories = $response->viewData('categories');

        $this->assertCount(3, $categories);
        $this->assertSame(['laptops', 'phones', 'accessories'], $categories->pluck('slug')->all());
        $this->assertSame(['Laptops', 'Phones', 'Accessories'], $categories->pluck('name')->all());
    }

    public function test_home_page_shows_latest_12_active_products_and_excludes_inactive(): void
    {
        $activeProducts = collect();

        // Set explicit, strictly increasing created_at timestamps so "latest()"
        // ordering (which only sorts by created_at, with no secondary tiebreaker)
        // is deterministic instead of relying on insertion-order ties.
        for ($i = 0; $i < 13; $i++) {
            $activeProducts->push(Product::factory()->create([
                'is_active' => true,
                'created_at' => now()->subMinutes(20 - $i),
            ]));
        }

        $inactive = Product::factory()->create([
            'is_active' => false,
            'created_at' => now(),
        ]);

        $response = $this->get(route('shop.home'));

        $response->assertOk();
        $products = $response->viewData('products');

        $this->assertCount(12, $products);
        $this->assertFalse($products->contains('id', $inactive->id));

        // "latest" (by created_at/id desc) 12 of the 13 active products - the very
        // first one created should be excluded, the rest should be present.
        $oldestActive = $activeProducts->first();
        $this->assertFalse($products->contains('id', $oldestActive->id));
    }
}
