<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveSession extends Model
{
    use HasFactory;


    protected $fillable = [
        'customer_id',
        'order_id',
        'package_id',
        'router_id',
        'mac_address',
        'mikrotik_username',
        'mikrotik_profile',
        'start_time',
        'expiry_time',
        'status',
        'disconnected_at',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'expiry_time' => 'datetime',
        'disconnected_at' => 'datetime',
        'status' => SessionStatus::class,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', SessionStatus::ACTIVE);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', SessionStatus::EXPIRED);
    }

    public function scopeNotExpired($query)
    {
        return $query->whereIn('status', [SessionStatus::ACTIVE, SessionStatus::SUSPENDED]);
    }

    public function isExpired(): bool
    {
        return $this->status === SessionStatus::EXPIRED;
    }

    public function isActive(): bool
    {
        return $this->status === SessionStatus::ACTIVE;
    }

    public function timeRemaining(): int
    {
        if (! $this->expiry_time || $this->isExpired()) {
            return 0;
        }

        return max(0, now()->diffInSeconds($this->expiry_time, false));
    }
}
