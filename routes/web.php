<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Captive portal pages and language switching.
|
*/

// Home/Landing page (captive portal landing)
Route::get('/', function (Request $request) {
    $mac = $request->query('mac');
    $ip = $request->query('ip');

    return view('portal.landing', compact('mac', 'ip'));
})->name('portal.landing');

// Package selection
Route::get('/packages', function (Request $request) {
    $packages = \App\Models\Package::active()->sorted()->get();

    return view('portal.packages', compact('packages'));
})->name('portal.packages');

// Checkout
Route::get('/checkout', function (Request $request) {
    $packageId = $request->query('package_id');
    $package = \App\Models\Package::active()->findOrFail($packageId);

    return view('portal.checkout', compact('package'));
})->name('portal.checkout');

// Payment processing / confirmation pending
Route::get('/processing/{reference}', function (string $reference) {
    return view('portal.processing', compact('reference'));
})->name('portal.processing');

// Payment success
Route::get('/success/{reference}', function (string $reference) {
    $order = \App\Models\Order::where('order_reference', $reference)
        ->with(['package', 'customer', 'activeSessions.package'])
        ->first();

    if (! $order || $order->status !== \App\Enums\PaymentStatus::PAID) {
        return redirect()->route('portal.landing', request()->query());
    }

    $session = $order->activeSessions()->latest()->first();

    return view('portal.success', compact('order', 'session'));
})->name('portal.success');

// Session status page
Route::get('/status', function (Request $request) {
    $mac = $request->query('mac');

    if ($mac) {
        $customer = \App\Models\Customer::where('mac_address', $mac)->first();
        $session = $customer
            ? \App\Models\ActiveSession::where('customer_id', $customer->id)
                ->with(['package'])
                ->latest()
                ->first()
            : null;
    } else {
        $session = null;
    }

    return view('portal.status', compact('session'));
})->name('portal.status');

// Error page
Route::get('/error', function (Request $request) {
    $message = $request->query('message', __('portal.payment_failed_message'));

    return view('portal.error', compact('message'));
})->name('portal.error');

// Terms & Conditions
Route::get('/terms', function () {
    return view('portal.terms');
})->name('portal.terms');

// Language switcher
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'sw'])) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }

    return redirect()->back();
})->name('portal.lang');

// Fallback POST route for admin login (handles native form submission
// when Livewire JavaScript hasn't fully initialized)
Route::post('/admin/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->only('email', 'password');

    if (\Illuminate\Support\Facades\Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();

        return redirect()->intended('/admin');
    }

    return back()->withErrors([
        'email' => __('filament-panels::pages/auth/login.messages.failed'),
    ])->onlyInput('email');
})->name('filament.admin.auth.login.post');
