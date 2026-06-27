<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActiveSessionResource;
use App\Models\ActiveSession;
use App\Models\Customer;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    /**
     * Get active session by MAC address.
     */
    public function showByMac(string $mac)
    {
        $customer = Customer::where('mac_address', $mac)->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => __('portal.session_not_found'),
            ], 404);
        }

        $session = ActiveSession::where('customer_id', $customer->id)
            ->with(['package', 'order'])
            ->latest()
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => __('portal.session_not_found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session' => ActiveSessionResource::make($session),
                'time_remaining' => $session->timeRemaining(),
                'is_active' => $session->isActive(),
            ],
        ]);
    }

    /**
     * Check if a MAC address is authorized.
     */
    public function checkAuth(Request $request)
    {
        $validated = $request->validate([
            'mac_address' => 'required|string|max:17',
        ]);

        $customer = Customer::where('mac_address', $validated['mac_address'])->first();

        if (! $customer) {
            return response()->json([
                'success' => true,
                'authorized' => false,
            ]);
        }

        $activeSession = ActiveSession::where('customer_id', $customer->id)
            ->active()
            ->where('expiry_time', '>', now())
            ->with('package')
            ->first();

        return response()->json([
            'success' => true,
            'authorized' => $activeSession !== null,
            'session' => $activeSession ? ActiveSessionResource::make($activeSession) : null,
        ]);
    }
}
