<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition()
    {
        $name = $this->faker->words(3, true);
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->randomNumber(4),
            'description' => $this->faker->sentences(3, true),
            'price' => $this->faker->numberBetween(99, 1999),
            'compare_at_price' => $this->faker->optional()->numberBetween(299, 2199),
            'stock_quantity' => $this->faker->numberBetween(0, 50),
            'is_active' => true,
            'is_featured' => $this->faker->boolean(25),
            'image' => 'https://via.placeholder.com/640x420?text=' . urlencode($name),
            'category' => $this->faker->randomElement(['laptops','phones','accessories']),
        ];
    }
}
