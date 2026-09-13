<?php

namespace Tests\Feature\Customer;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Note: Order does not use the HasFactory trait, so Order::factory() is
// unavailable. The corresponding Factory class is invoked directly instead
// (same workaround already used elsewhere in this suite, e.g.
// tests/Feature/Admin/CustomerManagementTest.php).
class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get(route('account.dashboard'))
            ->assertRedirect(route('customer.login'));
    }

    public function test_guest_is_redirected_to_login_from_orders(): void
    {
        $this->get(route('account.orders'))
            ->assertRedirect(route('customer.login'));
    }

    public function test_guest_is_redirected_to_login_from_order_show(): void
    {
        $order = Order::factory()->create();

        $this->get(route('account.orders.show', $order))
            ->assertRedirect(route('customer.login'));
    }

    public function test_authenticated_user_sees_own_orders_on_dashboard(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account.dashboard'));

        $response->assertOk();
        $response->assertViewIs('account.dashboard');
        $response->assertViewHas('orders', function ($orders) use ($order) {
            return $orders->contains('id', $order->id);
        });
    }

    public function test_dashboard_does_not_include_another_users_orders(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownOrder = Order::factory()->create(['user_id' => $user->id]);
        $otherOrder = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('account.dashboard'));

        $orders = $response->viewData('orders');

        $this->assertTrue($orders->contains('id', $ownOrder->id));
        $this->assertFalse($orders->contains('id', $otherOrder->id));
    }

    public function test_authenticated_user_sees_own_orders_on_orders_page(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account.orders'));

        $response->assertOk();
        $response->assertViewIs('account.orders');
        $response->assertViewHas('orders', function ($orders) use ($order) {
            return $orders->contains('id', $order->id);
        });
    }

    public function test_orders_page_does_not_include_another_users_orders(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownOrder = Order::factory()->create(['user_id' => $user->id]);
        $otherOrder = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('account.orders'));

        $orders = $response->viewData('orders');

        $this->assertTrue($orders->contains('id', $ownOrder->id));
        $this->assertFalse($orders->contains('id', $otherOrder->id));
    }

    public function test_orders_page_is_paginated_with_10_per_page(): void
    {
        $user = User::factory()->create();

        Order::factory()->count(11)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account.orders'));

        $response->assertOk();
        $orders = $response->viewData('orders');

        $this->assertSame(11, $orders->total());
        $this->assertSame(10, $orders->count());
        $this->assertTrue($orders->hasMorePages());

        $secondPage = $this->actingAs($user)->get(route('account.orders', ['page' => 2]));
        $secondPage->assertOk();
        $this->assertSame(1, $secondPage->viewData('orders')->count());
    }

    public function test_show_returns_403_when_order_belongs_to_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('account.orders.show', $order));

        $response->assertForbidden();
    }

    public function test_show_returns_200_with_correct_view_and_eager_loaded_relations_for_own_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account.orders.show', $order));

        $response->assertOk();
        $response->assertViewIs('account.order-show');
        $response->assertViewHas('order', function ($viewOrder) use ($order) {
            return $viewOrder->id === $order->id
                && $viewOrder->relationLoaded('items')
                && $viewOrder->relationLoaded('deliveryProvider')
                && $viewOrder->relationLoaded('deliveryZone');
        });
    }
}
