<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Integrations\Pesapal\PesapalClient;
use App\Models\Order;
use App\Models\PesapalWebhookLog;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PesapalIpnController extends Controller
{
    public function __construct(
        private readonly PesapalClient $pesapalClient,
        private readonly OrderService $orderService,
    ) {}

    /**
     * Handle incoming IPN from Pesapal.
     *
     * Pesapal sends a POST/GET request to this endpoint after payment.
     * We must respond quickly with 200, then process asynchronously.
     */
    public function handle(Request $request)
    {
        // Log the entire IPN request for auditing
        $payload = $request->all();

        $webhookLog = PesapalWebhookLog::create([
            'payload' => $payload,
            'ipn_type' => $payload['ipn_type'] ?? $payload['IPN_TYPE'] ?? null,
            'processed' => false,
        ]);

        try {
            // Extract tracking IDs from the payload
            // Pesapal v3 sends these in different formats depending on IPN type
            $trackingId = $payload['OrderTrackingId']
                ?? $payload['order_tracking_id']
                ?? $payload['OrderNotificationData']['OrderTrackingId']
                ?? null;

            $merchantReference = $payload['OrderMerchantReference']
                ?? $payload['merchant_reference']
                ?? $payload['OrderNotificationData']['MerchantReference']
                ?? null;

            // If no tracking ID, log and return
            if (! $trackingId) {
                Log::warning('Pesapal IPN received without tracking ID', [
                    'webhook_log_id' => $webhookLog->id,
                    'payload' => $payload,
                ]);

                $webhookLog->update([
                    'processed' => true,
                    'error_message' => 'No tracking ID in payload',
                ]);

                return response('OK - No tracking ID', 200);
            }

            // Find the order by tracking ID or merchant reference
            $order = Order::where('pesapal_tracking_id', $trackingId)
                ->orWhere('pesapal_merchant_ref', $merchantReference)
                ->orWhere('order_reference', $merchantReference)
                ->first();

            if (! $order) {
                Log::warning('Pesapal IPN for unknown order', [
                    'webhook_log_id' => $webhookLog->id,
                    'tracking_id' => $trackingId,
                    'merchant_reference' => $merchantReference,
                ]);

                $webhookLog->update([
                    'processed' => true,
                    'error_message' => 'Order not found',
                ]);

                return response('OK - Order not found', 200);
            }

            // Verify IPN by calling Pesapal to get transaction status
            try {
                $status = $this->pesapalClient->getTransactionStatus($trackingId);

                $this->orderService->processPaymentStatus($order, $status);

                $webhookLog->update([
                    'processed' => true,
                ]);

                Log::info('Pesapal IPN processed successfully', [
                    'webhook_log_id' => $webhookLog->id,
                    'order_id' => $order->id,
                    'tracking_id' => $trackingId,
                    'payment_status' => $status['status'] ?? 'unknown',
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to verify IPN with Pesapal', [
                    'webhook_log_id' => $webhookLog->id,
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                $webhookLog->update([
                    'processed' => true,
                    'error_message' => $e->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Pesapal IPN handler exception', [
                'webhook_log_id' => $webhookLog->id,
                'error' => $e->getMessage(),
            ]);

            $webhookLog->update([
                'processed' => true,
                'error_message' => $e->getMessage(),
            ]);
        }

        // Always return 200 to acknowledge receipt
        return response('OK', 200);
    }
}
