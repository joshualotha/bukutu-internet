<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_reference',
        'customer_id',
        'package_id',
        'router_id',
        'amount',
        'status',
        'payment_method',
        'pesapal_tracking_id',
        'pesapal_merchant_ref',
        'transaction_reference',
        'paid_at',
        'expired_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'status' => PaymentStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_reference)) {
                $order->order_reference = self::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'ORD-' . strtoupper(Str::random(8));
        } while (static::where('order_reference', $reference)->exists());

        return $reference;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function activeSessions(): HasMany
    {
        return $this->hasMany(ActiveSession::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', PaymentStatus::PAID);
    }

    public function scopeStalePending($query, int $minutes = 30)
    {
        return $query->where('status', PaymentStatus::PENDING)
            ->whereNotNull('pesapal_tracking_id')
            ->where('created_at', '<', now()->subMinutes($minutes));
    }
}
