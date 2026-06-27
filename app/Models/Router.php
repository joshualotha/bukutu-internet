<?php

namespace App\Models;

use App\Enums\RouterConnectionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'api_port',
        'username',
        'password',
        'location',
        'is_active',
        'last_seen_at',
        'connection_status',
        'notes',
    ];

    protected $casts = [
        'api_port' => 'integer',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'connection_status' => RouterConnectionStatus::class,
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function activeSessions(): HasMany
    {
        return $this->hasMany(ActiveSession::class);
    }

    /**
     * Decrypt the router password for API calls.
     */
    public function getDecryptedPassword(): string
    {
        return decrypt($this->password);
    }
}
