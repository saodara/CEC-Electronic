<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Note: Category (and several other Admin models) does not use the
// HasFactory trait, so Category::factory() is unavailable. The
// corresponding Factory class is invoked directly instead.

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_visiting_category_index(): void
    {
        $this->get(route('admin.categories.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_user_is_redirected_to_login(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.categories.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_category_index(): void
    {
        $this->actingAsAdmin();
        Category::factory()->count(3)->create();

        $this->get(route('admin.categories.index'))
            ->assertOk()
            ->assertViewIs('admin.categories.index')
            ->assertViewHas('categories');
    }

    public function test_admin_can_view_create_form(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.categories.create'))
            ->assertOk()
            ->assertViewIs('admin.categories.create')
            ->assertViewHas('category')
            ->assertViewHas('parents');
    }

    public function test_admin_can_store_a_new_category(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('admin.categories.store'), [
            'name' => 'Home Appliances',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('status', 'Category created.');

        $this->assertDatabaseHas('categories', [
            'name' => 'Home Appliances',
            'slug' => 'home-appliances',
        ]);
    }

    public function test_store_generates_a_unique_slug_when_name_already_exists(): void
    {
        $this->actingAsAdmin();
        Category::factory()->create(['name' => 'Home Appliances', 'slug' => 'home-appliances']);

        $this->post(route('admin.categories.store'), [
            'name' => 'Home Appliances',
        ]);

        $this->assertDatabaseHas('categories', ['slug' => 'home-appliances-2']);
    }

    public function test_store_fails_validation_without_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.categories.store'), [])
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_admin_can_view_edit_form(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $this->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertViewIs('admin.categories.edit')
            ->assertViewHas('category', fn (Category $viewCategory) => $viewCategory->is($category));
    }

    public function test_admin_can_update_a_category(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

        $response = $this->put(route('admin.categories.update', $category), [
            'name' => 'Old Name',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('status', 'Category updated.');

        $category->refresh();
        $this->assertSame('old-name', $category->slug);
    }

    public function test_update_regenerates_slug_when_name_changes(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

        $this->put(route('admin.categories.update', $category), [
            'name' => 'New Name',
        ]);

        $this->assertSame('new-name', $category->refresh()->slug);
    }

    public function test_admin_can_delete_a_category(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $response = $this->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('status', 'Category deleted.');
        $this->assertModelMissing($category);
    }
}
