<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::prefix('webhook')
                ->name('webhook.')
                ->group(__DIR__.'/../routes/webhook.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Exclude from CSRF: webhooks + admin login POST fallback
        $middleware->validateCsrfTokens(except: [
            'webhook/*',
            'admin/login',  // Native form POST fallback when Livewire JS doesn't intercept
        ]);

        // Rate limiting aliases
        $middleware->alias([
            'throttle.orders' => \Illuminate\Routing\Middleware\ThrottleRequests::class.':orders',
        ]);

        // Trust proxies for HTTPS
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
