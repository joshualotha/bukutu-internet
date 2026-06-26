<?php

use App\Http\Controllers\Webhook\PesapalIpnController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| These routes are excluded from CSRF protection and handle incoming
| webhooks from external services like Pesapal.
|
*/

Route::post('/pesapal/ipn', [PesapalIpnController::class, 'handle'])
    ->name('pesapal.ipn');
