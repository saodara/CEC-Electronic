<?php

namespace Tests\Feature\Console;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckPendingBakongPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function configureBakong(array $overrides = []): void
    {
        config(array_merge([
            'services.bakong.base_url' => 'https://api-bakong.test/v1',
            'services.bakong.account_username' => '855012345678',
            'services.bakong.account_name' => 'CEC Electronic',
            'services.bakong.access_token' => 'test-token',
            'services.bakong.merchant_city' => 'Phnom Penh',
            'services.bakong.daily_check_limit' => 90,
        ], $overrides));
    }

    /** Order doesn't use HasFactory, so instantiate the factory class directly. */
    private function makeOrder(array $attributes = []): Order
    {
        return Order::factory()->create($attributes);
    }

    public function test_it_marks_a_confirmed_order_as_paid(): void
    {
        $this->configureBakong();

        $order = $this->makeOrder([
            'payment_method' => 'bakong',
            'payment_status' => 'unpaid',
            'bakong_qr_md5' => 'md5-one',
        ]);

        Http::fake([
            'https://api-bakong.test/v1/check_transaction_by_md5' => Http::response([
                'responseCode' => 0,
                'data' => ['hash' => 'md5-one'],
            ]),
        ]);

        $this->artisan('bakong:check-pending')->assertExitCode(0);

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertNotNull($order->fresh()->payment_confirmed_at);
    }

    public function test_it_leaves_unconfirmed_orders_unpaid(): void
    {
        $this->configureBakong();

        $order = $this->makeOrder([
            'payment_method' => 'bakong',
            'payment_status' => 'unpaid',
            'bakong_qr_md5' => 'md5-two',
        ]);

        Http::fake([
            'https://api-bakong.test/v1/check_transaction_by_md5' => Http::response([
                'responseCode' => 1,
                'data' => null,
            ]),
        ]);

        $this->artisan('bakong:check-pending')->assertExitCode(0);

        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_it_ignores_orders_older_than_two_hours(): void
    {
        $this->configureBakong();

        $order = $this->makeOrder([
            'payment_method' => 'bakong',
            'payment_status' => 'unpaid',
            'bakong_qr_md5' => 'md5-old',
            'created_at' => now()->subHours(3),
        ]);

        Http::fake([
            'https://api-bakong.test/v1/check_transaction_by_md5' => Http::response([
                'responseCode' => 0,
                'data' => ['hash' => 'md5-old'],
            ]),
        ]);

        $this->artisan('bakong:check-pending')->assertExitCode(0);

        Http::assertNothingSent();
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_it_prioritizes_the_newest_orders_over_a_stale_backlog(): void
    {
        $this->configureBakong();

        // Old abandoned carts fill the limit if oldest-first — this asserts
        // the freshest order is still checked ahead of them.
        Order::factory()->count(5)->create([
            'payment_method' => 'bakong',
            'payment_status' => 'unpaid',
            'created_at' => now()->subMinutes(90),
        ])->each(fn (Order $o, int $i) => $o->update(['bakong_qr_md5' => "md5-stale-{$i}"]));

        $freshOrder = $this->makeOrder([
            'payment_method' => 'bakong',
            'payment_status' => 'unpaid',
            'bakong_qr_md5' => 'md5-fresh',
            'created_at' => now(),
        ]);

        Http::fake([
            'https://api-bakong.test/v1/check_transaction_by_md5' => Http::response([
                'responseCode' => 0,
                'data' => ['hash' => 'paid'],
            ]),
        ]);

        $this->artisan('bakong:check-pending', ['--limit' => 1])->assertExitCode(0);

        $this->assertSame('paid', $freshOrder->fresh()->payment_status);
    }

    public function test_it_respects_the_limit_option(): void
    {
        $this->configureBakong();

        $orders = Order::factory()->count(3)->create([
            'payment_method' => 'bakong',
            'payment_status' => 'unpaid',
        ])->each(function (Order $order, int $i) {
            $order->update(['bakong_qr_md5' => "md5-limit-{$i}"]);
        });

        Http::fake([
            'https://api-bakong.test/v1/check_transaction_by_md5' => Http::response([
                'responseCode' => 0,
                'data' => ['hash' => 'paid'],
            ]),
        ]);

        $this->artisan('bakong:check-pending', ['--limit' => 2])->assertExitCode(0);

        Http::assertSentCount(2);
    }

    public function test_it_still_confirms_payments_when_the_cache_is_unwritable(): void
    {
        $this->configureBakong();
        config([
            'cache.default' => 'broken',
            'cache.stores.broken' => ['driver' => 'file', 'path' => '/proc/no-such-dir/cache'],
        ]);
        \Illuminate\Support\Facades\Cache::purge('broken');

        $order = $this->makeOrder([
            'payment_method' => 'bakong',
            'payment_status' => 'unpaid',
            'bakong_qr_md5' => 'md5-broken-cache',
        ]);
        Http::fake(['*' => Http::response(['responseCode' => 0, 'data' => ['hash' => 'md5-broken-cache']], 200)]);

        $this->artisan('bakong:check-pending')->assertSuccessful();

        $this->assertSame('paid', $order->fresh()->payment_status);
    }
}
