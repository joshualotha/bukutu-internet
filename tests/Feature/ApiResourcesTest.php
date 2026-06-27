<?php

use App\Http\Resources\ActiveSessionResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PackageResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\RouterResource;
use App\Models\ActiveSession;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Router;

uses()->group('api-resources');

it('PackageResource returns correct structure', function () {
    $package = Package::factory()->create();

    $resource = PackageResource::make($package)->resolve();

    expect($resource)->toHaveKeys(['id', 'name', 'description', 'price', 'duration_minutes', 'upload_speed', 'download_speed', 'is_active']);
    expect($resource['name'])->toBe($package->name);
    expect($resource['price'])->toBe((float) $package->price);
});

it('CustomerResource returns correct structure', function () {
    $customer = Customer::factory()->create();

    $resource = CustomerResource::make($customer)->resolve();

    expect($resource)->toHaveKeys(['id', 'full_name', 'phone_number', 'mac_address', 'ip_address']);
    expect($resource['full_name'])->toBe($customer->full_name);
});

it('RouterResource returns correct structure', function () {
    $router = Router::factory()->create();

    $resource = RouterResource::make($router)->resolve();

    expect($resource)->toHaveKeys(['id', 'name', 'ip_address', 'api_port', 'is_active', 'connection_status']);
    expect($resource['name'])->toBe($router->name);
});

it('OrderResource returns correct structure', function () {
    $order = Order::factory()->create();

    $resource = OrderResource::make($order)->resolve();

    expect($resource)->toHaveKeys(['id', 'order_reference', 'amount', 'status', 'created_at']);
    expect($resource['order_reference'])->toBe($order->order_reference);
});

it('ActiveSessionResource returns correct structure', function () {
    $session = ActiveSession::factory()->create();

    $resource = ActiveSessionResource::make($session)->resolve();

    expect($resource)->toHaveKeys(['id', 'mac_address', 'start_time', 'expiry_time', 'status', 'is_active', 'time_remaining_seconds']);
    expect($resource['mac_address'])->toBe($session->mac_address);
});

it('PaymentResource returns correct structure', function () {
    $payment = Payment::factory()->create();

    $resource = PaymentResource::make($payment)->resolve();

    expect($resource)->toHaveKeys(['id', 'order_id', 'amount', 'provider', 'payment_method', 'status']);
    expect($resource['amount'])->toBe((float) $payment->amount);
});
