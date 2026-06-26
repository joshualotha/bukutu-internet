<?php

namespace App\Jobs;

use App\Enums\SessionStatus;
use App\Integrations\MikroTik\MikroTikClient;
use App\Models\ActiveSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DisconnectExpiredUsersJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Find recently expired sessions and deauthorize them on MikroTik.
     */
    public function handle(): void
    {
        // Find sessions expired within the last 30 minutes that haven't been disconnected yet
        $expiredSessions = ActiveSession::where('status', SessionStatus::EXPIRED)
            ->whereNotNull('disconnected_at')
            ->where('disconnected_at', '>=', now()->subMinutes(30))
            ->whereNotNull('mac_address')
            ->get();

        $count = 0;

        foreach ($expiredSessions as $session) {
            try {
                $router = $session->router;

                if (! $router) {
                    continue;
                }

                $mikrotik = new MikroTikClient($router);
                $mikrotik->deauthorizeByMac($session->mac_address);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to disconnect expired user from router', [
                    'session_id' => $session->id,
                    'mac' => $session->mac_address,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($count > 0) {
            Log::info("DisconnectExpiredUsersJob: Disconnected {$count} users from routers");
        }
    }
}
