<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;


    protected $fillable = [
        'order_id',
        'amount',
        'currency',
        'provider',
        'provider_reference',
        'provider_tracking_id',
        'payment_method',
        'phone_number',
        'status',
        'response_payload',
        'confirmation_code',
        'payment_time',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'response_payload' => 'json',
        'payment_time' => 'datetime',
        'status' => PaymentStatus::class,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
