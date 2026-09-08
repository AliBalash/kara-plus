<?php

namespace App\AI;

use App\Models\AuditEvent;
use App\Models\Contract;
use App\Models\Payment;
use Carbon\Carbon;

class AiFactEngine
{
    public function contract(Contract $contract): array
    {
        $contract->loadMissing(['payments', 'pickupDocument', 'returnDocument', 'customerDocument', 'amendments', 'car', 'latestStatus']);
        $facts = [];
        $add = function (string $type, int $severity, string $title, array $metrics = [], ?string $url = null) use (&$facts, $contract): void {
            $facts[] = ['fact_id' => strtoupper($type).':contract:'.$contract->id, 'type' => $type, 'severity' => $severity, 'entity_type' => 'contract', 'entity_id' => $contract->id, 'title' => $title, 'metrics' => $metrics, 'evidence_url' => $url];
        };
        $pending = (float) $contract->payments->where('approval_status', 'pending')->sum('amount_in_aed');
        if ($pending > 0) $add('pending_payment', 70, 'Pending payment requires review', ['amount_aed' => $pending], route('rental-requests.payment', [$contract->id, $contract->customer_id]));
        if (!$contract->pickupDocument && in_array($contract->current_status, ['delivery', 'awaiting_return', 'returned'], true)) $add('missing_pickup_document', 70, 'Pickup document is missing', [], route('rental-requests.pickup-document', $contract->id));
        if (!$contract->returnDocument && $contract->return_date?->isPast() && in_array($contract->current_status, ['awaiting_return', 'returned'], true)) $add('missing_return_document', 100, 'Return documentation is overdue', [], route('rental-requests.return-document', $contract->id));
        if ($contract->return_date?->isPast() && in_array($contract->current_status, ['delivery', 'awaiting_return'], true)) $add('overdue_return', 100, 'Planned return date has passed', ['hours_overdue' => (int) $contract->return_date->diffInHours(now())], route('rental-requests.details', $contract->id));
        return $facts;
    }

    public function contractPulse(array $facts): array
    {
        $penalty = collect($facts)->sum(function (array $fact): int {
            return match (true) {
                ($fact['severity'] ?? 0) >= 100 => 30,
                ($fact['severity'] ?? 0) >= 70 => 15,
                default => 5,
            };
        });
        $score = max(0, 100 - $penalty);
        $label = match (true) {
            $score >= 85 => 'Ready',
            $score >= 60 => 'Needs review',
            default => 'Needs attention',
        };

        return ['score' => $score, 'label' => $label, 'issues_count' => count($facts)];
    }

    public function dashboard(): array
    {
        $facts = [];
        $overdue = Contract::whereIn('current_status', ['delivery', 'awaiting_return'])->where('return_date', '<', now())->count();
        if ($overdue) $facts[] = $this->fact('overdue_returns', 100, 'Overdue returns require attention', ['count' => $overdue], route('expert.dashboard'));
        $pending = Payment::where('approval_status', 'pending')->selectRaw('COUNT(*) as count, COALESCE(SUM(amount_in_aed),0) as amount')->first();
        if ($pending?->count) $facts[] = $this->fact('pending_payments', 70, 'Pending payments require review', ['count' => (int) $pending->count, 'amount_aed' => (float) $pending->amount], route('rental-requests.confirm-payment-list'));
        $pickup = Contract::whereBetween('pickup_date', [now(), now()->addDay()])->count();
        if ($pickup) $facts[] = $this->fact('upcoming_pickups', 40, 'Pickups scheduled in the next 24 hours', ['count' => $pickup], route('rental-requests.awaiting.pickup'));
        return $facts;
    }

    public function payments(): array
    {
        $facts = [];
        $rows = Payment::where('approval_status', 'pending')->selectRaw('DATE(payment_date) as payment_day, COUNT(*) as count, COALESCE(SUM(amount_in_aed),0) as amount')->groupBy('payment_day')->orderBy('payment_day')->limit(10)->get();
        foreach ($rows as $row) {
            $age = $row->payment_day ? Carbon::parse($row->payment_day)->diffInHours(now()) : 0;
            $facts[] = $this->fact('payment_aging:'.$row->payment_day, $age > 72 ? 100 : 70, 'Pending payment batch', ['count' => (int) $row->count, 'amount_aed' => (float) $row->amount, 'age_hours' => $age], route('rental-requests.confirm-payment-list'));
        }
        return $facts;
    }

    public function changes(?int $userId): array
    {
        if (!$userId) return [];
        $previousLogin = AuditEvent::where('actor_user_id', $userId)
            ->where('action', 'auth_login_success')
            ->orderByDesc('occurred_at')
            ->skip(1)
            ->value('occurred_at');
        $from = $previousLogin ? Carbon::parse($previousLogin) : now()->subDay();
        $rows = AuditEvent::where('occurred_at', '>', $from)
            ->where('actor_user_id', '!=', $userId)
            ->selectRaw('entity_type, action, COUNT(*) as event_count')
            ->groupBy('entity_type', 'action')
            ->orderByDesc('event_count')
            ->limit(10)
            ->get();

        return $rows->map(fn ($row) => $this->fact(
            'change:'.strtolower((string) $row->entity_type).':'.$row->action,
            40,
            'Operational changes since your previous login',
            ['entity_type' => $row->entity_type, 'action' => $row->action, 'event_count' => (int) $row->event_count, 'since' => $from->toIso8601String()],
            route('expert.dashboard'),
            'changes'
        ))->all();
    }

    private function fact(string $type, int $severity, string $title, array $metrics, string $url, string $entityType = 'dashboard'): array
    {
        return ['fact_id' => strtoupper($entityType).':'.$type, 'type' => $type, 'severity' => $severity, 'entity_type' => $entityType, 'entity_id' => null, 'title' => $title, 'metrics' => $metrics, 'evidence_url' => $url];
    }
}
