<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActiveSession;
use App\Services\SessionService;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(
        private readonly SessionService $sessionService,
    ) {}

    public function suspend(ActiveSession $session)
    {
        try {
            $this->sessionService->suspendSession($session);

            return response()->json([
                'success' => true,
                'message' => 'Session suspended successfully',
                'data' => $session->fresh(),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function extend(Request $request, ActiveSession $session)
    {
        $validated = $request->validate([
            'minutes' => 'required|integer|min:1|max:43200', // max 30 days
        ]);

        $this->sessionService->extendSession($session, $validated['minutes']);

        return response()->json([
            'success' => true,
            'message' => "Session extended by {$validated['minutes']} minutes",
            'data' => $session->fresh(),
        ]);
    }

    public function disconnect(ActiveSession $session)
    {
        $this->sessionService->disconnectSession($session);

        return response()->json([
            'success' => true,
            'message' => 'Session disconnected',
            'data' => $session->fresh(),
        ]);
    }
}
