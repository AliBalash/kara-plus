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
        $contract->loadMissing(['payments', 'pickupDocument', 'returnDocument', 'customerDocument', 'amendments', 'car']);
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
        $count = AuditEvent::where('actor_user_id', $userId)->where('occurred_at', '>=', now()->subDay())->count();
        return $count ? [$this->fact('recent_changes', 40, 'Recent operations activity', ['event_count' => $count], route('reports.audit-center'))] : [];
    }

    private function fact(string $type, int $severity, string $title, array $metrics, string $url): array
    {
        return ['fact_id' => 'DASHBOARD:'.$type, 'type' => $type, 'severity' => $severity, 'entity_type' => 'dashboard', 'entity_id' => null, 'title' => $title, 'metrics' => $metrics, 'evidence_url' => $url];
    }
}
