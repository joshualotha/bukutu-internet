<?php

use App\Integrations\Pesapal\PesapalClient;
use Illuminate\Support\Facades\Http;

/**
 * End-to-end integration test for Pesapal.
 * 
 * These tests call the REAL Pesapal sandbox API to verify credentials
 * and API endpoints are working correctly.
 * 
 * They are skipped unless PESAPAL_CONSUMER_KEY is set.
 */

beforeEach(function () {
    $this->client = app(PesapalClient::class);
});

it('can get an access token from the real Pesapal sandbox', function () {
    $key = config('pesapal.consumer_key');
    if (empty($key) || $key === 'your_consumer_key_here') {
        $this->markTestSkipped('No Pesapal credentials configured');
    }

    $token = $this->client->getAccessToken();

    expect($token)->toBeString();
    expect(strlen($token))->toBeGreaterThan(100);
});

it('can submit an order to the real Pesapal sandbox', function () {
    $key = config('pesapal.consumer_key');
    if (empty($key) || $key === 'your_consumer_key_here') {
        $this->markTestSkipped('No Pesapal credentials configured');
    }

    $uniqueId = 'TEST-' . date('YmdHis') . '-' . substr(uniqid(), -4);
    $result = $this->client->submitOrder([
        'id' => $uniqueId,
        'amount' => 2000,
        'description' => 'Integration Test',
        'phone_number' => '+256701234567',
        'first_name' => 'Integration',
        'last_name' => 'Test',
        'email' => 'test@bukutu.co.tz',
    ]);

    expect($result)->toHaveKeys(['order_tracking_id', 'merchant_reference', 'redirect_url']);
    expect($result['redirect_url'])->toContain('pay.pesapal.com');
});

it('throws an error checking status for unpaid orders on the real Pesapal sandbox', function () {
    $key = config('pesapal.consumer_key');
    if (empty($key) || $key === 'your_consumer_key_here') {
        $this->markTestSkipped('No Pesapal credentials configured');
    }

    // Submit an order first to get a tracking ID
    $uniqueId = 'TSTATUS-' . date('YmdHis') . '-' . substr(uniqid(), -4);
    $result = $this->client->submitOrder([
        'id' => $uniqueId,
        'amount' => 1000,
        'description' => 'Status Check Test',
        'phone_number' => '+256701234567',
        'first_name' => 'Status',
        'last_name' => 'Check',
        'email' => 'status@bukutu.co.tz',
    ]);

    // Pesapal returns 404 HTML for unpaid/pending orders
    // This is expected behavior - status endpoint only returns JSON for
    // completed/failed transactions
    expect(fn () => $this->client->getTransactionStatus($result['order_tracking_id']))
        ->toThrow(\App\Integrations\Pesapal\PesapalRequestException::class);
});
