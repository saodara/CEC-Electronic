<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryProviderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company() . ' Express',
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->companyEmail(),
            'tracking_url' => $this->faker->url(),
            'base_fee' => $this->faker->numberBetween(0, 10),
            'is_active' => true,
            'notes' => null,
        ];
    }
}
