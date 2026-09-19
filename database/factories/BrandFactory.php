<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BrandFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->lexify('????')) . ' ' . $this->faker->unique()->lexify('???');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'logo' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
