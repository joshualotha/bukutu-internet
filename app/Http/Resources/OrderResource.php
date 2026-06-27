<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_reference' => $this->order_reference,
            'customer' => CustomerResource::make($this->whenLoaded('customer')),
            'package' => PackageResource::make($this->whenLoaded('package')),
            'router' => RouterResource::make($this->whenLoaded('router')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'active_sessions' => ActiveSessionResource::collection($this->whenLoaded('activeSessions')),
            'amount' => (float) $this->amount,
            'currency' => config('pesapal.currency', 'UGX'),
            'status' => $this->status?->value,
            'status_label' => __('portal.status_' . ($this->status?->value ?? 'pending')),
            'payment_method' => $this->payment_method,
            'pesapal_tracking_id' => $this->pesapal_tracking_id,
            'transaction_reference' => $this->transaction_reference,
            'paid_at' => $this->paid_at?->toISOString(),
            'expired_at' => $this->expired_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
