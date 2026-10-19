<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Durable preview and idempotency receipt; independent of audit-log retention. */
class FleetPriceOperation extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['actor_id' => 'integer', 'payload' => 'array', 'expires_at' => 'datetime', 'applied_at' => 'datetime'];
}
