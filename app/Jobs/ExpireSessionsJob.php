<?php

namespace App\Jobs;

use App\Enums\SessionStatus;
use App\Models\ActiveSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExpireSessionsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Find all active sessions past their expiry time and mark them expired.
     * The actual MikroTik cleanup is handled by DisconnectExpiredUsersJob.
     */
    public function handle(): void
    {
        $expiredSessions = ActiveSession::where('status', SessionStatus::ACTIVE)
            ->where('expiry_time', '<=', now())
            ->get();

        $count = 0;

        foreach ($expiredSessions as $session) {
            $session->update([
                'status' => SessionStatus::EXPIRED,
                'disconnected_at' => now(),
            ]);
            $count++;
        }

        if ($count > 0) {
            Log::info("ExpireSessionsJob: Expired {$count} sessions");
        }
    }
}
