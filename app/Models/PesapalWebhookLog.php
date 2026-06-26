<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesapalWebhookLog extends Model
{
    protected $fillable = [
        'payload',
        'ipn_type',
        'processed',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'json',
        'processed' => 'boolean',
    ];
}
