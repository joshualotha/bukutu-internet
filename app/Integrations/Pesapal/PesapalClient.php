<?php

namespace App\Integrations\Pesapal;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PesapalClient
{
    private string $baseUrl;

    private string $consumerKey;

    private string $consumerSecret;

    private string $callbackUrl;

    private string $ipnId;

    private string $currency;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('pesapal.base_url', 'https://pay.pesapal.com/v3'), '/');
        $this->consumerKey = config('pesapal.consumer_key');
        $this->consumerSecret = config('pesapal.consumer_secret');
        $this->callbackUrl = config('pesapal.callback_url');
        $this->ipnId = config('pesapal.ipn_id');
        $this->currency = config('pesapal.currency', 'TZS');
    }

    /**
     * Get OAuth2 access token from Pesapal.
     * Tokens are cached until expiry.
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'pesapal_access_token';

        return Cache::remember($cacheKey, now()->addMinutes(55), function () {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/api/Auth/RequestToken", [
                'consumer_key' => $this->consumerKey,
                'consumer_secret' => $this->consumerSecret,
            ]);

            if ($response->failed()) {
                Log::error('Pesapal auth failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new PesapalAuthenticationException(
                    'Failed to authenticate with Pesapal: ' . $response->body()
                );
            }

            $data = $response->json();

            if (! isset($data['token'])) {
                throw new PesapalAuthenticationException('No token returned from Pesapal');
            }

            return $data['token'];
        });
    }

    /**
     * Get a fresh access token (bypass cache).
     */
    public function refreshAccessToken(): string
    {
        Cache::forget('pesapal_access_token');

        return $this->getAccessToken();
    }

    /**
     * Make an authenticated request to the Pesapal API.
     */
    private function authenticatedRequest(string $method, string $endpoint, array $data = []): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$token}",
        ])->{$method}("{$this->baseUrl}{$endpoint}", $data);

        return $this->handleResponse($response, $method, $endpoint);
    }

    /**
     * Handle API response, with token refresh on 401.
     */
    private function handleResponse($response, string $method, string $endpoint): array
    {
        // If unauthorized, refresh token and retry once
        if ($response->status() === 401) {
            $token = $this->refreshAccessToken();

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$token}",
            ])->{$method}("{$this->baseUrl}{$endpoint}", $method === 'get' ? [] : $response->body());
        }

        if ($response->failed()) {
            Log::error('Pesapal API request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new PesapalRequestException(
                "Pesapal API error: {$response->status()} - {$response->body()}"
            );
        }

        return $response->json() ?? [];
    }

    /**
     * Register an IPN URL with Pesapal.
     */
    public function registerIpn(string $url): array
    {
        return $this->authenticatedRequest('post', '/api/URLSetup/RegisterIPN', [
            'url' => $url,
            'ipn_notification_type' => 'GET',
        ]);
    }

    /**
     * Get list of registered IPNs.
     */
    public function getRegisteredIpns(): array
    {
        return $this->authenticatedRequest('get', '/api/URLSetup/GetIpnList');
    }

    /**
     * Submit an order request to Pesapal.
     *
     * @param array $orderData [
     *     'id' => string,        // Unique order reference
     *     'amount' => float,
     *     'description' => string,
     *     'phone_number' => string,
     *     'first_name' => string,
     *     'last_name' => string,
     *     'email' => string|null,
     * ]
     * @return array ['redirect_url' => string, 'order_tracking_id' => string, 'merchant_reference' => string]
     */
    public function submitOrder(array $orderData): array
    {
        if (empty($this->ipnId)) {
            throw new PesapalConfigurationException(
                'Pesapal IPN ID is not configured. Register an IPN URL first.'
            );
        }

        $payload = [
            'id' => $orderData['id'],
            'currency' => $this->currency,
            'amount' => (float) $orderData['amount'],
            'description' => $orderData['description'] ?? 'Internet Package Purchase',
            'callback_url' => $this->callbackUrl,
            'notification_id' => $this->ipnId,
            'billing_address' => [
                'phone_number' => $orderData['phone_number'] ?? '',
                'first_name' => $orderData['first_name'] ?? 'Valued',
                'last_name' => $orderData['last_name'] ?? 'Customer',
                'email_address' => $orderData['email'] ?? '',
                'country_code' => 'TZ',
            ],
        ];

        $result = $this->authenticatedRequest('post', '/api/Transactions/SubmitOrderRequest', $payload);

        return [
            'redirect_url' => $result['redirect_url'] ?? '',
            'order_tracking_id' => $result['order_tracking_id'] ?? '',
            'merchant_reference' => $result['merchant_reference'] ?? '',
        ];
    }

    /**
     * Get transaction status from Pesapal.
     */
    public function getTransactionStatus(string $orderTrackingId): array
    {
        return $this->authenticatedRequest(
            'get',
            "/api/Transactions/GetTransactionStatus?orderTrackingId={$orderTrackingId}"
        );
    }

    /**
     * Verify the authenticity of an IPN request.
     * Checks that the request contains valid tracking IDs.
     */
    public function verifyIpnRequest(array $payload): bool
    {
        // Pesapal sends tracking info in the IPN payload
        // We should validate that the order_tracking_id exists
        $trackingId = $payload['OrderTrackingId'] ?? $payload['order_tracking_id'] ?? null;
        $merchantRef = $payload['OrderMerchantReference'] ?? $payload['merchant_reference'] ?? null;

        if (! $trackingId || ! $merchantRef) {
            return false;
        }

        // Optionally verify by calling getTransactionStatus
        try {
            $status = $this->getTransactionStatus($trackingId);
            // If we got a response, the tracking ID is valid
            return ! empty($status);
        } catch (\Exception $e) {
            return false;
        }
    }
}
