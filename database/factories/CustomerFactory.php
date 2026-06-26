<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Router;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone_number' => fake()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'mac_address' => fake()->macAddress(),
            'ip_address' => fake()->ipv4(),
            'device_name' => fake()->optional()->randomElement(['iPhone', 'Android', 'Windows Laptop', 'MacBook']),
            'router_id' => Router::factory(),
        ];
    }
}
