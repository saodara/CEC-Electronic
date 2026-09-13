<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'company_name' => $this->faker->company() . ' Ltd',
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'address' => $this->faker->address(),
            'contact_person' => $this->faker->name(),
            'payment_terms' => 'Net 30',
            'is_active' => true,
            'notes' => null,
        ];
    }
}
