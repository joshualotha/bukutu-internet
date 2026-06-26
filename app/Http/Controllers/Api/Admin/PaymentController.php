<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\OrderService;

class PaymentController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function refund(Payment $payment)
    {
        $order = $payment->order;

        try {
            $this->orderService->refundOrder($order);

            $payment->update(['status' => 'refunded']);

            return response()->json([
                'success' => true,
                'message' => 'Payment refunded successfully',
                'data' => $payment->fresh(),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
