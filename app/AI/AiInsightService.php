<?php

namespace App\AI;

use App\Models\AiInsight;
use App\Models\AiRun;
use App\Models\Contract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class AiInsightService
{
    public function generate(string $feature, ?int $entityId = null): array
    {
        if (!config('ai.enabled') || !config('ai.features.'.$feature, false)) return ['state' => 'disabled'];
        [$facts, $context, $entityType] = $this->payload($feature, $entityId);
        $context = app(AiContextSanitizer::class)->sanitize($context);
        $hash = hash('sha256', json_encode([$facts, $context, PromptRegistry::VERSION]));
        $cached = AiInsight::where('feature', $feature)->where('entity_id', $entityId)->where('input_hash', $hash)->where('expires_at', '>', now())->latest()->first();
        if ($cached) {
            $this->run((string) Str::uuid(), $feature, $entityType, $entityId, $hash, 'cached', true);
            return ['state' => 'ready', 'data' => $cached->response_json, 'cached' => true, 'facts' => $facts];
        }
        $started = microtime(true); $requestId = (string) Str::uuid();
        try {
            $gateway = app(AjilGatewayClient::class)->complete($feature, $facts, $context);
            $data = app(AiResponseValidator::class)->validate($gateway['response'], $facts);
            AiInsight::create(['scope' => 'panel', 'entity_type' => $entityType, 'entity_id' => $entityId, 'feature' => $feature, 'prompt_version' => PromptRegistry::VERSION, 'input_hash' => $hash, 'response_json' => $data, 'generated_at' => now(), 'expires_at' => now()->addSeconds(config('ai.cache_ttl'))]);
            $this->run($requestId, $feature, $entityType, $entityId, $hash, 'success', false, $gateway, (int) ((microtime(true) - $started) * 1000));
            return ['state' => 'ready', 'data' => $data, 'cached' => false, 'facts' => $facts];
        } catch (Throwable $e) {
            report($e); $this->run($requestId, $feature, $entityType, $entityId, $hash, 'unavailable', false, [], (int) ((microtime(true) - $started) * 1000), class_basename($e));
            return ['state' => 'unavailable'];
        }
    }

    private function payload(string $feature, ?int $entityId): array
    {
        $engine = app(AiFactEngine::class);
        return match ($feature) {
            'contract_brief' => $this->contractPayload($engine, $entityId),
            'dashboard_operations' => [$engine->dashboard(), ['generated_at' => now()->toIso8601String()], 'dashboard'],
            'payment_queue' => [$engine->payments(), ['generated_at' => now()->toIso8601String()], 'payment_queue'],
            'changes_since_login' => [$engine->changes(Auth::id()), ['period_hours' => 24], 'user'],
            default => [[], [], null],
        };
    }

    private function contractPayload(AiFactEngine $engine, ?int $id): array
    {
        $contract = Contract::findOrFail($id);
        $facts = $engine->contract($contract);
        return [$facts, ['contract_id' => $contract->id, 'status' => $contract->current_status, 'pickup_at' => $contract->pickup_date?->toIso8601String(), 'return_at' => $contract->return_date?->toIso8601String(), 'total_price_aed' => (float) $contract->total_price, 'previous_contracts' => Contract::where('customer_id', $contract->customer_id)->count(), 'payment_summary' => ['pending_amount_aed' => (float) $contract->payments->where('approval_status', 'pending')->sum('amount_in_aed'), 'operational_balance_aed' => $contract->calculateRemainingBalance($contract->payments)]], 'contract'];
    }

    private function run(string $requestId, string $feature, ?string $entityType, ?int $entityId, string $hash, string $status, bool $cached, array $gateway = [], ?int $latency = null, ?string $error = null): void
    {
        AiRun::create(['request_id' => $requestId, 'user_id' => Auth::id(), 'feature' => $feature, 'entity_type' => $entityType, 'entity_id' => $entityId, 'prompt_version' => PromptRegistry::VERSION, 'input_hash' => $hash, 'provider' => $gateway['provider'] ?? null, 'model' => $gateway['model'] ?? null, 'status' => $status, 'latency_ms' => $latency, 'cached' => $cached, 'error_class' => $error]);
    }
}
