<?php

namespace Tests\Feature\Admin;

use App\Models\DeliveryProvider;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_visiting_order_index(): void
    {
        $this->get(route('admin.orders.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_user_is_redirected_to_login(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.orders.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_order_index(): void
    {
        $this->actingAsAdmin();
        Order::factory()->count(3)->create();

        $this->get(route('admin.orders.index'))
            ->assertOk()
            ->assertViewIs('admin.orders.index')
            ->assertViewHas('orders')
            ->assertViewHas('paymentNotificationsCount');
    }

    public function test_admin_can_view_an_order(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create();

        $this->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertViewIs('admin.orders.show')
            ->assertViewHas('order', fn (Order $viewOrder) => $viewOrder->is($order))
            ->assertViewHas('deliveryProviders')
            ->assertViewHas('deliveryZones');
    }

    public function test_viewing_an_order_with_unseen_payment_confirmation_marks_it_seen(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create([
            'payment_confirmed_at' => now()->subHour(),
            'admin_payment_seen_at' => null,
        ]);

        $this->get(route('admin.orders.show', $order))->assertOk();

        $order->refresh();
        $this->assertNotNull($order->admin_payment_seen_at);
    }

    public function test_viewing_an_order_does_not_change_an_already_seen_payment_timestamp(): void
    {
        $this->actingAsAdmin();
        $seenAt = now()->subDay();
        $order = Order::factory()->create([
            'payment_confirmed_at' => now()->subDays(2),
            'admin_payment_seen_at' => $seenAt,
        ]);

        $this->get(route('admin.orders.show', $order))->assertOk();

        $order->refresh();
        $this->assertEquals($seenAt->toDateTimeString(), $order->admin_payment_seen_at->toDateTimeString());
    }

    public function test_admin_can_update_order_status_and_payment_status(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid']);

        $response = $this->put(route('admin.orders.update', $order), [
            'status' => 'processing',
            'payment_status' => 'unpaid',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Order updated.');

        $order->refresh();
        $this->assertSame('processing', $order->status);
    }

    public function test_marking_order_as_paid_sets_payment_confirmation_timestamps(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid']);

        $this->put(route('admin.orders.update', $order), [
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->payment_confirmed_at);
        $this->assertNotNull($order->admin_payment_seen_at);
    }

    public function test_update_validation_fails_without_required_fields(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create();

        $this->put(route('admin.orders.update', $order), [])
            ->assertSessionHasErrors(['status', 'payment_status']);
    }

    public function test_update_with_delivery_provider_creates_a_shipment(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid']);
        $provider = DeliveryProvider::factory()->create();

        $this->put(route('admin.orders.update', $order), [
            'status' => 'processing',
            'payment_status' => 'unpaid',
            'delivery_provider_id' => $provider->id,
            'tracking_number' => 'TRACK-123',
            // shipped_at/delivered_at must be present (even as null) or the
            // controller throws an "Undefined array key" error — see the
            // bug note in the class docblock below.
            'shipped_at' => null,
            'delivered_at' => null,
        ]);

        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'delivery_provider_id' => $provider->id,
            'tracking_number' => 'TRACK-123',
            'status' => 'pending',
        ]);
    }

    public function test_update_with_shipped_at_creates_a_shipped_shipment(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid']);
        $provider = DeliveryProvider::factory()->create();
        $shippedAt = now();

        $this->put(route('admin.orders.update', $order), [
            'status' => 'shipped',
            'payment_status' => 'unpaid',
            'delivery_provider_id' => $provider->id,
            'tracking_number' => 'TRACK-999',
            'shipped_at' => $shippedAt->toDateTimeString(),
            'delivered_at' => null,
        ]);

        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'tracking_number' => 'TRACK-999',
            'status' => 'shipped',
        ]);
    }

    public function test_update_with_delivery_provider_and_no_shipped_or_delivered_at_keys_creates_a_pending_shipment(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid']);
        $provider = DeliveryProvider::factory()->create();

        $response = $this->put(route('admin.orders.update', $order), [
            'status' => 'processing',
            'payment_status' => 'unpaid',
            'delivery_provider_id' => $provider->id,
            'tracking_number' => 'TRACK-123',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'delivery_provider_id' => $provider->id,
            'tracking_number' => 'TRACK-123',
            'status' => 'pending',
        ]);
    }

    public function test_update_without_delivery_provider_or_tracking_number_does_not_create_a_shipment(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid']);

        $this->put(route('admin.orders.update', $order), [
            'status' => 'processing',
            'payment_status' => 'unpaid',
        ]);

        $this->assertDatabaseCount('shipments', 0);
    }

    private function unpaidBakongOrder(): Order
    {
        config([
            'services.bakong.base_url' => 'https://api-bakong.test/v1',
            'services.bakong.access_token' => 'test-token',
        ]);

        return Order::factory()->create([
            'payment_method' => 'bakong',
            'payment_status' => 'unpaid',
            'bakong_qr_md5' => 'md5-admin',
        ]);
    }

    public function test_admin_can_verify_a_bakong_payment(): void
    {
        $order = $this->unpaidBakongOrder();
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['responseCode' => 0, 'data' => ['hash' => 'md5-admin']], 200)]);
        $this->actingAsAdmin();

        $this->post(route('admin.orders.verify-payment', $order))->assertRedirect();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->payment_confirmed_at);
    }

    public function test_admin_verify_leaves_the_order_unpaid_when_bakong_has_no_payment(): void
    {
        $order = $this->unpaidBakongOrder();
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['responseCode' => 1, 'data' => null], 200)]);
        $this->actingAsAdmin();

        $this->post(route('admin.orders.verify-payment', $order))->assertRedirect();

        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_admin_verify_works_when_the_cache_is_unwritable(): void
    {
        $order = $this->unpaidBakongOrder();
        config([
            'cache.default' => 'broken',
            'cache.stores.broken' => ['driver' => 'file', 'path' => '/proc/no-such-dir/cache'],
        ]);
        \Illuminate\Support\Facades\Cache::purge('broken');
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['responseCode' => 0, 'data' => ['hash' => 'md5-admin']], 200)]);
        $this->actingAsAdmin();

        $this->post(route('admin.orders.verify-payment', $order))->assertRedirect();

        $this->assertSame('paid', $order->fresh()->payment_status);
    }
}
