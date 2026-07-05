<?php

namespace App\Services\Audit;

use App\Models\AuditEvent;
use App\Services\Audit\Contracts\AuditExportContract;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElasticsearchAuditExporter implements AuditExportContract
{
    public function export(AuditEvent $event): void
    {
        if (! (bool) config('audit.elasticsearch.enabled', true)) {
            return;
        }

        $documentId = $event->event_uuid;
        $index = $this->indexName($event);
        $url = rtrim((string) config('audit.elasticsearch.base_url'), '/') . '/' . $index . '/_doc/' . $documentId;

        $response = $this->client()->put($url, $this->payload($event));

        if (! $response->successful()) {
            Log::error('Elasticsearch audit export request failed.', [
                'audit_event_id' => $event->id,
                'event_uuid' => $event->event_uuid,
                'index' => $index,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Elasticsearch export failed: ' . $response->status() . ' ' . $response->body());
        }
    }

    public function ensureIlmAndTemplate(): void
    {
        if (! (bool) config('audit.elasticsearch.enabled', true)) {
            return;
        }

        $base = rtrim((string) config('audit.elasticsearch.base_url'), '/');
        $policyName = (string) config('audit.elasticsearch.ilm_policy', 'kara-audit-1m');
        $indexPrefix = (string) config('audit.elasticsearch.index_prefix', 'kara-audit');

        $policyPayload = [
            'policy' => [
                'phases' => [
                    'hot' => ['actions' => new \stdClass()],
                    'delete' => [
                        'min_age' => '30d',
                        'actions' => [
                            'delete' => new \stdClass(),
                        ],
                    ],
                ],
            ],
        ];

        $policyRes = $this->client()->put("{$base}/_ilm/policy/{$policyName}", $policyPayload);
        if (! $policyRes->successful()) {
            Log::error('Failed to configure Elasticsearch ILM policy for audit exports.', [
                'policy' => $policyName,
                'status' => $policyRes->status(),
                'body' => $policyRes->body(),
            ]);
            throw new \RuntimeException('Failed setting ILM policy: ' . $policyRes->status() . ' ' . $policyRes->body());
        }

        $templatePayload = [
            'index_patterns' => ["{$indexPrefix}-*"],
            'template' => [
                'settings' => [
                    'index.lifecycle.name' => $policyName,
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'refresh_interval' => '5s',
                ],
                'mappings' => [
                    'dynamic' => true,
                ],
            ],
        ];

        $templateRes = $this->client()->put("{$base}/_index_template/{$indexPrefix}-template", $templatePayload);
        if (! $templateRes->successful()) {
            Log::error('Failed to configure Elasticsearch index template for audit exports.', [
                'index_prefix' => $indexPrefix,
                'status' => $templateRes->status(),
                'body' => $templateRes->body(),
            ]);
            throw new \RuntimeException('Failed setting index template: ' . $templateRes->status() . ' ' . $templateRes->body());
        }
    }

    private function payload(AuditEvent $event): array
    {
        $contractRefs = $this->resolveContractRefs($event);

        return [
            'event_uuid' => $event->event_uuid,
            'occurred_at' => optional($event->occurred_at)->toIso8601String(),
            'actor_user_id' => $event->actor_user_id,
            'actor_role_snapshot' => $event->actor_role_snapshot,
            'ip' => $event->ip,
            'user_agent' => $event->user_agent,
            'route_name' => $event->route_name,
            'method' => $event->method,
            'url' => $event->url,
            'status_code' => $event->status_code,
            'request_id' => $event->request_id,
            'session_id_hash' => $event->session_id_hash,
            'entity_type' => $event->entity_type,
            'entity_id' => $event->entity_id,
            'action' => $event->action,
            'before' => $this->normalizeFlattenedField($event->before),
            'after' => $this->normalizeFlattenedField($event->after),
            'changed_fields' => $this->normalizeFlattenedField($event->changed_fields),
            'meta' => $this->normalizeFlattenedField($event->meta),
            'contract_refs' => $contractRefs !== [] ? $contractRefs : null,
        ];
    }

    private function normalizeFlattenedField(mixed $value): ?array
    {
        if (! is_array($value) || $value === []) {
            return null;
        }

        return $this->normalizeFlattenedObject($value);
    }

    private function normalizeFlattenedObject(array $value): array
    {
        if (array_is_list($value)) {
            $normalized = [];

            foreach ($value as $index => $item) {
                if (is_scalar($item) || $item === null) {
                    $normalized[$this->flattenedListKey($item, $index)] = true;
                    continue;
                }

                $normalized['item_' . $index] = $this->normalizeFlattenedValue($item);
            }

            return $normalized;
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalized[(string) $key] = $this->normalizeFlattenedValue($item);
        }

        return $normalized;
    }

    private function normalizeFlattenedValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value === [] ? new \stdClass() : $this->normalizeFlattenedObject($value);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof \JsonSerializable) {
            return $this->normalizeFlattenedValue($value->jsonSerialize());
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $this->normalizeFlattenedValue($value->toArray());
        }

        if (is_object($value)) {
            return $value::class;
        }

        return $value;
    }

    private function flattenedListKey(mixed $value, int $index): string
    {
        $candidate = trim((string) ($value ?? ''));

        if ($candidate === '') {
            return 'item_' . $index;
        }

        return mb_substr($candidate, 0, 120);
    }

    private function resolveContractRefs(AuditEvent $event): array
    {
        $refs = [];

        if ($event->entity_type === 'App\\Models\\Contract') {
            $this->appendRef($refs, $event->entity_id);
        }

        foreach (['meta', 'before', 'after'] as $segment) {
            $payload = $event->{$segment};
            if (! is_array($payload)) {
                continue;
            }

            foreach (['contract_id', 'from_contract_id', 'to_contract_id'] as $key) {
                $this->appendRef($refs, $payload[$key] ?? null);
            }
        }

        $refs = array_values(array_unique($refs));
        sort($refs, SORT_NATURAL);

        return $refs;
    }

    private function appendRef(array &$refs, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            $refs[] = (string) (int) $value;
            return;
        }

        if (is_string($value)) {
            $refs[] = $value;
        }
    }

    private function indexName(AuditEvent $event): string
    {
        $prefix = (string) config('audit.elasticsearch.index_prefix', 'kara-audit');
        $date = optional($event->occurred_at)->format('Y.m.d') ?? now()->format('Y.m.d');

        return "{$prefix}-{$date}";
    }

    private function client(): PendingRequest
    {
        $request = Http::timeout((int) config('audit.export.request_timeout_seconds', 10))
            ->acceptJson()
            ->asJson()
            ->withOptions(['verify' => (bool) config('audit.elasticsearch.verify_tls', false)]);

        $apiKey = config('audit.elasticsearch.api_key');
        if (is_string($apiKey) && $apiKey !== '') {
            return $request->withToken($apiKey);
        }

        $username = config('audit.elasticsearch.username');
        $password = config('audit.elasticsearch.password');
        if (is_string($username) && $username !== '') {
            return $request->withBasicAuth($username, (string) $password);
        }

        return $request;
    }
}
