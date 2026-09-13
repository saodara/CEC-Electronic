<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'session_id' => null,
            'product_id' => Product::factory(),
            'quantity' => 1,
            'unit_price' => $this->faker->numberBetween(10, 200),
        ];
    }
}
