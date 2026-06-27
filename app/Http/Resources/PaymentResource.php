<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'provider' => $this->provider,
            'provider_reference' => $this->provider_reference,
            'provider_tracking_id' => $this->provider_tracking_id,
            'payment_method' => $this->payment_method,
            'phone_number' => mask_phone($this->phone_number),
            'status' => $this->status?->value,
            'confirmation_code' => $this->confirmation_code,
            'payment_time' => $this->payment_time?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
