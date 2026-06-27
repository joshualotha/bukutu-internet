<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActiveSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer' => CustomerResource::make($this->whenLoaded('customer')),
            'order' => OrderResource::make($this->whenLoaded('order')),
            'package' => PackageResource::make($this->whenLoaded('package')),
            'router' => RouterResource::make($this->whenLoaded('router')),
            'mac_address' => $this->mac_address,
            'mikrotik_username' => $this->mikrotik_username,
            'mikrotik_profile' => $this->mikrotik_profile,
            'start_time' => $this->start_time?->toISOString(),
            'expiry_time' => $this->expiry_time?->toISOString(),
            'time_remaining_seconds' => $this->timeRemaining(),
            'time_remaining_formatted' => $this->timeRemaining() > 0
                ? gmdate('H:i:s', $this->timeRemaining())
                : '00:00:00',
            'status' => $this->status?->value,
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'disconnected_at' => $this->disconnected_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
