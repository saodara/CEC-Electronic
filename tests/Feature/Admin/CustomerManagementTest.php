<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Note: Order does not use the HasFactory trait, so Order::factory() is
// unavailable. The corresponding Factory class is invoked directly instead.

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_visiting_customer_index(): void
    {
        $this->get(route('admin.customers.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_user_is_redirected_to_login(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.customers.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_customer_index(): void
    {
        $this->actingAsAdmin();
        Order::factory()->count(3)->create(['customer_phone' => '012345678']);
        Order::factory()->count(2)->create(['customer_phone' => '098765432']);

        $this->get(route('admin.customers.index'))
            ->assertOk()
            ->assertViewIs('admin.customers.index')
            ->assertViewHas('customers');
    }

    public function test_customer_index_groups_orders_by_phone(): void
    {
        $this->actingAsAdmin();
        Order::factory()->count(3)->create(['customer_phone' => '012345678']);
        Order::factory()->count(2)->create(['customer_phone' => '098765432']);

        $response = $this->get(route('admin.customers.index'));

        $customers = $response->viewData('customers');
        $this->assertCount(2, $customers);
    }

    public function test_customer_list_shows_every_customer_but_no_admins(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->update(['phone' => '011111111']);
        $otherAdmin = User::factory()->admin()->create(['email' => 'boss@cec.test']);

        $registeredNoOrders = User::factory()->create(['name' => 'Sok Dara', 'phone' => '012000111']);
        $registeredWithOrders = User::factory()->create(['name' => 'Chan Lina', 'phone' => '012000222']);
        Order::factory()->create(['user_id' => $registeredWithOrders->id, 'customer_name' => 'Chan Lina', 'customer_phone' => '012000222', 'grand_total' => 40]);
        Order::factory()->create(['customer_name' => 'Guest Buyer', 'customer_phone' => '012000333', 'grand_total' => 25]);

        Order::factory()->create(['user_id' => $admin->id, 'customer_name' => 'Admin By Account', 'customer_phone' => '099000001']);
        Order::factory()->create(['customer_name' => 'Admin By Phone', 'customer_phone' => '011111111']);
        Order::factory()->create(['customer_name' => 'Admin By Email', 'customer_phone' => '099000003', 'customer_email' => 'Boss@CEC.test']);

        $response = $this->get(route('admin.customers.index'))->assertOk();

        $names = collect($response->viewData('customers')->items())->pluck('customer_name')->sort()->values()->all();
        $this->assertSame(['Chan Lina', 'Guest Buyer', 'Sok Dara'], $names);
        $response->assertSee('No orders yet')->assertSee('Registered')->assertSee('Guest')
            ->assertDontSee('Admin By Account')->assertDontSee('Admin By Phone')->assertDontSee('Admin By Email')
            ->assertDontSee($otherAdmin->email);

        $this->get(route('admin.customers.show', '011111111'))->assertNotFound();
        $this->get(route('admin.customers.show', '099000001'))->assertNotFound();
        $this->get(route('admin.customers.show', '012000333'))->assertOk();
    }

    public function test_customer_list_pages_ten_at_a_time(): void
    {
        $this->actingAsAdmin();
        User::factory()->count(12)->create();

        $this->assertCount(10, $this->get(route('admin.customers.index'))->viewData('customers')->items());
        $this->assertCount(2, $this->get(route('admin.customers.index', ['page' => 2]))->viewData('customers')->items());
    }

    public function test_guest_is_redirected_to_login_when_visiting_customer_show(): void
    {
        $order = Order::factory()->create(['customer_phone' => '012345678']);

        $this->get(route('admin.customers.show', $order->customer_phone))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_a_customer(): void
    {
        $this->actingAsAdmin();
        $phone = '012345678';
        Order::factory()->count(2)->create(['customer_phone' => $phone]);

        $this->get(route('admin.customers.show', $phone))
            ->assertOk()
            ->assertViewIs('admin.customers.show')
            ->assertViewHas('customer')
            ->assertViewHas('orders')
            ->assertViewHas('stats');
    }

    public function test_customer_show_returns_404_for_unknown_phone(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.customers.show', 'no-such-phone'))
            ->assertNotFound();
    }

    public function test_customer_show_computes_stats(): void
    {
        $this->actingAsAdmin();
        $phone = '012345678';
        Order::factory()->create(['customer_phone' => $phone, 'grand_total' => 100, 'payment_status' => 'paid']);
        Order::factory()->create(['customer_phone' => $phone, 'grand_total' => 50, 'payment_status' => 'unpaid']);

        $response = $this->get(route('admin.customers.show', $phone));

        $stats = $response->viewData('stats');
        $this->assertSame(2, $stats['orders']);
        $this->assertEquals(150, $stats['spent']);
        $this->assertSame(1, $stats['unpaid']);
    }
}
