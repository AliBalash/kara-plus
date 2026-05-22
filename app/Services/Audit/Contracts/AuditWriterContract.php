<?php

namespace App\Services\Audit\Contracts;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;

interface AuditWriterContract
{
    public function log(string $action, array $payload = []): ?AuditEvent;

    public function logModel(string $action, Model $model, ?array $before = null, ?array $after = null, ?array $changedFields = null, array $meta = []): ?AuditEvent;
}
