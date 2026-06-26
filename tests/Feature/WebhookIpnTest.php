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

    Http::fake([
        'https://pay.pesapal.com/v3/api/Auth/RequestToken' => Http::response([
            'token' => 'test-token',
        ]),
        'https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus*' => Http::response([
            'status' => 'completed',
            'payment_method' => 'mobile_money',
            'amount' => $order->amount,
        ]),
    ]);

    $payload = [
        'OrderTrackingId' => 'tracking-123',
        'OrderMerchantReference' => 'merchant-ref-123',
        'ipn_type' => 'payment_complete',
    ];

    $response = $this->postJson('/webhook/pesapal/ipn', $payload);

    $response->assertStatus(200);

    // Order should still be pending since we mock the response from Pesapal
    $order->refresh();
    expect($order->status->value)->toBe('pending');
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
