<?php

namespace App\AI;

use App\Models\AiInsight;
use App\Models\AiRun;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class AiInsightService
{
    public function generate(string $feature, ?int $entityId = null, bool $force = false): array
    {
        if (! config('ai.enabled') || ! config('ai.features.'.$feature, false)) {
            return ['state' => 'disabled'];
        }
        [$facts, $context, $entityType] = $this->payload($feature, $entityId);
        $context = app(AiContextSanitizer::class)->sanitize($context);
        [$facts, $context] = app(AiTokenBudgeter::class)->compact($facts, $context, config('ai.max_facts'), config('ai.max_context_bytes'));
        $verifiedFallback = ['state' => 'unavailable', 'facts' => $facts, 'meta' => $context['pulse'] ?? []];
        if ($this->isCircuitOpen($feature)) {
            return $verifiedFallback;
        }
        $promptVersion = app(PromptRegistry::class)->version($feature);
        $hash = hash('sha256', json_encode([$facts, $context, $promptVersion]));
        $cached = AiInsight::where('feature', $feature)->where('entity_id', $entityId)->where('input_hash', $hash)->where('expires_at', '>', now())->latest()->first();
        if ($cached && ! $force) {
            $this->run((string) Str::uuid(), $feature, $entityType, $entityId, $hash, $promptVersion, 'cached', true);

            return $this->readyPayload($cached, $facts, $context, $entityType, true);
        }
        $lock = Cache::lock('kara-ai:inflight:'.$hash, config('ai.ajil.timeout') + 5);
        if (! $lock->get()) {
            return ['state' => 'busy', 'facts' => $facts, 'meta' => $context['pulse'] ?? []];
        }
        $started = microtime(true);
        $requestId = (string) Str::uuid();
        try {
            // A request may have completed between the first cache lookup and
            // acquiring the lock. Reuse it instead of calling Ajil again.
            $cached = AiInsight::where('feature', $feature)->where('entity_id', $entityId)->where('input_hash', $hash)->where('expires_at', '>', now())->latest()->first();
            if ($cached && ! $force) {
                $this->run((string) Str::uuid(), $feature, $entityType, $entityId, $hash, $promptVersion, 'cached', true);

                return $this->readyPayload($cached, $facts, $context, $entityType, true);
            }
            $gateway = app(AjilGatewayClient::class)->complete($feature, $facts, $context);
            $data = app(AiResponseValidator::class)->validate($gateway['response'], $facts);
            $insight = AiInsight::create(['scope' => 'panel', 'entity_type' => $entityType, 'entity_id' => $entityId, 'feature' => $feature, 'prompt_version' => $promptVersion, 'input_hash' => $hash, 'response_json' => $data, 'generated_at' => now(), 'expires_at' => now()->addSeconds(config('ai.cache_ttl'))]);
            Cache::forget($this->circuitKey($feature));
            $this->run($requestId, $feature, $entityType, $entityId, $hash, $promptVersion, 'success', false, $gateway, (int) ((microtime(true) - $started) * 1000));

            return $this->readyPayload($insight, $facts, $context, $entityType, false);
        } catch (Throwable $e) {
            report($e);
            $this->recordFailure($feature);
            $this->run($requestId, $feature, $entityType, $entityId, $hash, $promptVersion, 'unavailable', false, [], (int) ((microtime(true) - $started) * 1000), class_basename($e));

            return $verifiedFallback;
        } finally {
            $lock->release();
        }
    }

    private function readyPayload(AiInsight $insight, array $facts, array $context, ?string $entityType, bool $cached): array
    {
        return [
            'state' => 'ready',
            'data' => $insight->response_json,
            'cached' => $cached,
            'insight_id' => $insight->id,
            'entity_type' => $entityType,
            'generated_at' => $insight->generated_at?->toIso8601String(),
            'expires_at' => $insight->expires_at?->toIso8601String(),
            'facts' => $facts,
            'meta' => $context['pulse'] ?? [],
        ];
    }

    private function payload(string $feature, ?int $entityId): array
    {
        $engine = app(AiFactEngine::class);

        return match ($feature) {
            'contract_brief' => $this->contractPayload($engine, $entityId),
            'customer_brief' => $this->customerPayload($engine, $entityId),
            'vehicle_brief' => $this->vehiclePayload($engine, $entityId),
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
        $statusAt = $contract->latestStatus()->value('created_at') ?? $contract->updated_at;
        $customerContracts = $this->contractCounts(Contract::query()->where('customer_id', $contract->customer_id));
        $previousContracts = Contract::query()->where('customer_id', $contract->customer_id)
            ->where('id', '!=', $contract->id)->select(['id', 'current_status', 'pickup_date', 'return_date'])
            ->latest('id')->limit(3)->get();

        return [$facts, [
            'contract_id' => $contract->id,
            'status' => $contract->current_status,
            'status_age_hours' => $statusAt ? (int) \Illuminate\Support\Carbon::parse($statusAt)->diffInHours(now()) : null,
            'pickup_at' => $contract->pickup_date?->toIso8601String(),
            'return_at' => $contract->return_date?->toIso8601String(),
            'actual_pickup_at' => $contract->actual_pickup_at?->toIso8601String(),
            'actual_return_at' => $contract->actual_return_at?->toIso8601String(),
            'duration_days' => $contract->pickup_date && $contract->return_date ? $contract->pickup_date->diffInDays($contract->return_date) : null,
            'total_price_aed' => (float) $contract->total_price,
            'customer_operational_profile' => [
                'status' => $contract->customer?->status,
                'total_contracts' => $customerContracts['total'],
                'previous_contracts' => max(0, $customerContracts['total'] - 1),
                'active_contracts' => $customerContracts['active'],
                'completed_contracts' => $customerContracts['completed'],
                'cancelled_or_rejected_contracts' => $customerContracts['cancelled'],
                'recent_contracts' => $previousContracts->map(fn (Contract $previous) => [
                    'contract_id' => $previous->id,
                    'status' => $previous->current_status,
                    'pickup_at' => $previous->pickup_date?->toIso8601String(),
                    'return_at' => $previous->return_date?->toIso8601String(),
                ])->values()->all(),
            ],
            'vehicle' => [
                'id' => $contract->car_id,
                'operational_status' => $contract->car?->operationalStatus(),
                'available' => $contract->car?->availability,
                'service_due_at' => $contract->car?->service_due_date?->toDateString(),
                'insurance_expires_at' => $contract->car?->latestInsurance?->expiry_date?->toDateString(),
                'registration_expires_at' => $contract->car?->expiry_date?->toDateString(),
            ],
            'documents' => [
                'customer_document_present' => $contract->customerDocument !== null,
                'pickup_document_present' => $contract->pickupDocument !== null,
                'return_document_present' => $contract->returnDocument !== null,
            ],
            'amendments' => $contract->amendments()->latest('id')->limit(3)->get()->map(fn ($amendment) => [
                'type' => $amendment->type,
                'status' => $amendment->status,
                'effective_at' => $amendment->effective_at?->toIso8601String(),
                'total_amount_aed' => (float) $amendment->total_amount,
            ])->values()->all(),
            'status_timeline' => $contract->statuses()->latest('created_at')->limit(6)->get()
                ->map(fn ($status) => [
                    'status' => $status->status,
                    'occurred_at' => $status->created_at?->toIso8601String(),
                ])->values()->all(),
            'pulse' => $engine->contractPulse($facts),
            'payment_summary' => [
                'transaction_count' => $payments->count(),
                'pending_count' => $payments->where('approval_status', 'pending')->count(),
                'charge_amount_aed' => (float) $payments->whereIn('payment_type', \App\Models\Payment::CHARGE_PAYMENT_TYPES)->sum('amount_in_aed'),
                'operational_balance_aed' => $contract->calculateRemainingBalance($payments),
                'by_type' => $payments->groupBy('payment_type')->map(fn ($group) => [
                    'count' => $group->count(),
                    'amount_aed' => (float) $group->sum('amount_in_aed'),
                ])->all(),
            ],
        ], 'contract'];
    }

    private function customerPayload(AiFactEngine $engine, ?int $id): array
    {
        $customer = Customer::findOrFail($id);
        $counts = $this->contractCounts($customer->contracts());
        $recent = $customer->contracts()->select(['id', 'current_status', 'pickup_date', 'return_date'])
            ->latest('id')->limit(3)->get()->map(fn (Contract $contract) => [
                'contract_id' => $contract->id,
                'status' => $contract->current_status,
                'pickup_at' => $contract->pickup_date?->toIso8601String(),
                'return_at' => $contract->return_date?->toIso8601String(),
            ])->all();

        return [$engine->customer($customer), [
            'customer_id' => $customer->id,
            'status' => $customer->status,
            'contract_counts' => $counts,
            'recent_contracts' => $recent,
            'passport_expired' => $customer->passport_expiry_date?->isPast(),
        ], 'customer'];
    }

    private function vehiclePayload(AiFactEngine $engine, ?int $id): array
    {
        $car = Car::findOrFail($id);
        $counts = $this->contractCounts(Contract::query()->where('car_id', $car->id));

        return [$engine->vehicle($car), [
            'vehicle_id' => $car->id,
            'operational_status' => $car->operationalStatus(),
            'available' => (bool) $car->availability,
            'service_due_at' => $car->service_due_date?->toDateString(),
            'insurance_expires_at' => $car->latestInsurance?->expiry_date?->toDateString(),
            'registration_expires_at' => $car->expiry_date?->toDateString(),
            'contract_counts' => $counts,
        ], 'vehicle'];
    }

    private function contractCounts($query): array
    {
        $row = $query->selectRaw("COUNT(*) as total, SUM(CASE WHEN current_status IN ('assigned','under_review','delivery','inspection','agreement_inspection','awaiting_return') THEN 1 ELSE 0 END) as active, SUM(CASE WHEN current_status = 'complete' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN current_status IN ('cancelled','rejected') THEN 1 ELSE 0 END) as cancelled")->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'active' => (int) ($row->active ?? 0),
            'completed' => (int) ($row->completed ?? 0),
            'cancelled' => (int) ($row->cancelled ?? 0),
        ];
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

    private function circuitKey(string $feature): string
    {
        return 'kara-ai:circuit:'.$feature;
    }

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
