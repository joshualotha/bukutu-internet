<?php

use App\Integrations\Pesapal\PesapalClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

uses()->group('pesapal');

beforeEach(function () {
    Config::set('pesapal.consumer_key', 'test_consumer_key');
    Config::set('pesapal.consumer_secret', 'test_consumer_secret');
    Config::set('pesapal.base_url', 'https://pay.pesapal.com/v3');
    Config::set('pesapal.callback_url', 'http://localhost/webhook/pesapal/ipn');
    Config::set('pesapal.ipn_id', 'test-ipn-id-123');
    Config::set('pesapal.currency', 'UGX');

    Cache::flush();
});

it('can get an access token', function () {
    Http::fake([
        'https://pay.pesapal.com/v3/api/Auth/RequestToken' => Http::response([
            'token' => 'test-access-token-123',
            'expiryDate' => now()->addHours(1)->toIso8601String(),
        ]),
    ]);

    $client = new PesapalClient();
    $token = $client->getAccessToken();

    expect($token)->toBe('test-access-token-123');
});

it('caches the access token', function () {
    Http::fake([
        'https://pay.pesapal.com/v3/api/Auth/RequestToken' => Http::response([
            'token' => 'test-access-token-123',
        ]),
    ]);

    $client = new PesapalClient();
    $token1 = $client->getAccessToken();
    $token2 = $client->getAccessToken();

    expect($token1)->toBe($token2);
    Http::assertSentCount(1);
});

it('can submit an order request', function () {
    Http::fake([
        'https://pay.pesapal.com/v3/api/Auth/RequestToken' => Http::response([
            'token' => 'access-token',
        ]),
        'https://pay.pesapal.com/v3/api/Transactions/SubmitOrderRequest' => Http::response([
            'redirect_url' => 'https://pay.pesapal.com/payment-link',
            'order_tracking_id' => 'tracking-id-123',
            'merchant_reference' => 'ORD-XXXXXXXX',
        ]),
    ]);

    $client = new PesapalClient();
    $result = $client->submitOrder([
        'id' => 'ORD-TEST123',
        'amount' => 5000,
        'description' => 'Test Package',
        'phone_number' => '0712345678',
        'first_name' => 'Test',
        'last_name' => 'User',
    ]);

    expect($result['redirect_url'])->toBe('https://pay.pesapal.com/payment-link')
        ->and($result['order_tracking_id'])->toBe('tracking-id-123')
        ->and($result['merchant_reference'])->toBe('ORD-XXXXXXXX');
});

it('can get transaction status', function () {
    Http::fake([
        'https://pay.pesapal.com/v3/api/Auth/RequestToken' => Http::response([
            'token' => 'access-token',
        ]),
        'https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus*' => Http::response([
            'status' => 'completed',
            'payment_method' => 'mobile_money',
            'amount' => 5000,
            'currency' => 'UGX',
        ]),
    ]);

    $client = new PesapalClient();
    $status = $client->getTransactionStatus('tracking-id-123');

    expect($status['status'])->toBe('completed');
});
