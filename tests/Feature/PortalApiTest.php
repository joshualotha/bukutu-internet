<?php

use App\Models\Package;

uses()->group('portal-api');

beforeEach(function () {
    Package::factory()->create([
        'name' => '1 Hour',
        'price' => 1000,
        'duration_minutes' => 60,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Package::factory()->create([
        'name' => 'Daily',
        'price' => 5000,
        'duration_minutes' => 1440,
        'is_active' => true,
        'sort_order' => 2,
    ]);

    Package::factory()->create([
        'name' => 'Weekly',
        'price' => 20000,
        'duration_minutes' => 10080,
        'is_active' => false,
        'sort_order' => 3,
    ]);
});

it('lists active packages', function () {
    $response = $this->getJson('/api/portal/packages');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'price', 'duration_minutes', 'sort_order'],
            ],
        ]);

    // Should only return active packages
    expect($response->json('data'))->toHaveCount(2);
});

it('shows package details', function () {
    $package = Package::where('name', '1 Hour')->first();

    $response = $this->getJson("/api/portal/packages/{$package->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => '1 Hour',
                'price' => 1000,
            ],
        ]);
});

it('returns 404 for inactive package', function () {
    $package = Package::where('name', 'Weekly')->first();

    $response = $this->getJson("/api/portal/packages/{$package->id}");

    $response->assertStatus(404);
});

it('checks auth returns false for unknown mac', function () {
    $response = $this->postJson('/api/portal/auth/check', [
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'authorized' => false,
        ]);
});

it('returns session not found for unknown mac', function () {
    $response = $this->getJson('/api/portal/session/AA:BB:CC:DD:EE:FF');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
        ]);
});
