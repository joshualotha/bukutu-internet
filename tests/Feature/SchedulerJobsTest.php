<?php

use App\Enums\SessionStatus;
use App\Jobs\ExpireSessionsJob;
use App\Models\ActiveSession;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Package;
use App\Models\Router;

uses()->group('scheduler');

it('expires sessions past their expiry time', function () {
    $router = Router::factory()->create();
    $customer = Customer::factory()->create(['router_id' => $router->id]);
    $package = Package::factory()->create();
    $order = Order::factory()->paid()->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
    ]);

    $session = ActiveSession::factory()->create([
        'customer_id' => $customer->id,
        'order_id' => $order->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'expiry_time' => now()->subMinutes(5),
        'status' => SessionStatus::ACTIVE,
    ]);

    (new ExpireSessionsJob())->handle();

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::EXPIRED)
        ->and($session->disconnected_at)->not->toBeNull();
});

it('does not expire active sessions that have not expired', function () {
    $router = Router::factory()->create();
    $customer = Customer::factory()->create(['router_id' => $router->id]);
    $package = Package::factory()->create();
    $order = Order::factory()->paid()->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
    ]);

    $session = ActiveSession::factory()->create([
        'customer_id' => $customer->id,
        'order_id' => $order->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'expiry_time' => now()->addHour(),
        'status' => SessionStatus::ACTIVE,
    ]);

    (new ExpireSessionsJob())->handle();

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::ACTIVE);
});
