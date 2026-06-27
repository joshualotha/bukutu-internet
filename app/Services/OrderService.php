<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Integrations\Pesapal\PesapalClient;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Package;
use App\Models\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private readonly PesapalClient $pesapalClient,
        private readonly SessionService $sessionService,
    ) {}

    /**
     * Create a new order and submit to Pesapal.
     *
     * @return array{order: Order, redirect_url: string|null}
     */
    public function createOrder(string $macAddress, int $packageId, string $phoneNumber, ?string $fullName = null, ?int $routerId = null): array
    {
        return DB::transaction(function () use ($macAddress, $packageId, $phoneNumber, $fullName, $routerId) {
            $package = Package::findOrFail($packageId);

            if (! $package->is_active) {
                throw new \RuntimeException('Package is not available');
            }

            // Find or create customer by MAC
            $customer = Customer::firstOrCreate(
                ['mac_address' => $macAddress],
                [
                    'phone_number' => $phoneNumber,
                    'full_name' => $fullName,
                    'router_id' => $routerId,
                ]
            );

            // Update customer info if provided
            if ($phoneNumber && empty($customer->phone_number)) {
                $customer->phone_number = $phoneNumber;
            }
            if ($fullName) {
                $customer->full_name = $fullName;
            }
            if ($routerId) {
                $customer->router_id = $routerId;
            }
            $customer->save();

            // Create order
            $order = Order::create([
                'customer_id' => $customer->id,
                'package_id' => $package->id,
                'router_id' => $routerId,
                'amount' => $package->price,
                'status' => PaymentStatus::PENDING,
            ]);

            // Submit to Pesapal
            try {
                $name = explode(' ', $fullName ?? 'Valued Customer', 2);
                $firstName = $name[0] ?? 'Valued';
                $lastName = $name[1] ?? 'Customer';

                $pesapalResult = $this->pesapalClient->submitOrder([
                    'id' => $order->order_reference,
                    'amount' => $package->price,
                    'description' => "{$package->name} - Internet Package",
                    'phone_number' => $phoneNumber,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $customer->email,
                ]);

                // Update order with Pesapal tracking info
                $order->update([
                    'pesapal_tracking_id' => $pesapalResult['order_tracking_id'],
                    'pesapal_merchant_ref' => $pesapalResult['merchant_reference'],
                ]);

                // Create payment record
                $order->payments()->create([
                    'amount' => $package->price,
                    'currency' => config('pesapal.currency', 'TZS'),
                    'provider' => 'pesapal',
                    'provider_tracking_id' => $pesapalResult['order_tracking_id'],
                    'provider_reference' => $pesapalResult['merchant_reference'],
                    'payment_method' => 'pesapal',
                    'phone_number' => $phoneNumber,
                    'status' => PaymentStatus::PENDING,
                ]);

                return [
                    'order' => $order->fresh(),
                    'redirect_url' => $pesapalResult['redirect_url'],
                ];
            } catch (\Exception $e) {
                Log::error('Failed to submit order to Pesapal', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                // Order is created but payment submission failed
                $order->update(['status' => PaymentStatus::FAILED]);

                throw $e;
            }
        });
    }

    /**
     * Check order status.
     */
    public function checkOrderStatus(string $orderReference): Order
    {
        $order = Order::where('order_reference', $orderReference)
            ->with(['package', 'customer', 'payments'])
            ->firstOrFail();

        // If order is still pending and has a Pesapal tracking ID, verify with Pesapal
        if ($order->status === PaymentStatus::PENDING && $order->pesapal_tracking_id) {
            try {
                $status = $this->pesapalClient->getTransactionStatus($order->pesapal_tracking_id);

                $this->processPaymentStatus($order, $status);
            } catch (\Exception $e) {
                Log::warning('Failed to check order status with Pesapal', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $order->fresh();
    }

    /**
     * Process a successful payment.
     */
    public function processSuccessfulPayment(Order $order, array $paymentData = []): void
    {
        DB::transaction(function () use ($order, $paymentData) {
            if ($order->status === PaymentStatus::PAID) {
                Log::warning('Order already paid, skipping activation', [
                    'order_id' => $order->id,
                ]);

                return;
            }

            $order->update([
                'status' => PaymentStatus::PAID,
                'paid_at' => now(),
                'transaction_reference' => $paymentData['transaction_reference'] ?? $order->transaction_reference,
            ]);

            // Update the payment record
            $payment = $order->payments()->latest()->first();
            if ($payment) {
                $payment->update([
                    'status' => PaymentStatus::PAID,
                    'provider_reference' => $paymentData['provider_reference'] ?? $payment->provider_reference,
                    'confirmation_code' => $paymentData['confirmation_code'] ?? null,
                    'payment_time' => now(),
                    'response_payload' => $paymentData,
                ]);
            }

            // Activate session on MikroTik
            $router = $order->router ?? Router::find($order->customer->router_id);
            if (! $router) {
                Log::error('No router found for order activation', [
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                ]);

                return;
            }

            $this->sessionService->activateSession(
                $order,
                $router
            );
        });
    }

    /**
     * Process payment status from Pesapal verification.
     */
    public function processPaymentStatus(Order $order, array $status): void
    {
        $pesapalStatus = $status['status'] ?? $status['payment_status_description'] ?? '';

        switch (strtolower($pesapalStatus)) {
            case 'completed':
            case 'paid':
            case 'success':
                $this->processSuccessfulPayment($order, $status);
                break;

            case 'failed':
            case 'declined':
                $order->update(['status' => PaymentStatus::FAILED]);
                break;

            case 'cancelled':
                $order->update(['status' => PaymentStatus::FAILED]);
                break;

            case 'pending':
                // Still pending, do nothing
                break;

            default:
                Log::warning('Unknown Pesapal payment status', [
                    'order_id' => $order->id,
                    'status' => $pesapalStatus,
                ]);
                break;
        }
    }

    /**
     * Mark order as expired.
     */
    public function expireOrder(Order $order): void
    {
        if ($order->status === PaymentStatus::PENDING) {
            $order->update([
                'status' => PaymentStatus::EXPIRED,
                'expired_at' => now(),
            ]);
        }
    }

    /**
     * Refund an order (marks as refunded, does not actually refund via Pesapal).
     */
    public function refundOrder(Order $order): void
    {
        if ($order->status !== PaymentStatus::PAID) {
            throw new \RuntimeException('Only paid orders can be refunded');
        }

        $order->update(['status' => PaymentStatus::REFUNDED]);
    }
}
