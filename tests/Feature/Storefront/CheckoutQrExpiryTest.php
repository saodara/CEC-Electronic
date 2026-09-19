<?php

namespace Tests\Feature\Storefront;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutQrExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.bakong.base_url' => 'https://api-bakong.test/v1',
            'services.bakong.account_username' => '855012345678',
            'services.bakong.access_token' => 'test-token',
            'services.bakong.qr_expiry_seconds' => 90,
        ]);
    }

    private function bakongOrder(User $user, array $overrides = []): Order
    {
        return Order::factory()->create(array_merge([
            'user_id' => $user->id,
            'payment_method' => 'bakong',
            'payment_status' => 'unpaid',
            'bakong_qr_string' => 'old-qr',
            'bakong_qr_md5' => md5('old-qr'),
            'bakong_qr_expires_at' => now()->addMinutes(30),
        ], $overrides));
    }

    public function test_unpaid_success_page_has_a_close_button_in_both_qr_states(): void
    {
        $user = User::factory()->create();
        $order = $this->bakongOrder($user);

        $html = $this->actingAs($user)->get(route('checkout.success', $order))->assertOk()->getContent();

        // One Close in the live-QR state, one beside "Generate new QR" in the expired state.
        $this->assertSame(2, substr_count($html, 'data-payment-close style='));
        $this->assertStringContainsString('data-payment-reopen', $html);
    }

    public function test_success_page_does_not_reset_a_running_qr_deadline(): void
    {
        $user = User::factory()->create();
        $order = $this->bakongOrder($user);
        $deadline = $order->bakong_qr_expires_at->copy();

        $this->actingAs($user)->get(route('checkout.success', $order))->assertOk();

        $order->refresh();
        $this->assertSame('old-qr', $order->bakong_qr_string);
        $this->assertTrue($order->bakong_qr_expires_at->equalTo($deadline));
    }

    public function test_success_page_issues_a_90_second_qr_for_orders_without_a_deadline(): void
    {
        $user = User::factory()->create();
        $order = $this->bakongOrder($user, ['bakong_qr_expires_at' => null]);

        $this->actingAs($user)->get(route('checkout.success', $order))->assertOk();

        $order->refresh();
        $this->assertNotSame('old-qr', $order->bakong_qr_string);
        $this->assertEqualsWithDelta(90, now()->diffInSeconds($order->bakong_qr_expires_at), 2);
    }

    public function test_success_page_shows_expired_state_after_the_deadline(): void
    {
        $user = User::factory()->create();
        $order = $this->bakongOrder($user, ['bakong_qr_expires_at' => now()->subMinute()]);

        $this->actingAs($user)->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee('Generate new QR');

        $this->assertSame('old-qr', $order->fresh()->bakong_qr_string);
    }

    public function test_payment_status_reports_qr_expired(): void
    {
        $user = User::factory()->create();
        $order = $this->bakongOrder($user, ['bakong_qr_expires_at' => now()->subMinute()]);
        Http::fake(['*' => Http::response(['responseCode' => 1], 200)]);

        $this->actingAs($user)->getJson(route('checkout.payment-status', $order))
            ->assertOk()
            ->assertJson(['qr_expired' => true, 'is_paid' => false]);
    }

    public function test_regenerate_issues_a_new_qr_once_the_old_one_expired(): void
    {
        $user = User::factory()->create();
        $order = $this->bakongOrder($user, ['bakong_qr_expires_at' => now()->subMinute()]);
        Http::fake(['*' => Http::response(['responseCode' => 1], 200)]);

        $this->actingAs($user)->post(route('checkout.regenerate-qr', $order))
            ->assertRedirect(route('checkout.success', $order));

        $order->refresh();
        $this->assertNotSame('old-qr', $order->bakong_qr_string);
        $this->assertFalse($order->bakongQrExpired());
    }

    public function test_regenerate_is_a_no_op_while_the_qr_is_still_valid(): void
    {
        $user = User::factory()->create();
        $order = $this->bakongOrder($user);

        $this->actingAs($user)->post(route('checkout.regenerate-qr', $order));

        $this->assertSame('old-qr', $order->fresh()->bakong_qr_string);
    }

    public function test_regenerate_marks_order_paid_when_the_old_qr_was_paid_at_the_last_minute(): void
    {
        $user = User::factory()->create();
        $order = $this->bakongOrder($user, ['bakong_qr_expires_at' => now()->subMinute()]);
        Http::fake(['*' => Http::response(['responseCode' => 0, 'data' => ['hash' => 'x']], 200)]);

        $this->actingAs($user)->post(route('checkout.regenerate-qr', $order));

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('old-qr', $order->bakong_qr_string);
    }

    public function test_regenerate_is_forbidden_for_another_customer(): void
    {
        $order = $this->bakongOrder(User::factory()->create(), ['bakong_qr_expires_at' => now()->subMinute()]);

        $this->actingAs(User::factory()->create())
            ->post(route('checkout.regenerate-qr', $order))
            ->assertForbidden();
    }
}
