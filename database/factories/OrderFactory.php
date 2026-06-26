<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Package;
use App\Models\Router;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_reference' => Order::generateReference(),
            'customer_id' => Customer::factory(),
            'package_id' => Package::factory(),
            'router_id' => Router::factory(),
            'amount' => fake()->randomFloat(2, 500, 50000),
            'status' => 'pending',
            'payment_method' => 'pesapal',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }
}
