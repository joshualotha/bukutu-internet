<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\ActiveSession;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user()->customer;

        if (! $customer) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $sessions = ActiveSession::where('customer_id', $customer->id)
            ->with(['package', 'router'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $sessions,
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

        $session = ActiveSession::where('customer_id', $customer->id)
            ->with(['package', 'router', 'order'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $session,
        ]);
    }
}
