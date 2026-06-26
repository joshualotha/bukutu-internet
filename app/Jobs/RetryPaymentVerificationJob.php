<?php

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Integrations\Pesapal\PesapalClient;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RetryPaymentVerificationJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Retry verification for stale pending orders.
     */
    public function handle(PesapalClient $pesapalClient, OrderService $orderService): void
    {
        $staleOrders = Order::stalePending(30)->get();

        $count = 0;

        foreach ($staleOrders as $order) {
            try {
                $status = $pesapalClient->getTransactionStatus($order->pesapal_tracking_id);

                $orderService->processPaymentStatus($order, $status);

                $count++;
            } catch (\Exception $e) {
                Log::warning('RetryPaymentVerificationJob: Failed to verify order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($count > 0) {
            Log::info("RetryPaymentVerificationJob: Processed {$count} stale orders");
        }
    }
}
