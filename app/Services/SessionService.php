<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Integrations\MikroTik\MikroTikClient;
use App\Models\ActiveSession;
use App\Models\Order;
use App\Models\Router;
use Illuminate\Support\Facades\Log;

class SessionService
{
    /**
     * Activate a session on MikroTik for a paid order.
     */
    public function activateSession(Order $order, Router $router): ActiveSession
    {
        $package = $order->package;
        $customer = $order->customer;
        $macAddress = $customer->mac_address;
        $profile = $package->mikrotik_profile;
        $startTime = now();
        $expiryTime = $startTime->copy()->addMinutes($package->duration_minutes);

        // Create MikroTik client and authorize the MAC
        $mikrotik = new MikroTikClient($router);
        $authorized = $mikrotik->authorizeByMac($macAddress, $profile);

        if (! $authorized) {
            Log::error('Failed to authorize MAC on MikroTik during session activation', [
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'mac' => $macAddress,
                'router_id' => $router->id,
            ]);

            throw new \RuntimeException("Failed to activate session on router {$router->name}");
        }

        // Create the active session record
        $session = ActiveSession::create([
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'package_id' => $package->id,
            'router_id' => $router->id,
            'mac_address' => $macAddress,
            'mikrotik_username' => 'bt_' . str_replace(':', '', strtolower($macAddress)),
            'mikrotik_profile' => $profile,
            'start_time' => $startTime,
            'expiry_time' => $expiryTime,
            'status' => SessionStatus::ACTIVE,
        ]);

        Log::info('Session activated successfully', [
            'session_id' => $session->id,
            'mac' => $macAddress,
            'expires_at' => $expiryTime->toDateTimeString(),
        ]);

        return $session;
    }

    /**
     * Expire a session.
     */
    public function expireSession(ActiveSession $session): void
    {
        if ($session->status === SessionStatus::EXPIRED) {
            return;
        }

        $session->update([
            'status' => SessionStatus::EXPIRED,
            'disconnected_at' => now(),
        ]);

        // Deauthorize on MikroTik
        try {
            $router = $session->router;
            if ($router) {
                $mikrotik = new MikroTikClient($router);
                $mikrotik->deauthorizeByMac($session->mac_address);
            }
        } catch (\Exception $e) {
            Log::error('Failed to deauthorize MAC on session expiry', [
                'session_id' => $session->id,
                'mac' => $session->mac_address,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Session expired', [
            'session_id' => $session->id,
            'mac' => $session->mac_address,
        ]);
    }

    /**
     * Suspend an active session.
     */
    public function suspendSession(ActiveSession $session): void
    {
        if ($session->status !== SessionStatus::ACTIVE) {
            throw new \RuntimeException('Only active sessions can be suspended');
        }

        $session->update([
            'status' => SessionStatus::SUSPENDED,
        ]);

        // Disable on MikroTik
        try {
            $router = $session->router;
            if ($router) {
                $mikrotik = new MikroTikClient($router);
                $mikrotik->disableHotspotUser($session->mac_address);
            }
        } catch (\Exception $e) {
            Log::error('Failed to disable MAC on session suspend', [
                'session_id' => $session->id,
                'mac' => $session->mac_address,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Session suspended', [
            'session_id' => $session->id,
            'mac' => $session->mac_address,
        ]);
    }

    /**
     * Resume a suspended session.
     */
    public function resumeSession(ActiveSession $session): void
    {
        if ($session->status !== SessionStatus::SUSPENDED) {
            throw new \RuntimeException('Only suspended sessions can be resumed');
        }

        $session->update([
            'status' => SessionStatus::ACTIVE,
        ]);

        // Re-enable on MikroTik
        try {
            $router = $session->router;
            if ($router) {
                $mikrotik = new MikroTikClient($router);
                $mikrotik->enableHotspotUser($session->mac_address);
            }
        } catch (\Exception $e) {
            Log::error('Failed to enable MAC on session resume', [
                'session_id' => $session->id,
                'mac' => $session->mac_address,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Session resumed', [
            'session_id' => $session->id,
            'mac' => $session->mac_address,
        ]);
    }

    /**
     * Extend a session's expiry time.
     */
    public function extendSession(ActiveSession $session, int $additionalMinutes): void
    {
        $newExpiry = $session->expiry_time
            ? $session->expiry_time->copy()->addMinutes($additionalMinutes)
            : now()->addMinutes($additionalMinutes);

        $session->update([
            'expiry_time' => $newExpiry,
        ]);

        Log::info('Session extended', [
            'session_id' => $session->id,
            'additional_minutes' => $additionalMinutes,
            'new_expiry' => $newExpiry->toDateTimeString(),
        ]);
    }

    /**
     * Disconnect an active session immediately.
     */
    public function disconnectSession(ActiveSession $session): void
    {
        try {
            $router = $session->router;
            if ($router) {
                $mikrotik = new MikroTikClient($router);
                $mikrotik->disconnectSession($session->mac_address);
            }
        } catch (\Exception $e) {
            Log::error('Failed to disconnect session', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }

        $session->update([
            'status' => SessionStatus::EXPIRED,
            'disconnected_at' => now(),
        ]);

        Log::info('Session disconnected', [
            'session_id' => $session->id,
            'mac' => $session->mac_address,
        ]);
    }
}
