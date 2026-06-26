<?php

namespace App\Providers;

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
    }
}
