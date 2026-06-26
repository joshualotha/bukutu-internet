<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        $names = ['1 Hour', '2 Hours', 'Daily', 'Weekly', 'Monthly', '3 Days', '5 Hours'];

        return [
            'name' => fake()->unique()->randomElement($names),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->randomFloat(2, 500, 50000),
            'duration_minutes' => fake()->randomElement([60, 120, 360, 1440, 4320, 10080, 43800]),
            'upload_speed' => fake()->randomElement(['1M', '2M', '5M', '10M']),
            'download_speed' => fake()->randomElement(['2M', '5M', '10M', '20M']),
            'mikrotik_profile' => fake()->randomElement(['1M_1M', '2M_2M', '5M_5M', '10M_10M']),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
