<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartAjaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_to_cart_returns_json_count_for_ajax_requests(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson(route('cart.store', $product), [
            'quantity' => 2,
        ]);

        $response->assertOk()->assertJson([
            'count' => 2,
        ]);
    }

    public function test_increasing_quantity_via_ajax_returns_updated_totals(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100]);

        $this->actingAs($user)->post(route('cart.store', $product), ['quantity' => 1]);
        $cartItem = CartItem::first();

        $response = $this->actingAs($user)->patchJson(route('cart.update', $cartItem), [
            'quantity' => 2,
        ]);

        $response->assertOk()->assertJson([
            'removed' => false,
            'quantity' => 2,
            'line_total' => '200.00',
            'subtotal' => '200.00',
            'count' => 2,
        ]);
    }

    public function test_decreasing_quantity_to_zero_via_ajax_removes_the_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->post(route('cart.store', $product), ['quantity' => 1]);
        $cartItem = CartItem::first();

        $response = $this->actingAs($user)->patchJson(route('cart.update', $cartItem), [
            'quantity' => 0,
        ]);

        $response->assertOk()->assertJson([
            'removed' => true,
            'count' => 0,
        ]);
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    public function test_removing_item_via_ajax_returns_updated_count(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->post(route('cart.store', $product), ['quantity' => 1]);
        $cartItem = CartItem::first();

        $response = $this->actingAs($user)->deleteJson(route('cart.destroy', $cartItem));

        $response->assertOk()->assertJson([
            'removed' => true,
            'count' => 0,
        ]);
    }
}
