<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if (config('app.env') === 'production' || env('APP_FORCE_HTTPS', false)) {
            $this->app['request']->server->set('HTTPS', 'on');
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Suppress PHP 8.5+ deprecation notices (e.g. PDO::MYSQL_ATTR_SSL_CA)
        // These are logged but should not break HTML output
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        // Define API rate limiters
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('orders', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

    }
}
