<?php

namespace App\AI;

use App\Models\AiInsight;
use App\Models\AiRun;
use App\Models\Contract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class AiInsightService
{
    public function generate(string $feature, ?int $entityId = null): array
    {
        if (!config('ai.enabled') || !config('ai.features.'.$feature, false)) return ['state' => 'disabled'];
        if ($this->isCircuitOpen($feature)) return ['state' => 'unavailable'];
        [$facts, $context, $entityType] = $this->payload($feature, $entityId);
        $context = app(AiContextSanitizer::class)->sanitize($context);
        [$facts, $context] = app(AiTokenBudgeter::class)->compact($facts, $context, config('ai.max_facts'), config('ai.max_context_bytes'));
        $promptVersion = app(PromptRegistry::class)->version($feature);
        $hash = hash('sha256', json_encode([$facts, $context, $promptVersion]));
        $cached = AiInsight::where('feature', $feature)->where('entity_id', $entityId)->where('input_hash', $hash)->where('expires_at', '>', now())->latest()->first();
        if ($cached) {
            $this->run((string) Str::uuid(), $feature, $entityType, $entityId, $hash, $promptVersion, 'cached', true);
            return ['state' => 'ready', 'data' => $cached->response_json, 'cached' => true, 'insight_id' => $cached->id, 'entity_type' => $entityType, 'facts' => $facts, 'meta' => $context['pulse'] ?? []];
        }
        $lock = Cache::lock('kara-ai:inflight:'.$hash, config('ai.ajil.timeout') + 5);
        if (! $lock->get()) {
            return ['state' => 'busy'];
        }
        $started = microtime(true); $requestId = (string) Str::uuid();
        try {
            // A request may have completed between the first cache lookup and
            // acquiring the lock. Reuse it instead of calling Ajil again.
            $cached = AiInsight::where('feature', $feature)->where('entity_id', $entityId)->where('input_hash', $hash)->where('expires_at', '>', now())->latest()->first();
            if ($cached) {
                $this->run((string) Str::uuid(), $feature, $entityType, $entityId, $hash, $promptVersion, 'cached', true);
                return ['state' => 'ready', 'data' => $cached->response_json, 'cached' => true, 'insight_id' => $cached->id, 'entity_type' => $entityType, 'facts' => $facts, 'meta' => $context['pulse'] ?? []];
            }
            $gateway = app(AjilGatewayClient::class)->complete($feature, $facts, $context);
            $data = app(AiResponseValidator::class)->validate($gateway['response'], $facts);
            $insight = AiInsight::create(['scope' => 'panel', 'entity_type' => $entityType, 'entity_id' => $entityId, 'feature' => $feature, 'prompt_version' => $promptVersion, 'input_hash' => $hash, 'response_json' => $data, 'generated_at' => now(), 'expires_at' => now()->addSeconds(config('ai.cache_ttl'))]);
            Cache::forget($this->circuitKey($feature));
            $this->run($requestId, $feature, $entityType, $entityId, $hash, $promptVersion, 'success', false, $gateway, (int) ((microtime(true) - $started) * 1000));
            return ['state' => 'ready', 'data' => $data, 'cached' => false, 'insight_id' => $insight->id, 'entity_type' => $entityType, 'facts' => $facts, 'meta' => $context['pulse'] ?? []];
        } catch (Throwable $e) {
            report($e); $this->recordFailure($feature); $this->run($requestId, $feature, $entityType, $entityId, $hash, $promptVersion, 'unavailable', false, [], (int) ((microtime(true) - $started) * 1000), class_basename($e));
            return ['state' => 'unavailable'];
        } finally {
            $lock->release();
        }
    }

    private function payload(string $feature, ?int $entityId): array
    {
        $engine = app(AiFactEngine::class);
        return match ($feature) {
            'contract_brief' => $this->contractPayload($engine, $entityId),
            'dashboard_operations' => [$engine->dashboard(), [], 'dashboard'],
            'payment_queue' => [$engine->payments(), [], 'payment_queue'],
            'changes_since_login' => [$engine->changes(Auth::id()), ['period_hours' => 24], 'user'],
            default => [[], [], null],
        };
    }

    private function contractPayload(AiFactEngine $engine, ?int $id): array
    {
        $contract = Contract::findOrFail($id);
        $facts = $engine->contract($contract);
        $payments = $contract->payments;
        $statusAt = $contract->latestStatus?->created_at ?? $contract->updated_at;

        return [$facts, [
            'contract_id' => $contract->id,
            'status' => $contract->current_status,
            'status_age_hours' => $statusAt ? (int) $statusAt->diffInHours(now()) : null,
            'pickup_at' => $contract->pickup_date?->toIso8601String(),
            'return_at' => $contract->return_date?->toIso8601String(),
            'actual_pickup_at' => $contract->actual_pickup_at?->toIso8601String(),
            'actual_return_at' => $contract->actual_return_at?->toIso8601String(),
            'duration_days' => $contract->pickup_date && $contract->return_date ? $contract->pickup_date->diffInDays($contract->return_date) : null,
            'total_price_aed' => (float) $contract->total_price,
            'previous_contracts' => Contract::where('customer_id', $contract->customer_id)->count(),
            'vehicle' => [
                'id' => $contract->car_id,
                'operational_status' => $contract->car?->status,
                'available' => $contract->car?->availability,
                'service_due_at' => $contract->car?->service_due_date?->toDateString(),
                'insurance_expires_at' => $contract->car?->insurance_expiry_date?->toDateString(),
                'registration_expires_at' => $contract->car?->expiry_date?->toDateString(),
            ],
            'documents' => [
                'customer_document_present' => $contract->customerDocument !== null,
                'pickup_document_present' => $contract->pickupDocument !== null,
                'return_document_present' => $contract->returnDocument !== null,
            ],
            'amendments' => $contract->amendments->take(3)->map(fn ($amendment) => [
                'type' => $amendment->type,
                'status' => $amendment->status,
                'effective_at' => $amendment->effective_at?->toIso8601String(),
                'total_amount_aed' => (float) $amendment->total_amount,
            ])->values()->all(),
            'pulse' => $engine->contractPulse($facts),
            'payment_summary' => [
                'transaction_count' => $payments->count(),
                'pending_count' => $payments->where('approval_status', 'pending')->count(),
                'pending_amount_aed' => (float) $payments->where('approval_status', 'pending')->sum('amount_in_aed'),
                'operational_balance_aed' => $contract->calculateRemainingBalance($payments),
            ],
        ], 'contract'];
    }

    private function run(string $requestId, string $feature, ?string $entityType, ?int $entityId, string $hash, string $promptVersion, string $status, bool $cached, array $gateway = [], ?int $latency = null, ?string $error = null): void
    {
        AiRun::create([
            'request_id' => $requestId,
            'user_id' => Auth::id(),
            'feature' => $feature,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'prompt_version' => $promptVersion,
            'input_hash' => $hash,
            'provider' => $gateway['provider'] ?? null,
            'model' => $gateway['model'] ?? null,
            'strategy' => config('ai.routing_strategy'),
            'status' => $status,
            'latency_ms' => $latency,
            'input_tokens' => is_numeric($gateway['input_tokens'] ?? null) ? (int) $gateway['input_tokens'] : null,
            'output_tokens' => is_numeric($gateway['output_tokens'] ?? null) ? (int) $gateway['output_tokens'] : null,
            'cached' => $cached,
            'error_class' => $error,
        ]);
    }

    private function circuitKey(string $feature): string { return 'kara-ai:circuit:'.$feature; }

    private function isCircuitOpen(string $feature): bool
    {
        return (int) Cache::get($this->circuitKey($feature), 0) >= config('ai.circuit.failure_threshold');
    }

    private function recordFailure(string $feature): void
    {
        $key = $this->circuitKey($feature);
        Cache::put($key, ((int) Cache::get($key, 0)) + 1, now()->addSeconds(config('ai.circuit.cooldown')));
    }
}
