<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_uuid',
        'occurred_at',
        'actor_user_id',
        'actor_role_snapshot',
        'ip',
        'user_agent',
        'route_name',
        'method',
        'url',
        'status_code',
        'request_id',
        'session_id_hash',
        'entity_type',
        'entity_id',
        'action',
        'before',
        'after',
        'changed_fields',
        'meta',
        'export_status',
        'export_attempts',
        'last_export_attempt_at',
        'exported_at',
        'export_last_error',
        'elastic_document_id',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'actor_role_snapshot' => 'array',
        'before' => 'array',
        'after' => 'array',
        'changed_fields' => 'array',
        'meta' => 'array',
        'last_export_attempt_at' => 'datetime',
        'exported_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
