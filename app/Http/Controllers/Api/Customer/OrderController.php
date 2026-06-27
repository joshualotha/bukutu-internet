<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function index(Request $request)
    {
        $customer = $request->user()->customer;

        if (! $customer) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $orders = Order::where('customer_id', $customer->id)
            ->with(['package', 'payments'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
        ]);
    }

    public function show(Request $request, $id)
    {
        $customer = $request->user()->customer;

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        $order = Order::where('customer_id', $customer->id)
            ->with(['package', 'payments', 'activeSessions'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => OrderResource::make($order),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|integer|exists:packages,id',
            'phone_number' => 'required|string|max:20',
        ]);

        $customer = $request->user()->customer;

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer profile not found',
            ], 404);
        }

        try {
            $result = $this->orderService->createOrder(
                macAddress: $customer->mac_address,
                packageId: $validated['package_id'],
                phoneNumber: $validated['phone_number'],
                fullName: $customer->full_name,
                routerId: $customer->router_id,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => OrderResource::make($result['order']->load(['package', 'customer'])),
                    'redirect_url' => $result['redirect_url'],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
