<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryZoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->city() . ' Zone',
            'city' => $this->faker->city(),
            'province' => $this->faker->state(),
            'delivery_fee' => $this->faker->numberBetween(0, 10),
            'free_delivery_minimum' => 50,
            'estimated_days' => $this->faker->numberBetween(1, 5),
            'is_active' => true,
        ];
    }
}
