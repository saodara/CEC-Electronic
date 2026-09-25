<?php

namespace Tests\Feature\Storefront;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
            'services.bakong.qr_expiry_seconds' => 180,
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

    public function test_success_page_issues_a_3_minute_qr_for_orders_without_a_deadline(): void
    {
        $user = User::factory()->create();
        $order = $this->bakongOrder($user, ['bakong_qr_expires_at' => null]);

        $this->actingAs($user)->get(route('checkout.success', $order))->assertOk();

        $order->refresh();
        $this->assertNotSame('old-qr', $order->bakong_qr_string);
        $this->assertEqualsWithDelta(180, now()->diffInSeconds($order->bakong_qr_expires_at), 2);
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

    private function bakongSaysPaid(): void
    {
        Http::fake(['*' => Http::response(['responseCode' => 0, 'data' => ['hash' => md5('old-qr')]], 200)]);
    }

    public function test_payment_is_confirmed_even_when_the_cache_is_unwritable(): void
    {
        // Reproduces a cache dir that the web user cannot write to (root-owned
        // folders): the daily-budget counter and throttle must not stop a paid
        // order from being confirmed.
        config([
            'cache.default' => 'broken',
            'cache.stores.broken' => ['driver' => 'file', 'path' => '/proc/no-such-dir/cache'],
        ]);
        Cache::purge('broken');

        $user = User::factory()->create();
        $order = $this->bakongOrder($user);
        $this->bakongSaysPaid();

        $this->actingAs($user)->getJson(route('checkout.payment-status', $order))
            ->assertOk()
            ->assertJson(['is_paid' => true]);

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_payment_made_in_the_last_seconds_is_caught_by_one_final_check_after_expiry(): void
    {
        $user = User::factory()->create();
        $order = $this->bakongOrder($user, ['bakong_qr_expires_at' => now()->subSeconds(2)]);
        $this->bakongSaysPaid();

        // The normal 60s throttle window is already used up by an earlier poll.
        Cache::put("bakong-check:{$order->id}", true, 60);

        $this->actingAs($user)->getJson(route('checkout.payment-status', $order))
            ->assertOk()
            ->assertJson(['is_paid' => true]);

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_the_final_check_after_expiry_runs_only_once_per_order(): void
    {
        $user = User::factory()->create();
        $order = $this->bakongOrder($user, ['bakong_qr_expires_at' => now()->subSeconds(2)]);
        Http::fake(['*' => Http::response(['responseCode' => 1], 200)]);
        Cache::put("bakong-check:{$order->id}", true, 60);

        $this->actingAs($user)->getJson(route('checkout.payment-status', $order))->assertOk();
        $this->actingAs($user)->getJson(route('checkout.payment-status', $order))->assertOk();
        $this->actingAs($user)->getJson(route('checkout.payment-status', $order))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_success_page_does_a_final_check_instead_of_giving_up_when_the_qr_ran_out(): void
    {
        $user = User::factory()->create();
        $order = $this->bakongOrder($user, ['bakong_qr_expires_at' => now()->subMinute()]);

        $html = $this->actingAs($user)->get(route('checkout.success', $order))->assertOk()->getContent();

        $this->assertStringContainsString('finalCheck', $html);
    }

    private function breakTheCache(): void
    {
        config([
            'cache.default' => 'broken',
            'cache.stores.broken' => ['driver' => 'file', 'path' => '/proc/no-such-dir/cache'],
        ]);
        Cache::purge('broken');
    }

    public function test_a_broken_cache_does_not_let_polling_flood_bakong(): void
    {
        $this->breakTheCache();
        $user = User::factory()->create();
        $order = $this->bakongOrder($user);
        Http::fake(['*' => Http::response(['responseCode' => 1], 200)]);

        // The page polls every 15s; without a working throttle every poll would
        // spend one of Bakong's ~100 daily requests.
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->getJson(route('checkout.payment-status', $order))->assertOk();
        }

        Http::assertSentCount(1);
    }
}
