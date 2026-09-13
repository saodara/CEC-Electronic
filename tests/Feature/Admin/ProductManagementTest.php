<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_visiting_product_index(): void
    {
        $this->get(route('admin.products.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_user_is_redirected_to_login(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_product_index(): void
    {
        $this->actingAsAdmin();
        Product::factory()->count(3)->create();

        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertViewIs('admin.products.index')
            ->assertViewHas('products');
    }

    public function test_admin_can_view_create_form(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.products.create'))
            ->assertOk()
            ->assertViewIs('admin.products.create');
    }

    public function test_admin_can_store_a_new_product(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('admin.products.store'), [
            'name' => 'Wireless Mouse',
            'price' => 19.99,
            'stock_quantity' => 10,
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('status', 'Product created.');

        $this->assertDatabaseHas('products', [
            'name' => 'Wireless Mouse',
            'slug' => 'wireless-mouse',
            'price' => 19.99,
        ]);
    }

    public function test_store_generates_a_unique_slug_when_name_already_exists(): void
    {
        $this->actingAsAdmin();
        Product::factory()->create(['name' => 'Wireless Mouse', 'slug' => 'wireless-mouse']);

        $this->post(route('admin.products.store'), [
            'name' => 'Wireless Mouse',
            'price' => 24.99,
        ]);

        $this->assertDatabaseHas('products', ['slug' => 'wireless-mouse-2']);
    }

    public function test_store_fails_validation_without_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.products.store'), [])
            ->assertSessionHasErrors(['name', 'price']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_store_fails_validation_when_price_is_not_positive(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.products.store'), [
            'name' => 'Broken Product',
            'price' => 0,
        ])->assertSessionHasErrors(['price']);
    }

    public function test_admin_can_view_edit_form(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();

        $this->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertViewIs('admin.products.edit')
            ->assertViewHas('product', fn (Product $viewProduct) => $viewProduct->is($product));
    }

    public function test_admin_can_update_a_product(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create(['name' => 'Old Name', 'slug' => 'old-name', 'price' => 10]);

        $response = $this->put(route('admin.products.update', $product), [
            'name' => 'Old Name',
            'price' => 50,
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('status', 'Product updated.');

        $product->refresh();
        $this->assertSame('old-name', $product->slug);
        $this->assertEquals(50, $product->price);
    }

    public function test_update_regenerates_slug_when_name_changes(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

        $this->put(route('admin.products.update', $product), [
            'name' => 'New Name',
            'price' => 10,
        ]);

        $this->assertSame('new-name', $product->refresh()->slug);
    }

    public function test_admin_can_delete_a_product(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();

        $response = $this->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('status', 'Product deleted.');
        $this->assertModelMissing($product);
    }
}
