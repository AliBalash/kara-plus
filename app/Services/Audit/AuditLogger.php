<?php

namespace App\Services\Audit;

use App\Jobs\ExportAuditEventJob;
use App\Models\AuditEvent;
use App\Services\Audit\Contracts\AuditWriterContract;
use App\Support\Audit\AuditContext;
use App\Support\Audit\AuditPayloadNormalizer;
use App\Support\Audit\AuditRedactor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuditLogger implements AuditWriterContract
{
    private static bool $muted = false;

    public function __construct(
        private readonly AuditContext $context,
        private readonly AuditRedactor $redactor,
        private readonly AuditPayloadNormalizer $normalizer,
    ) {
    }

    public static function mute(bool $value = true): void
    {
        self::$muted = $value;
    }

    public function log(string $action, array $payload = []): ?AuditEvent
    {
        if (self::$muted) {
            return null;
        }

        $actor = Auth::user();
        $context = $this->context->toArray();

        $before = $this->sanitize($payload['before'] ?? null);
        $after = $this->sanitize($payload['after'] ?? null);
        $changedFields = $this->sanitize($payload['changed_fields'] ?? null);
        $meta = $this->sanitize($payload['meta'] ?? null);

        $event = AuditEvent::create([
            'event_uuid' => (string) Str::uuid(),
            'occurred_at' => now(),
            'actor_user_id' => $payload['actor_user_id'] ?? $actor?->id,
            'actor_role_snapshot' => $payload['actor_role_snapshot'] ?? ($actor ? $actor->getRoleNames()->values()->all() : null),
            'ip' => $payload['ip'] ?? $context['ip'] ?? null,
            'user_agent' => $payload['user_agent'] ?? $context['user_agent'] ?? null,
            'route_name' => $payload['route_name'] ?? $context['route_name'] ?? null,
            'method' => $payload['method'] ?? $context['method'] ?? null,
            'url' => $payload['url'] ?? $context['url'] ?? null,
            'status_code' => $payload['status_code'] ?? $context['status_code'] ?? null,
            'request_id' => $payload['request_id'] ?? $context['request_id'] ?? null,
            'session_id_hash' => $payload['session_id_hash'] ?? $context['session_id_hash'] ?? null,
            'entity_type' => $payload['entity_type'] ?? null,
            'entity_id' => isset($payload['entity_id']) ? (string) $payload['entity_id'] : null,
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'changed_fields' => $changedFields,
            'meta' => $meta,
            'export_status' => 'pending',
        ]);

        if ((bool) config('audit.export.enabled', true)) {
            try {
                ExportAuditEventJob::dispatch($event->id)->onQueue((string) config('audit.export.queue', 'default'));
            } catch (\Throwable $exception) {
                Log::error('Audit export dispatch failed.', [
                    'audit_event_id' => $event->id,
                    'event_uuid' => $event->event_uuid,
                    'action' => $event->action,
                    'message' => $exception->getMessage(),
                ]);

                $event->update([
                    'export_status' => 'failed',
                    'export_last_error' => 'Dispatch failed: ' . $exception->getMessage(),
                ]);
            }
        }

        return $event;
    }

    public function logModel(string $action, Model $model, ?array $before = null, ?array $after = null, ?array $changedFields = null, array $meta = []): ?AuditEvent
    {
        return $this->log($action, [
            'entity_type' => $model::class,
            'entity_id' => (string) $model->getKey(),
            'before' => $before,
            'after' => $after,
            'changed_fields' => $changedFields,
            'meta' => $meta,
        ]);
    }

    private function sanitize(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return $this->redactor->redact($this->normalizer->normalize($value));
    }
}
