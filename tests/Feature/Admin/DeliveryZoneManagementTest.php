<?php

namespace Tests\Feature\Admin;

use App\Models\DeliveryZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Note: DeliveryZone does not use the HasFactory trait, so
// DeliveryZone::factory() is unavailable. The corresponding Factory
// class is invoked directly instead.

class DeliveryZoneManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_visiting_delivery_zone_index(): void
    {
        $this->get(route('admin.delivery-zones.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_user_is_redirected_to_login(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.delivery-zones.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_delivery_zone_index(): void
    {
        $this->actingAsAdmin();
        DeliveryZone::factory()->count(3)->create();

        $this->get(route('admin.delivery-zones.index'))
            ->assertOk()
            ->assertViewIs('admin.delivery-zones.index')
            ->assertViewHas('zones');
    }

    public function test_admin_can_view_create_form(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.delivery-zones.create'))
            ->assertOk()
            ->assertViewIs('admin.delivery-zones.create')
            ->assertViewHas('zone');
    }

    public function test_admin_can_store_a_new_delivery_zone(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('admin.delivery-zones.store'), [
            'name' => 'Phnom Penh Zone',
        ]);

        $response->assertRedirect(route('admin.delivery-zones.index'));
        $response->assertSessionHas('status', 'Delivery zone created.');

        $this->assertDatabaseHas('delivery_zones', [
            'name' => 'Phnom Penh Zone',
            'delivery_fee' => 0,
            'estimated_days' => 1,
            'is_active' => false,
        ]);
    }

    public function test_store_fails_validation_without_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.delivery-zones.store'), [])
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseCount('delivery_zones', 0);
    }

    public function test_store_fails_validation_when_estimated_days_is_less_than_one(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.delivery-zones.store'), [
            'name' => 'Phnom Penh Zone',
            'estimated_days' => 0,
        ])->assertSessionHasErrors(['estimated_days']);
    }

    public function test_admin_can_view_edit_form(): void
    {
        $this->actingAsAdmin();
        $zone = DeliveryZone::factory()->create();

        $this->get(route('admin.delivery-zones.edit', $zone))
            ->assertOk()
            ->assertViewIs('admin.delivery-zones.edit')
            ->assertViewHas('zone', fn (DeliveryZone $viewZone) => $viewZone->is($zone));
    }

    public function test_admin_can_update_a_delivery_zone(): void
    {
        $this->actingAsAdmin();
        $zone = DeliveryZone::factory()->create(['name' => 'Old Zone']);

        $response = $this->put(route('admin.delivery-zones.update', $zone), [
            'name' => 'New Zone',
            'delivery_fee' => 10,
            'estimated_days' => 3,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.delivery-zones.index'));
        $response->assertSessionHas('status', 'Delivery zone updated.');

        $zone->refresh();
        $this->assertSame('New Zone', $zone->name);
        $this->assertEquals(10, $zone->delivery_fee);
        $this->assertEquals(3, $zone->estimated_days);
        $this->assertTrue((bool) $zone->is_active);
    }

    public function test_admin_can_delete_a_delivery_zone(): void
    {
        $this->actingAsAdmin();
        $zone = DeliveryZone::factory()->create();

        $response = $this->delete(route('admin.delivery-zones.destroy', $zone));

        $response->assertRedirect(route('admin.delivery-zones.index'));
        $response->assertSessionHas('status', 'Delivery zone deleted.');
        $this->assertModelMissing($zone);
    }
}
