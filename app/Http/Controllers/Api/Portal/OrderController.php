<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    /**
     * Create a new order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mac_address' => 'required|string|max:17',
            'package_id' => 'required|integer|exists:packages,id',
            'phone_number' => 'required|string|max:20',
            'full_name' => 'nullable|string|max:255',
            'router_id' => 'nullable|integer|exists:routers,id',
        ]);

        try {
            $result = $this->orderService->createOrder(
                macAddress: $validated['mac_address'],
                packageId: $validated['package_id'],
                phoneNumber: $validated['phone_number'],
                fullName: $validated['full_name'] ?? null,
                routerId: $validated['router_id'] ?? null,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => OrderResource::make($result['order']->load(['package', 'customer'])),
                    'redirect_url' => $result['redirect_url'],
                ],
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('portal.error_occurred'),
            ], 500);
        }
    }

    /**
     * Check order status by reference.
     */
    public function show(string $reference)
    {
        try {
            $order = $this->orderService->checkOrderStatus($reference);

            return response()->json([
                'success' => true,
                'data' => OrderResource::make($order->load(['package', 'customer', 'payments'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('portal.order_not_found'),
            ], 404);
        }
    }
}
