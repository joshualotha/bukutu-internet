<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'ip_address' => $this->ip_address,
            'api_port' => $this->api_port,
            'location' => $this->location,
            'is_active' => $this->is_active,
            'connection_status' => $this->connection_status?->value,
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'customers_count' => $this->whenCounted('customers'),
            'active_sessions_count' => $this->whenCounted('activeSessions'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
