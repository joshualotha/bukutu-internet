<?php

namespace Database\Factories;

use App\Models\Router;
use Illuminate\Database\Eloquent\Factories\Factory;

class RouterFactory extends Factory
{
    protected $model = Router::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word() . '-Router',
            'ip_address' => fake()->ipv4(),
            'api_port' => 8728,
            'username' => 'admin',
            'password' => encrypt('password'),
            'location' => fake()->optional()->city(),
            'is_active' => true,
            'connection_status' => 'unknown',
        ];
    }

    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'connection_status' => 'online',
            'last_seen_at' => now(),
        ]);
    }
}
