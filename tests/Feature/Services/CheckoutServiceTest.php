<?php

namespace Tests\Feature\Services;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * App\Models\Order and App\Models\CartItem don't use the HasFactory trait,
     * so `Order::factory()` / `CartItem::factory()` aren't available. Fall back
     * to instantiating the factory classes directly.
     */
    private function makeCartItem(array $attributes = []): CartItem
    {
        return CartItem::factory()->create($attributes);
    }

    private function makeOrder(array $attributes = []): Order
    {
        return Order::factory()->create($attributes);
    }

    private function validCheckoutPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '012345678',
            'address_line_1' => '123 Main St',
            'address_line_2' => null,
            'city' => 'Phnom Penh',
            'province' => null,
            'country' => 'Cambodia',
            'shipping_method' => 'standard',
            'payment_method' => 'cash_on_delivery',
            'notes' => null,
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login_on_checkout_create(): void
    {
        $this->get(route('checkout.create'))
            ->assertRedirect(route('customer.login'));
    }

    public function test_guest_is_redirected_to_login_on_checkout_store(): void
    {
        $this->post(route('checkout.store'), $this->validCheckoutPayload())
            ->assertRedirect(route('customer.login'));
    }

    public function test_store_redirects_to_cart_when_cart_is_empty(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('checkout.store'), $this->validCheckoutPayload())
            ->assertRedirect(route('shop.cart'))
            ->assertSessionHas('status', 'Your cart is empty.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_store_creates_order_and_items_and_clears_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $cartItem = $this->makeCartItem([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 10.00,
        ]);

        $response = $this->actingAs($user)
            ->post(route('checkout.store'), $this->validCheckoutPayload());

        $order = Order::first();
        $this->assertNotNull($order);

        $response->assertRedirect(route('checkout.success', $order));
        $response->assertSessionHas('status', 'Order placed.');

        $this->assertMatchesRegularExpression('/^EH-\d{8}-\d{4}$/', $order->order_number);
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('John Doe', $order->customer_name);
        $this->assertSame('john@example.com', $order->customer_email);
        $this->assertSame('012345678', $order->customer_phone);
        $this->assertSame('pending', $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('cash_on_delivery', $order->payment_method);
        $this->assertSame('standard', $order->shipping_method);
        $this->assertEquals(30.00, $order->subtotal);
        $this->assertEquals(0, $order->shipping_total);
        $this->assertEquals(0, $order->discount_total);
        $this->assertEquals(30.00, $order->grand_total);
        $this->assertSame('123 Main St', $order->shipping_address['address_line_1']);
        $this->assertSame('Phnom Penh', $order->shipping_address['city']);
        $this->assertSame('Cambodia', $order->shipping_address['country']);

        $this->assertDatabaseCount('order_items', 1);
        $orderItem = $order->items()->first();
        $this->assertSame($product->id, $orderItem->product_id);
        $this->assertSame($product->name, $orderItem->product_name);
        $this->assertSame(3, $orderItem->quantity);
        $this->assertEquals(10.00, $orderItem->unit_price);
        $this->assertEquals(30.00, $orderItem->line_total);

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    public function test_double_submitting_place_order_does_not_crash_on_the_second_request(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->makeCartItem([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10.00,
        ]);

        // First submission succeeds and clears the cart, same as a real "Place
        // order" click. A double-click, back-button resubmit, or slow-network
        // retry sends a second identical request against the now-empty cart —
        // that must fail soft, not crash with a 422 error page.
        $this->actingAs($user)
            ->post(route('checkout.store'), $this->validCheckoutPayload())
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('checkout.store'), $this->validCheckoutPayload())
            ->assertRedirect(route('shop.cart'))
            ->assertSessionHas('status', 'Your cart is empty.');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_store_defaults_customer_email_to_user_email_when_not_provided(): void
    {
        $user = User::factory()->create(['email' => 'user-account@example.com']);
        $product = Product::factory()->create();

        $this->makeCartItem([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 5,
        ]);

        $payload = $this->validCheckoutPayload();
        unset($payload['customer_email']);

        $this->actingAs($user)->post(route('checkout.store'), $payload);

        $order = Order::first();
        $this->assertSame('user-account@example.com', $order->customer_email);
    }

    public function test_store_generates_bakong_qr_when_payment_method_is_bakong(): void
    {
        config([
            'services.bakong.account_username' => '855012345678',
            'services.bakong.base_url' => 'https://api-bakong.test/v1',
            'services.bakong.access_token' => 'test-token',
        ]);

        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->makeCartItem([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 20,
        ]);

        $this->actingAs($user)->post(route('checkout.store'), $this->validCheckoutPayload([
            'payment_method' => 'bakong',
        ]));

        $order = Order::first();
        $this->assertNotNull($order->bakong_qr_string);
        $this->assertNotNull($order->bakong_qr_md5);
    }

    public function test_store_validation_fails_when_required_fields_are_missing(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->makeCartItem(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->actingAs($user)
            ->post(route('checkout.store'), $this->validCheckoutPayload([
                'customer_name' => '',
                'customer_phone' => '',
                'address_line_1' => '',
                'city' => '',
            ]))
            ->assertSessionHasErrors(['customer_name', 'customer_phone', 'address_line_1', 'city']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_success_view_is_forbidden_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $order = $this->makeOrder(['user_id' => $owner->id]);

        $this->actingAs($stranger)
            ->get(route('checkout.success', $order))
            ->assertForbidden();
    }

    public function test_success_view_is_shown_to_owner(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $user->id, 'payment_method' => 'cash_on_delivery']);

        $this->actingAs($user)
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->assertViewIs('checkout.success')
            ->assertViewHas('order', fn ($viewOrder) => $viewOrder->is($order));
    }

    public function test_payment_status_is_forbidden_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $order = $this->makeOrder(['user_id' => $owner->id]);

        $this->actingAs($stranger)
            ->get(route('checkout.payment-status', $order))
            ->assertForbidden();
    }

    public function test_payment_status_returns_current_state_without_calling_bakong_when_already_paid(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $order = $this->makeOrder([
            'user_id' => $user->id,
            'payment_status' => 'paid',
            'payment_confirmed_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson(route('checkout.payment-status', $order))
            ->assertOk()
            ->assertJson([
                'order_number' => $order->order_number,
                'payment_status' => 'paid',
                'is_paid' => true,
            ]);

        Http::assertNothingSent();
    }

    public function test_payment_status_updates_order_when_bakong_reports_paid(): void
    {
        config([
            'services.bakong.base_url' => 'https://api-bakong.test/v1',
            'services.bakong.access_token' => 'test-token',
        ]);

        Http::fake([
            'https://api-bakong.test/v1/check_transaction_by_md5' => Http::response([
                'responseCode' => 0,
                'data' => ['hash' => 'md5-xyz'],
            ]),
        ]);

        $user = User::factory()->create();
        $order = $this->makeOrder([
            'user_id' => $user->id,
            'payment_status' => 'unpaid',
            'bakong_qr_md5' => 'md5-xyz',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('checkout.payment-status', $order));

        $response->assertOk()
            ->assertJson([
                'payment_status' => 'paid',
                'is_paid' => true,
            ]);

        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertNotNull($order->payment_confirmed_at);
    }

    public function test_payment_status_stays_unpaid_when_bakong_reports_not_paid(): void
    {
        config([
            'services.bakong.base_url' => 'https://api-bakong.test/v1',
            'services.bakong.access_token' => 'test-token',
        ]);

        Http::fake([
            'https://api-bakong.test/v1/check_transaction_by_md5' => Http::response([
                'responseCode' => 1,
                'data' => null,
            ]),
        ]);

        $user = User::factory()->create();
        $order = $this->makeOrder([
            'user_id' => $user->id,
            'payment_status' => 'unpaid',
            'bakong_qr_md5' => 'md5-xyz',
        ]);

        $this->actingAs($user)
            ->getJson(route('checkout.payment-status', $order))
            ->assertOk()
            ->assertJson([
                'payment_status' => 'unpaid',
                'is_paid' => false,
            ]);

        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }
}
