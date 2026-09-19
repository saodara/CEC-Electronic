<?php

namespace Tests\Feature\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

// Note: Category does not use the HasFactory trait, so Category::factory() is
// unavailable. The corresponding Factory class is invoked directly instead
// (same workaround already used elsewhere in this suite, e.g.
// tests/Feature/Admin/CustomerManagementTest.php for Order).
class CatalogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_category_page_shows_only_active_products_for_a_real_category_row(): void
    {
        $category = Category::factory()->create(['name' => 'Laptops', 'slug' => 'laptops']);
        $other = Category::factory()->create(['name' => 'Phones', 'slug' => 'phones']);

        $activeInCategory = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
        ]);
        Product::factory()->create([
            'category_id' => $other->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('shop.category', 'laptops'));

        $response->assertOk();
        $response->assertViewIs('shop.category');
        $response->assertViewHas('categoryName', 'Laptops');
        $products = $response->viewData('products');

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains('id', $activeInCategory->id));
    }

    public function test_category_page_falls_back_to_legacy_string_column_when_no_category_row_matches(): void
    {
        $product = Product::factory()->create([
            'category' => 'laptops',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'category' => 'phones',
            'is_active' => true,
        ]);

        $response = $this->get(route('shop.category', 'laptops'));

        $response->assertOk();
        $response->assertViewIs('shop.category');
        $response->assertViewHas('categoryName', 'Laptops');
        $products = $response->viewData('products');

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains('id', $product->id));
    }

    public function test_category_page_humanizes_multi_word_slug_for_legacy_fallback(): void
    {
        Product::factory()->create([
            'category' => 'gaming-accessories',
            'is_active' => true,
        ]);

        $response = $this->get(route('shop.category', 'gaming-accessories'));

        $response->assertOk();
        $response->assertViewHas('categoryName', 'Gaming accessories');
    }

    public function test_product_page_shows_existing_product_by_slug(): void
    {
        $product = Product::factory()->create(['slug' => 'my-cool-product']);

        $response = $this->get(route('shop.product', 'my-cool-product'));

        $response->assertOk();
        $response->assertViewIs('shop.product');
        $response->assertViewHas('product', function ($viewProduct) use ($product) {
            return $viewProduct->id === $product->id;
        });
    }

    public function test_product_page_404s_for_unknown_slug(): void
    {
        $response = $this->get(route('shop.product', 'does-not-exist'));

        $response->assertNotFound();
    }

    public function test_search_matches_by_partial_case_insensitive_name(): void
    {
        $match = Product::factory()->create(['name' => 'Wireless Mouse', 'is_active' => true]);
        Product::factory()->create(['name' => 'Keyboard', 'is_active' => true]);

        $response = $this->get(route('shop.search', ['q' => 'mouse']));

        $response->assertOk();
        $response->assertViewIs('shop.category');
        $response->assertViewHas('categoryName', 'Search: mouse');

        $products = $response->viewData('products');
        $this->assertCount(1, $products);
        $this->assertTrue($products->contains('id', $match->id));
    }

    public function test_search_matches_by_sku(): void
    {
        $match = Product::factory()->create(['sku' => 'SKU-12345', 'is_active' => true]);

        $response = $this->get(route('shop.search', ['q' => '12345']));

        $response->assertOk();
        $products = $response->viewData('products');
        $this->assertTrue($products->contains('id', $match->id));
    }

    public function test_search_matches_by_description(): void
    {
        $match = Product::factory()->create(['description' => 'A great budget laptop for students', 'is_active' => true]);

        $response = $this->get(route('shop.search', ['q' => 'budget laptop']));

        $response->assertOk();
        $products = $response->viewData('products');
        $this->assertTrue($products->contains('id', $match->id));
    }

    public function test_search_with_empty_query_returns_all_active_products(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $response = $this->get(route('shop.search'));

        $response->assertOk();
        $response->assertViewHas('categoryName', 'Search');

        $products = $response->viewData('products');
        $this->assertCount(3, $products);
    }

    public function test_brands_page_lists_active_brands_with_active_product_counts(): void
    {
        $asus = Brand::factory()->create(['name' => 'Asus Test', 'slug' => 'asus-test']);
        Brand::factory()->create(['is_active' => false, 'slug' => 'hidden-brand']);
        Product::factory()->count(2)->create(['brand_id' => $asus->id, 'is_active' => true]);
        Product::factory()->create(['brand_id' => $asus->id, 'is_active' => false]);

        $response = $this->get(route('shop.brands'));

        $response->assertOk();
        $response->assertViewIs('shop.brands');

        $brands = $response->viewData('brands');
        $found = $brands->firstWhere('slug', 'asus-test');

        $this->assertNotNull($found);
        $this->assertSame(2, $found->products_count);
        $this->assertNull($brands->firstWhere('slug', 'hidden-brand'));
    }

    public function test_brand_page_shows_only_products_assigned_to_that_brand(): void
    {
        $dell = Brand::factory()->create(['name' => 'Dell Test', 'slug' => 'dell-test']);
        $match = Product::factory()->create(['brand_id' => $dell->id, 'is_active' => true]);
        // Mentions the brand but is not assigned to it: must not appear.
        Product::factory()->create(['name' => 'Dell Test charger', 'brand_id' => null, 'is_active' => true]);
        Product::factory()->create(['brand_id' => $dell->id, 'is_active' => false]);

        $response = $this->get(route('shop.brand', 'dell-test'));

        $response->assertOk();
        $response->assertViewIs('shop.category');
        $response->assertViewHas('categoryName', 'Dell Test Products');

        $products = $response->viewData('products');
        $this->assertCount(1, $products);
        $this->assertTrue($products->contains('id', $match->id));
    }

    public function test_brand_page_404s_for_unknown_or_inactive_brand_slug(): void
    {
        Brand::factory()->create(['slug' => 'gone', 'is_active' => false]);

        $this->get(route('shop.brand', 'not-a-real-brand'))->assertNotFound();
        $this->get(route('shop.brand', 'gone'))->assertNotFound();
    }

    public function test_category_page_can_be_filtered_by_brand_checkboxes(): void
    {
        $category = Category::factory()->create(['name' => 'Laptops', 'slug' => 'laptops']);
        $dell = Brand::factory()->create(['slug' => 'dell-test']);
        $hp = Brand::factory()->create(['slug' => 'hp-test']);

        $dellProduct = Product::factory()->create(['category_id' => $category->id, 'brand_id' => $dell->id]);
        Product::factory()->create(['category_id' => $category->id, 'brand_id' => $hp->id]);
        Product::factory()->create(['category_id' => $category->id, 'brand_id' => null]);

        $response = $this->get(route('shop.category', ['slug' => 'laptops', 'brand' => ['dell-test']]));

        $response->assertOk();
        $products = $response->viewData('products');
        $this->assertCount(1, $products);
        $this->assertTrue($products->contains('id', $dellProduct->id));
        $response->assertViewHas('selectedBrands', ['dell-test']);
    }

    public function test_search_can_be_filtered_by_brand(): void
    {
        $dell = Brand::factory()->create(['slug' => 'dell-test']);
        $hp = Brand::factory()->create(['slug' => 'hp-test']);
        $match = Product::factory()->create(['name' => 'Office laptop A', 'brand_id' => $dell->id]);
        Product::factory()->create(['name' => 'Office laptop B', 'brand_id' => $hp->id]);

        $response = $this->get(route('shop.search', ['q' => 'Office', 'brand' => ['dell-test']]));

        $products = $response->viewData('products');
        $this->assertCount(1, $products);
        $this->assertTrue($products->contains('id', $match->id));
    }

    public function test_brand_filter_results_are_cached_separately_per_selection(): void
    {
        $category = Category::factory()->create(['slug' => 'laptops']);
        $dell = Brand::factory()->create(['slug' => 'dell-test']);
        $hp = Brand::factory()->create(['slug' => 'hp-test']);
        Product::factory()->create(['category_id' => $category->id, 'brand_id' => $dell->id]);
        Product::factory()->create(['category_id' => $category->id, 'brand_id' => $hp->id]);

        $this->get(route('shop.category', ['slug' => 'laptops', 'brand' => ['dell-test']]))
            ->assertViewHas('products', fn ($p) => $p->count() === 1);
        $this->get(route('shop.category', ['slug' => 'laptops', 'brand' => ['hp-test']]))
            ->assertViewHas('products', fn ($p) => $p->count() === 1 && $p->first()->brand_id === $hp->id);
        $this->get(route('shop.category', 'laptops'))
            ->assertViewHas('products', fn ($p) => $p->count() === 2);
    }
}
