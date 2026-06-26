<?php

use App\Jobs\CleanupOldLogsJob;
use App\Jobs\CollectUsageStatisticsJob;
use App\Jobs\DisconnectExpiredUsersJob;
use App\Jobs\ExpireSessionsJob;
use App\Jobs\RetryPaymentVerificationJob;
use App\Jobs\TestRouterConnectionsJob;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Task Scheduling
|--------------------------------------------------------------------------
|
| All scheduled jobs are defined here. These run via the Laravel scheduler
| which should be triggered every minute by the server's cron:
|   * * * * * php /path-to-project/artisan schedule:run >> /dev/null 2>&1
|
*/

// Every minute
Schedule::job(new ExpireSessionsJob)->everyMinute();
Schedule::job(new DisconnectExpiredUsersJob)->everyMinute();

// Every 5 minutes
Schedule::job(new RetryPaymentVerificationJob)->everyFiveMinutes();

// Every hour
Schedule::job(new CollectUsageStatisticsJob)->hourly();

// Daily at 3 AM
Schedule::job(new CleanupOldLogsJob)->dailyAt('03:00');

// Test router connectivity hourly
Schedule::job(new TestRouterConnectionsJob)->hourly();
