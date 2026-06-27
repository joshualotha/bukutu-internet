<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'mac_address' => $this->mac_address,
            'ip_address' => $this->ip_address,
            'device_name' => $this->device_name,
            'router' => RouterResource::make($this->whenLoaded('router')),
            'orders_count' => $this->whenCounted('orders'),
            'active_sessions_count' => $this->whenCounted('activeSessions'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
