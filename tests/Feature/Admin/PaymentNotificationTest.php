<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.bakong.base_url' => 'https://api-bakong.test/v1',
            'services.bakong.account_username' => '855012345678',
            'services.bakong.access_token' => 'test-token',
            'services.bakong.qr_expiry_seconds' => 180,
        ]);
    }

    public function test_guest_cannot_read_payment_notifications(): void
    {
        $this->getJson(route('admin.payment-notifications'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_customer_cannot_read_payment_notifications(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('admin.payment-notifications'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_successful_customer_payment_shows_up_as_admin_alert_and_clears_when_viewed(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'customer_name' => 'Jane Buyer',
            'payment_method' => 'bakong',
            'payment_status' => 'unpaid',
            'grand_total' => 25.50,
            'bakong_qr_string' => 'qr',
            'bakong_qr_md5' => md5('qr'),
            'bakong_qr_expires_at' => now()->addMinutes(3),
        ]);

        $admin = $this->actingAsAdmin();

        // Before payment: no alert.
        $this->getJson(route('admin.payment-notifications'))
            ->assertOk()
            ->assertJson(['count' => 0, 'orders' => []]);

        // Customer's checkout page polls and Bakong reports the payment.
        Http::fake(['*' => Http::response(['responseCode' => 0, 'data' => ['hash' => 'abc']], 200)]);

        $this->actingAs($customer)
            ->getJson(route('checkout.payment-status', $order))
            ->assertOk()
            ->assertJson(['is_paid' => true]);

        // Admin now sees the alert.
        $this->actingAsAdmin($admin);

        $this->getJson(route('admin.payment-notifications'))
            ->assertOk()
            ->assertJson([
                'count' => 1,
                'orders' => [[
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => 'Jane Buyer',
                    'payment_method' => 'BAKONG',
                    'grand_total' => '25.50',
                    'url' => route('admin.orders.show', $order),
                ]],
            ]);

        // The bell is rendered on admin pages.
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="payBellBtn"', false);

        // Opening the order marks the alert as seen.
        $this->get(route('admin.orders.show', $order))->assertOk();

        $this->getJson(route('admin.payment-notifications'))
            ->assertOk()
            ->assertJson(['count' => 0, 'orders' => []]);
    }

    public function test_payment_is_picked_up_within_about_ten_seconds(): void
    {
        config(['services.bakong.check_interval_seconds' => 10]);

        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'payment_method' => 'bakong',
            'payment_status' => 'unpaid',
            'bakong_qr_string' => 'qr',
            'bakong_qr_md5' => md5('qr'),
            'bakong_qr_expires_at' => now()->addMinutes(3),
        ]);

        // First check: Bakong hasn't seen the payment yet.
        Http::fake(['*' => Http::sequence()
            ->push(['responseCode' => 1], 200)
            ->push(['responseCode' => 0, 'data' => ['hash' => 'abc']], 200)]);

        $this->actingAs($customer)->getJson(route('checkout.payment-status', $order))
            ->assertJson(['is_paid' => false]);

        // 5s later: still inside the interval, so Bakong isn't asked again.
        $this->travel(5)->seconds();
        $this->getJson(route('checkout.payment-status', $order))
            ->assertJson(['is_paid' => false]);
        Http::assertSentCount(1);

        // 10s after the first check: Bakong is asked again and confirms it.
        $this->travel(6)->seconds();
        $this->getJson(route('checkout.payment-status', $order))
            ->assertJson(['is_paid' => true]);
        Http::assertSentCount(2);
    }

    public function test_unpaid_orders_do_not_trigger_alerts(): void
    {
        Order::factory()->count(2)->create(['payment_status' => 'unpaid']);

        $this->actingAsAdmin();

        $this->getJson(route('admin.payment-notifications'))
            ->assertOk()
            ->assertJson(['count' => 0]);
    }
}
