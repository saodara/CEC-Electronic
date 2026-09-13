<?php

namespace Tests\Feature\Admin;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Note: Supplier does not use the HasFactory trait, so Supplier::factory()
// is unavailable. The corresponding Factory class is invoked directly.

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_visiting_supplier_index(): void
    {
        $this->get(route('admin.suppliers.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_user_is_redirected_to_login(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.suppliers.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_supplier_index(): void
    {
        $this->actingAsAdmin();
        Supplier::factory()->count(3)->create();

        $this->get(route('admin.suppliers.index'))
            ->assertOk()
            ->assertViewIs('admin.suppliers.index')
            ->assertViewHas('suppliers');
    }

    public function test_admin_can_view_create_form(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.suppliers.create'))
            ->assertOk()
            ->assertViewIs('admin.suppliers.create')
            ->assertViewHas('supplier');
    }

    public function test_admin_can_store_a_new_supplier(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('admin.suppliers.store'), [
            'name' => 'Acme Supplies',
        ]);

        $response->assertRedirect(route('admin.suppliers.index'));
        $response->assertSessionHas('status', 'Supplier created.');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Acme Supplies',
            'is_active' => false,
        ]);
    }

    public function test_store_fails_validation_without_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.suppliers.store'), [])
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_store_fails_validation_when_email_is_invalid(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.suppliers.store'), [
            'name' => 'Acme Supplies',
            'email' => 'not-an-email',
        ])->assertSessionHasErrors(['email']);
    }

    public function test_admin_can_view_edit_form(): void
    {
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create();

        $this->get(route('admin.suppliers.edit', $supplier))
            ->assertOk()
            ->assertViewIs('admin.suppliers.edit')
            ->assertViewHas('supplier', fn (Supplier $viewSupplier) => $viewSupplier->is($supplier));
    }

    public function test_admin_can_update_a_supplier(): void
    {
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create(['name' => 'Old Supplier']);

        $response = $this->put(route('admin.suppliers.update', $supplier), [
            'name' => 'New Supplier',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.suppliers.index'));
        $response->assertSessionHas('status', 'Supplier updated.');

        $supplier->refresh();
        $this->assertSame('New Supplier', $supplier->name);
        $this->assertTrue((bool) $supplier->is_active);
    }

    public function test_admin_can_delete_a_supplier(): void
    {
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create();

        $response = $this->delete(route('admin.suppliers.destroy', $supplier));

        $response->assertRedirect(route('admin.suppliers.index'));
        $response->assertSessionHas('status', 'Supplier deleted.');
        $this->assertModelMissing($supplier);
    }
}
