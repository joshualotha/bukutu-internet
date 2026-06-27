<?php

use App\Enums\SessionStatus;
use App\Models\ActiveSession;
use App\Services\SessionService;
use Illuminate\Support\Facades\Http;

uses()->group('services');

beforeEach(function () {
    $this->sessionService = app(SessionService::class);

    // Mock ALL HTTP calls to prevent MikroTik timeouts
    Http::fake([
        '*' => Http::response([], 200),
    ]);
});

it('can expire a session', function () {
    $session = ActiveSession::factory()->create([
        'status' => 'active',
    ]);

    $this->sessionService->expireSession($session);

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::EXPIRED);
    expect($session->disconnected_at)->not->toBeNull();
});

it('does not double-expire a session', function () {
    $session = ActiveSession::factory()->expired()->create();

    $this->sessionService->expireSession($session);

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::EXPIRED);
});

it('can extend a session', function () {
    $session = ActiveSession::factory()->create([
        'status' => 'active',
        'expiry_time' => now()->addHour(),
    ]);

    $originalExpiry = $session->expiry_time;

    $this->sessionService->extendSession($session, 30);

    $session->refresh();
    expect($session->expiry_time)->toEqual($originalExpiry->copy()->addMinutes(30));
});

it('throws exception when suspending non-active session', function () {
    $session = ActiveSession::factory()->expired()->create();

    $this->sessionService->suspendSession($session);
})->throws(\RuntimeException::class, 'Only active sessions can be suspended');

it('can disconnect a session', function () {
    $session = ActiveSession::factory()->create([
        'status' => 'active',
    ]);

    $this->sessionService->disconnectSession($session);

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::EXPIRED);
    expect($session->disconnected_at)->not->toBeNull();
});
