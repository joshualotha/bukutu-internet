<?php

use App\Integrations\Pesapal\PesapalClient;
use App\Models\Package;
use App\Services\OrderService;
use App\Services\SessionService;

uses()->group('services');

beforeEach(function () {
    $this->pesapalClient = Mockery::mock(PesapalClient::class);
    $this->sessionService = Mockery::mock(SessionService::class);

    $this->orderService = new OrderService(
        $this->pesapalClient,
        $this->sessionService,
    );
});

it('throws exception when package is inactive', function () {
    $package = Package::factory()->inactive()->create();

    $this->orderService->createOrder(
        macAddress: 'AA:BB:CC:DD:EE:FF',
        packageId: $package->id,
        phoneNumber: '0712345678',
    );
})->throws(\RuntimeException::class, 'Package is not available');

it('throws exception when pesapal submission fails', function () {
    $package = Package::factory()->create();

    $this->pesapalClient->shouldReceive('submitOrder')
        ->once()
        ->andThrow(new \Exception('Pesapal error'));

    expect(fn () => $this->orderService->createOrder(
        macAddress: 'AA:BB:CC:DD:EE:FF',
        packageId: $package->id,
        phoneNumber: '0712345678',
    ))->toThrow(\Exception::class, 'Pesapal error');
});
