<?php

use App\Enums\PaymentStatus;
use App\Models\ActiveSession;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payment;
use App\Services\ReportService;

uses()->group('services');

beforeEach(function () {
    $this->reportService = app(ReportService::class);
});

it('calculates daily revenue correctly', function () {
    $package = Package::factory()->create(['price' => 10000]);
    $customer = Customer::factory()->create();

    Order::factory()->count(3)->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'amount' => 10000,
        'status' => 'paid',
        'paid_at' => today(),
    ]);

    // Create an order from yesterday (should not count)
    Order::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'amount' => 10000,
        'status' => 'paid',
        'paid_at' => today()->subDay(),
    ]);

    $revenue = $this->reportService->dailyRevenue(today());

    expect($revenue)->toBe(30000.0);
});

it('calculates monthly revenue correctly', function () {
    $package = Package::factory()->create(['price' => 15000]);
    $customer = Customer::factory()->create();

    Order::factory()->count(2)->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'amount' => 15000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $revenue = $this->reportService->monthlyRevenue(now()->month, now()->year);

    expect($revenue)->toBe(30000.0);
});

it('returns popular packages sorted by order count', function () {
    $popular = Package::factory()->create(['name' => 'Popular Package']);
    $unpopular = Package::factory()->create(['name' => 'Unpopular Package']);
    $customer = Customer::factory()->create();

    // 5 orders for the popular package
    Order::factory()->count(5)->create([
        'customer_id' => $customer->id,
        'package_id' => $popular->id,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    // 1 order for the unpopular package
    Order::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => $unpopular->id,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $packages = $this->reportService->popularPackages();

    expect($packages->count())->toBeGreaterThanOrEqual(2);
    expect((int) $packages->first()->total_orders)->toBe(5);
});

it('calculates customer retention metrics', function () {
    $package = Package::factory()->create();

    // Returning customer (2 orders)
    $returning = Customer::factory()->create();
    Order::factory()->count(2)->create([
        'customer_id' => $returning->id,
        'package_id' => $package->id,
        'status' => 'paid',
    ]);

    // One-time customers
    Customer::factory()->count(3)->create()->each(function ($customer) use ($package) {
        Order::factory()->create([
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'status' => 'paid',
        ]);
    });

    $retention = $this->reportService->customerRetention();

    expect($retention['total_customers'])->toBe(4);
    expect($retention['returning_customers'])->toBe(1);
    expect($retention['one_time_customers'])->toBe(3);
    expect($retention['retention_rate'])->toBe(25.0);
});

it('returns active users grouped by day', function () {
    $customer = Customer::factory()->create();
    $package = Package::factory()->create();

    ActiveSession::factory()->count(3)->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'start_time' => today(),
        'status' => 'active',
    ]);

    $activeUsers = $this->reportService->activeUsersByDay('30_days');

    expect($activeUsers->count())->toBeGreaterThanOrEqual(1);
});

it('returns failed payments for a given period', function () {
    $customer = Customer::factory()->create();
    $package = Package::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'status' => 'failed',
    ]);

    Payment::factory()->count(2)->create([
        'order_id' => $order->id,
        'status' => 'failed',
    ]);

    $failed = $this->reportService->failedPayments('30_days');

    expect($failed->count())->toBe(2);
});

it('returns device usage statistics', function () {
    Customer::factory()->create(['device_name' => 'iPhone']);
    Customer::factory()->create(['device_name' => 'iPhone']);
    Customer::factory()->create(['device_name' => 'Android']);

    $devices = $this->reportService->deviceUsage();

    expect($devices->count())->toBeGreaterThanOrEqual(2);
});

it('returns revenue by day for charting', function () {
    $package = Package::factory()->create(['price' => 5000]);
    $customer = Customer::factory()->create();

    Order::factory()->count(5)->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'amount' => 5000,
        'status' => 'paid',
        'paid_at' => today(),
    ]);

    $revenueByDay = $this->reportService->revenueByDay(30);

    expect($revenueByDay->count())->toBeGreaterThanOrEqual(1);

    $todayData = $revenueByDay->firstWhere('date', today()->toDateString());
    expect((float) ($todayData->revenue ?? 0))->toBe(25000.0);
});
