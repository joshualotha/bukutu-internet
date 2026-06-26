<?php

namespace App\Jobs;

use App\Models\AdminActivityLog;
use App\Models\PesapalWebhookLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CleanupOldLogsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Delete old log entries.
     */
    public function handle(): void
    {
        // Delete Pesapal webhook logs older than 90 days
        $deletedWebhookLogs = PesapalWebhookLog::where('created_at', '<', now()->subDays(90))->delete();

        // Delete admin activity logs older than 180 days
        $deletedAdminLogs = AdminActivityLog::where('created_at', '<', now()->subDays(180))->delete();

        Log::info('CleanupOldLogsJob', [
            'deleted_pesapal_logs' => $deletedWebhookLogs,
            'deleted_admin_logs' => $deletedAdminLogs,
        ]);
    }
}
