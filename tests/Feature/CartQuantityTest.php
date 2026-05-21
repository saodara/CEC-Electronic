<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartQuantityTest extends TestCase
{
    use RefreshDatabase;

    public function test_decimal_quantity_is_saved_as_whole_number_when_adding_to_cart(): void
    {
        $product = Product::factory()->create();

        $this->post(route('cart.store', $product), [
            'quantity' => '1.5',
        ])->assertRedirect();

        $this->assertSame(1, CartItem::first()->quantity);
    }

    public function test_decimal_quantity_is_saved_as_whole_number_when_updating_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->post(route('cart.store', $product), [
            'quantity' => 1,
        ]);

        $cartItem = CartItem::first();

        $this->actingAs($user)->patch(route('cart.update', $cartItem), [
            'quantity' => '3.7',
        ])->assertRedirect();

        $this->assertSame(3, $cartItem->refresh()->quantity);
    }
}
