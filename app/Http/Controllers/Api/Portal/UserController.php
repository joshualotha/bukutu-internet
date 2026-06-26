<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get user info by MAC address.
     */
    public function showByMac(string $mac)
    {
        $customer = Customer::where('mac_address', $mac)->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => __('portal.user_not_found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    /**
     * Update user profile.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'mac_address' => 'required|string|max:17',
            'full_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'device_name' => 'nullable|string|max:255',
        ]);

        $customer = Customer::where('mac_address', $validated['mac_address'])->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => __('portal.user_not_found'),
            ], 404);
        }

        $customer->update(array_filter([
            'full_name' => $validated['full_name'] ?? $customer->full_name,
            'phone_number' => $validated['phone_number'] ?? $customer->phone_number,
            'email' => $validated['email'] ?? $customer->email,
            'device_name' => $validated['device_name'] ?? $customer->device_name,
        ]));

        return response()->json([
            'success' => true,
            'data' => $customer->fresh(),
            'message' => __('portal.success'),
        ]);
    }
}
