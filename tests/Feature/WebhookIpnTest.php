<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Package;
use Illuminate\Support\Facades\Http;

uses()->group('webhook');

it('accepts a valid IPN payload and returns 200', function () {
    $customer = Customer::factory()->create();
    $package = Package::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'pesapal_tracking_id' => 'tracking-123',
        'pesapal_merchant_ref' => 'merchant-ref-123',
        'status' => 'pending',
    ]);

    // Mock both Pesapal and any MikroTik HTTP calls
    Http::fake([
        'https://pay.pesapal.com/v3/api/Auth/RequestToken' => Http::response([
            'token' => 'test-token',
        ]),
        'https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus*' => Http::response([
            'status' => 'completed',
            'payment_method' => 'mobile_money',
            'amount' => $order->amount,
        ]),
        '*' => Http::response([], 200),
    ]);

    $payload = [
        'OrderTrackingId' => 'tracking-123',
        'OrderMerchantReference' => 'merchant-ref-123',
        'ipn_type' => 'payment_complete',
    ];

    $response = $this->postJson('/webhook/pesapal/ipn', $payload);

    $response->assertStatus(200);

    // Order should be paid since the mocked Pesapal response returns 'completed'
    $order->refresh();
    expect($order->status->value)->toBe('paid');
});

it('returns 200 for unknown tracking ID', function () {
    $payload = [
        'OrderTrackingId' => 'unknown-tracking-id',
        'OrderMerchantReference' => 'unknown-ref',
    ];

    $response = $this->postJson('/webhook/pesapal/ipn', $payload);

    $response->assertStatus(200);
});

it('returns 200 when no tracking ID is provided', function () {
    $response = $this->postJson('/webhook/pesapal/ipn', [
        'some_other_data' => 'value',
    ]);

    $response->assertStatus(200);
});

it('logs webhook payloads', function () {
    $payload = [
        'OrderTrackingId' => 'tracking-456',
        'OrderMerchantReference' => 'ref-456',
        'test_data' => 'should be logged',
    ];

    $this->postJson('/webhook/pesapal/ipn', $payload);

    $this->assertDatabaseHas('pesapal_webhook_logs', [
        'processed' => true,
    ]);
});

it('does not double-activate for duplicate IPN', function () {
    $customer = Customer::factory()->create();
    $package = Package::factory()->create();
    $router = App\Models\Router::factory()->create();

    // Create an order that is already paid
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'router_id' => $router->id,
        'pesapal_tracking_id' => 'dup-tracking-123',
        'pesapal_merchant_ref' => 'dup-merchant-ref',
        'status' => 'paid',
    ]);

    // Mock Pesapal to return completed (but order is already paid)
    Http::fake([
        'https://pay.pesapal.com/v3/api/Auth/RequestToken' => Http::response([
            'token' => 'test-token',
        ]),
        'https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus*' => Http::response([
            'status' => 'completed',
            'payment_method' => 'mobile_money',
            'amount' => $order->amount,
        ]),
        '*' => Http::response([], 200),
    ]);

    $payload = [
        'OrderTrackingId' => 'dup-tracking-123',
        'OrderMerchantReference' => 'dup-merchant-ref',
        'ipn_type' => 'payment_complete',
    ];

    $response = $this->postJson('/webhook/pesapal/ipn', $payload);

    $response->assertStatus(200);

    // Order should still be paid (not double-activated)
    $order->refresh();
    expect($order->status->value)->toBe('paid');

    // No MikroTik call should have been made (order was already paid, so processSuccessfulPayment skipped)
    // No ActiveSession should be created
    expect(App\Models\ActiveSession::count())->toBe(0);
});

it('marks order as failed when Pesapal returns failed status', function () {
    $customer = Customer::factory()->create();
    $package = Package::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'pesapal_tracking_id' => 'failed-tracking',
        'pesapal_merchant_ref' => 'failed-merchant-ref',
        'status' => 'pending',
    ]);

    // Mock Pesapal to return 'failed' status
    Http::fake([
        'https://pay.pesapal.com/v3/api/Auth/RequestToken' => Http::response([
            'token' => 'test-token',
        ]),
        'https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus*' => Http::response([
            'status' => 'failed',
            'payment_method' => 'mobile_money',
            'amount' => $order->amount,
        ]),
    ]);

    $payload = [
        'OrderTrackingId' => 'failed-tracking',
        'OrderMerchantReference' => 'failed-merchant-ref',
        'ipn_type' => 'payment_failed',
    ];

    $response = $this->postJson('/webhook/pesapal/ipn', $payload);

    $response->assertStatus(200);

    // Order should be marked as failed
    $order->refresh();
    expect($order->status->value)->toBe('failed');

    // No ActiveSession should be created for a failed payment
    expect(App\Models\ActiveSession::count())->toBe(0);
});

it('handles IPN with invalid/missing tracking IDs gracefully', function () {
    // Send a payload without proper tracking fields
    $response = $this->postJson('/webhook/pesapal/ipn', [
        'some_random_data' => 'no tracking info',
        'ipn_type' => 'unknown',
    ]);

    $response->assertStatus(200);

    // The webhook log should indicate the error
    $this->assertDatabaseHas('pesapal_webhook_logs', [
        'processed' => true,
        'error_message' => 'No tracking ID in payload',
    ]);
});
