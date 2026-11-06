<?php

namespace Tests\Feature\Services;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStockTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create(['stock_quantity' => 10]);
    }

    private function order(array $attributes = [], int $quantity = 3): Order
    {
        $order = Order::factory()->create($attributes + ['status' => 'pending', 'payment_status' => 'unpaid', 'payment_method' => 'bakong']);
        $order->items()->create([
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => $quantity,
            'unit_price' => 10,
            'line_total' => 10 * $quantity,
        ]);

        return $order;
    }

    private function assertStock(int $expected): void
    {
        $this->assertSame($expected, $this->product->fresh()->stock_quantity);
    }

    public function test_unpaid_qr_order_holds_no_stock_until_paid(): void
    {
        $order = $this->order();
        $order->update(['notes' => 'still waiting']);
        $this->assertStock(10);

        $order->update(['payment_status' => 'paid']);
        $this->assertStock(7);
        $this->assertNotNull($order->fresh()->stock_deducted_at);
    }

    public function test_payment_saved_twice_only_counts_once(): void
    {
        $order = $this->order();

        $order->update(['payment_status' => 'paid']);
        $order->fresh()->update(['status' => 'processing']);
        $order->fresh()->update(['status' => 'completed']);
        Order::findOrFail($order->id)->update(['payment_status' => 'refunded']);
        Order::findOrFail($order->id)->update(['payment_status' => 'paid']);

        $this->assertStock(7);
    }

    public function test_cancelling_a_paid_order_returns_stock_once(): void
    {
        $order = $this->order();
        $order->update(['payment_status' => 'paid']);

        $order->fresh()->update(['status' => 'cancelled']);
        $this->assertStock(10);
        $this->assertNull($order->fresh()->stock_deducted_at);

        $order->fresh()->update(['payment_status' => 'refunded']);
        $this->assertStock(10);
    }

    public function test_cancelling_an_unpaid_order_changes_nothing(): void
    {
        $order = $this->order();

        $order->update(['status' => 'cancelled']);
        $this->assertStock(10);

        $order->fresh()->update(['payment_status' => 'paid']);
        $this->assertStock(10);
    }

    public function test_reopening_a_cancelled_paid_order_takes_stock_again(): void
    {
        $order = $this->order(['payment_status' => 'paid', 'stock_deducted_at' => now()]);

        $order->update(['status' => 'cancelled']);
        $this->assertStock(13);

        $order->fresh()->update(['status' => 'processing']);
        $this->assertStock(10);
    }

    public function test_cancelling_an_order_from_before_this_rule_adds_no_stock(): void
    {
        $order = $this->order(['payment_status' => 'paid', 'stock_deducted_at' => null, 'status' => 'completed']);

        // Already paid before stock tracking existed, so it was never taken out.
        $order->update(['status' => 'cancelled']);

        $this->assertStock(10);
    }

    public function test_cash_on_delivery_checkout_takes_stock_when_placed(): void
    {
        $user = User::factory()->create();
        CartItem::factory()->create(['user_id' => $user->id, 'session_id' => null, 'product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 10]);

        $this->actingAs($user)->post(route('checkout.store'), $this->payload('cash_on_delivery'))->assertRedirect();

        $this->assertStock(8);
        $order = Order::where('payment_method', 'cash_on_delivery')->firstOrFail();
        $this->assertNotNull($order->stock_deducted_at);

        $order->update(['payment_status' => 'paid']);
        $this->assertStock(8);

        $order->fresh()->update(['status' => 'cancelled']);
        $this->assertStock(10);
    }

    public function test_item_cost_is_saved_at_checkout(): void
    {
        $this->product->update(['cost_price' => 6.5]);
        $user = User::factory()->create();
        CartItem::factory()->create(['user_id' => $user->id, 'session_id' => null, 'product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 10]);

        $this->actingAs($user)->post(route('checkout.store'), $this->payload('cash_on_delivery'));

        $this->assertSame('6.50', Order::firstOrFail()->items()->firstOrFail()->unit_cost);
    }

    public function test_selling_more_than_in_stock_goes_negative_and_product_can_still_be_edited(): void
    {
        $order = $this->order([], 12);
        $order->update(['payment_status' => 'paid']);
        $this->assertStock(-2);

        $this->actingAsAdmin();
        $this->put(route('admin.products.update', $this->product), [
            'name' => $this->product->name,
            'price' => 20,
            'stock_quantity' => -2,
        ])->assertSessionHasNoErrors();
    }

    public function test_deleted_product_does_not_break_an_order(): void
    {
        $order = $this->order();
        $order->items()->create(['product_id' => null, 'product_name' => 'Deleted', 'quantity' => 1, 'unit_price' => 1, 'line_total' => 1]);

        $order->update(['payment_status' => 'paid']);

        $this->assertStock(7);
    }

    private function payload(string $method): array
    {
        return [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '012345678',
            'address_line_1' => '123 Main St',
            'city' => 'Phnom Penh',
            'country' => 'Cambodia',
            'payment_method' => $method,
        ];
    }
}
