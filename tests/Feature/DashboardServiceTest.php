<?php

use App\Enums\PaymentStatus;
use App\Models\ActiveSession;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Package;
use App\Models\Router;
use App\Services\DashboardService;

uses()->group('services');

beforeEach(function () {
    $this->dashboardService = app(DashboardService::class);
});

it('returns dashboard metrics', function () {
    $package = Package::factory()->create(['price' => 5000]);
    $customer = Customer::factory()->create();
    $router = Router::factory()->create(['is_active' => true, 'connection_status' => 'online']);

    Order::factory()->count(3)->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'amount' => 5000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    ActiveSession::factory()->count(2)->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'status' => 'active',
    ]);

    $metrics = $this->dashboardService->getMetrics();

    expect($metrics['total_customers'])->toBeGreaterThanOrEqual(1);
    expect($metrics['active_sessions'])->toBeGreaterThanOrEqual(2);
    expect($metrics['revenue_today'])->toBeGreaterThanOrEqual(15000);
    expect($metrics['total_routers'])->toBeGreaterThanOrEqual(1);
});

it('returns chart data', function () {
    $package = Package::factory()->create(['price' => 5000, 'name' => 'Test Package']);
    $customer = Customer::factory()->create();

    Order::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'amount' => 5000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $charts = $this->dashboardService->getCharts();

    expect($charts)->toHaveKeys(['revenue_chart', 'active_users_chart', 'popular_packages']);
    expect($charts['popular_packages']['labels'])->toContain('Test Package');
});

it('returns recent orders', function () {
    $package = Package::factory()->create();
    $customer = Customer::factory()->create();

    Order::factory()->count(5)->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
    ]);

    $recentOrders = $this->dashboardService->getRecentOrders(3);

    expect($recentOrders)->toHaveCount(3);
});

it('returns router status', function () {
    Router::factory()->count(2)->create([
        'is_active' => true,
        'connection_status' => 'online',
    ]);
    Router::factory()->create([
        'is_active' => false,
        'connection_status' => 'offline',
    ]);

    $status = $this->dashboardService->getRouterStatus();

    expect($status)->toHaveCount(2);
});
