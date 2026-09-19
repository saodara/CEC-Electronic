<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.brands.index'))->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_user_is_redirected_to_login(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(route('admin.brands.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_index_create_and_edit_pages(): void
    {
        $this->actingAsAdmin();
        $brand = Brand::factory()->create();

        $this->get(route('admin.brands.index'))->assertOk()->assertViewIs('admin.brands.index');
        $this->get(route('admin.brands.create'))->assertOk()->assertViewIs('admin.brands.create');
        $this->get(route('admin.brands.edit', $brand))->assertOk()->assertViewIs('admin.brands.edit');
    }

    public function test_admin_can_create_brand_with_generated_unique_slug_and_logo(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        Brand::factory()->create(['name' => 'Acme', 'slug' => 'acme']);

        $this->post(route('admin.brands.store'), [
            'name' => 'Acme',
            'is_active' => '1',
            'sort_order' => 3,
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect(route('admin.brands.index'));

        $brand = Brand::where('slug', 'acme-2')->firstOrFail();
        $this->assertTrue($brand->is_active);
        $this->assertSame(3, $brand->sort_order);
        Storage::disk('public')->assertExists($brand->logo);
    }

    public function test_name_is_required(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.brands.store'), ['name' => ''])->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_brand_and_hide_it(): void
    {
        $this->actingAsAdmin();
        $brand = Brand::factory()->create(['name' => 'Old', 'slug' => 'old']);

        $this->put(route('admin.brands.update', $brand), ['name' => 'New'])
            ->assertRedirect(route('admin.brands.index'));

        $brand->refresh();
        $this->assertSame('New', $brand->name);
        $this->assertSame('new', $brand->slug);
        $this->assertFalse($brand->is_active);
    }

    public function test_deleting_a_brand_keeps_its_products(): void
    {
        $this->actingAsAdmin();
        $brand = Brand::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id]);

        $this->delete(route('admin.brands.destroy', $brand))->assertRedirect(route('admin.brands.index'));

        $this->assertModelMissing($brand);
        $this->assertNull($product->fresh()->brand_id);
    }

    public function test_product_can_be_assigned_a_brand_and_list_filtered_by_it(): void
    {
        $this->actingAsAdmin();
        $dell = Brand::factory()->create();
        $hp = Brand::factory()->create();
        $dellProduct = Product::factory()->create(['brand_id' => $dell->id]);
        Product::factory()->create(['brand_id' => $hp->id]);

        $this->get(route('admin.products.index', ['brand' => $dell->id]))
            ->assertOk()
            ->assertViewHas('products', fn ($p) => $p->count() === 1 && $p->first()->id === $dellProduct->id);

        $this->post(route('admin.products.store'), [
            'name' => 'Branded thing',
            'price' => 10,
            'brand_id' => $hp->id,
        ])->assertRedirect(route('admin.products.index'));

        $this->assertSame($hp->id, Product::where('name', 'Branded thing')->value('brand_id'));
    }

    public function test_product_rejects_unknown_brand(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.products.store'), ['name' => 'X', 'price' => 10, 'brand_id' => 9999])
            ->assertSessionHasErrors('brand_id');
    }
}
