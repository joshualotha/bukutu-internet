<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $userCustomer = $request->user()->customer;

        if (! $userCustomer) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        // Find all sessions for this customer to get their devices
        $devices = Customer::where('phone_number', $userCustomer->phone_number)
            ->whereNotNull('device_name')
            ->select('mac_address', 'device_name', 'ip_address', 'created_at')
            ->orderByDesc('created_at')
            ->get()
            ->unique('mac_address')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }
}
