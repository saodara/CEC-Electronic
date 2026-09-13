<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(20, 500);

        return [
            'order_number' => 'EH-' . $this->faker->unique()->numerify('########'),
            'user_id' => null,
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_phone' => $this->faker->phoneNumber(),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'bakong',
            'shipping_method' => 'standard',
            'subtotal' => $subtotal,
            'shipping_total' => 0,
            'discount_total' => 0,
            'grand_total' => $subtotal,
            'shipping_address' => [
                'address_line_1' => $this->faker->streetAddress(),
                'address_line_2' => null,
                'city' => $this->faker->city(),
                'province' => null,
                'country' => 'Cambodia',
            ],
            'notes' => null,
            'placed_at' => now(),
        ];
    }
}
