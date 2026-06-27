<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'currency' => config('pesapal.currency', 'TZS'),
            'duration_minutes' => $this->duration_minutes,
            'duration_label' => $this->duration_minutes >= 1440
                ? __('portal.duration_days', ['days' => intdiv($this->duration_minutes, 1440)])
                : ($this->duration_minutes >= 60
                    ? __('portal.duration_hours', ['hours' => intdiv($this->duration_minutes, 60)])
                    : __('portal.duration_minutes', ['minutes' => $this->duration_minutes])),
            'upload_speed' => $this->upload_speed,
            'download_speed' => $this->download_speed,
            'mikrotik_profile' => $this->mikrotik_profile,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
