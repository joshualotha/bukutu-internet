<?php

use App\Integrations\MikroTik\MikroTikClient;
use App\Models\Router;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

uses()->group('mikrotik');

beforeEach(function () {
    $this->router = new Router([
        'id' => 1,
        'name' => 'Test Router',
        'ip_address' => '192.168.88.1',
        'api_port' => 8728,
        'username' => 'admin',
        'password' => encrypt('password123'),
        'connection_status' => 'unknown',
    ]);
});

it('can test connection successfully', function () {
    Http::fake([
        'http://192.168.88.1:8728/rest/system/resource' => Http::response([
            'platform' => 'MikroTik',
            'version' => '7.6',
        ]),
    ]);

    $client = new MikroTikClient($this->router);
    $result = $client->testConnection();

    expect($result)->toBeTrue();
});

it('returns false when connection fails', function () {
    Http::fake([
        'http://192.168.88.1:8728/rest/*' => Http::response('', 503),
    ]);

    $client = new MikroTikClient($this->router);
    $result = $client->testConnection();

    expect($result)->toBeFalse();
});

it('returns false on connection exception', function () {
    Http::fake([
        'http://192.168.88.1:8728/rest/*' => function () {
            throw new ConnectionException('Connection refused');
        },
    ]);

    $client = new MikroTikClient($this->router);
    $result = $client->testConnection();

    expect($result)->toBeFalse();
});

it('can create a hotspot user', function () {
    Http::fake([
        'http://192.168.88.1:8728/rest/ip/hotspot/user' => Http::response(['.id' => '*1'], 201),
    ]);

    $client = new MikroTikClient($this->router);
    $result = $client->createHotspotUser('AA:BB:CC:DD:EE:FF', '1M_1M');

    expect($result)->toBeTrue();
});

it('can get a hotspot user by mac', function () {
    Http::fake([
        'http://192.168.88.1:8728/rest/ip/hotspot/user?name=bt_aabbccddeeff' => Http::response([
            ['.id' => '*1', 'name' => 'bt_aabbccddeeff', 'mac-address' => 'AA:BB:CC:DD:EE:FF'],
        ]),
    ]);

    $client = new MikroTikClient($this->router);
    $result = $client->getHotspotUser('AA:BB:CC:DD:EE:FF');

    expect($result)->not->toBeNull()
        ->and($result['.id'])->toEqual('*1');
});

it('returns null when hotspot user not found', function () {
    Http::fake([
        'http://192.168.88.1:8728/rest/ip/hotspot/user?name=bt_nonexistent' => Http::response('', 404),
    ]);

    $client = new MikroTikClient($this->router);
    $result = $client->getHotspotUser('AA:BB:CC:DD:FF:FF');

    expect($result)->toBeNull();
});
