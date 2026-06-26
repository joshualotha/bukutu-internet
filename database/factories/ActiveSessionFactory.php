<?php

namespace Database\Factories;

use App\Models\ActiveSession;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Package;
use App\Models\Router;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActiveSessionFactory extends Factory
{
    protected $model = ActiveSession::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'order_id' => Order::factory(),
            'package_id' => Package::factory(),
            'router_id' => Router::factory(),
            'mac_address' => fake()->macAddress(),
            'mikrotik_username' => 'bt_' . str_replace(':', '', strtolower(fake()->macAddress())),
            'mikrotik_profile' => '1M_1M',
            'start_time' => now()->subHours(1),
            'expiry_time' => now()->addHours(1),
            'status' => 'active',
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expiry_time' => now()->subMinutes(10),
            'disconnected_at' => now()->subMinutes(5),
        ]);
    }
}
