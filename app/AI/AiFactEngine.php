<?php

namespace App\AI;

use App\Models\AuditEvent;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Insurance;
use App\Models\Payment;
use Carbon\Carbon;

class AiFactEngine
{
    public function contract(Contract $contract): array
    {
        $contract->loadMissing(['payments', 'pickupDocument', 'returnDocument', 'customerDocument', 'car.latestInsurance', 'customer']);
        $facts = [];
        $add = function (string $type, int $severity, string $title, array $metrics = [], ?string $url = null) use (&$facts, $contract): void {
            $facts[] = ['fact_id' => strtoupper($type).':contract:'.$contract->id, 'type' => $type, 'severity' => $severity, 'entity_type' => 'contract', 'entity_id' => $contract->id, 'title' => $title, 'metrics' => $metrics, 'evidence_url' => $url];
        };
        $pending = (float) $contract->payments->where('approval_status', 'pending')->sum('amount_in_aed');
        if ($pending > 0) {
            $add('pending_payment', 70, 'Pending payment requires review', ['amount_aed' => $pending], route('rental-requests.payment', [$contract->id, $contract->customer_id]));
        }
        if (! $contract->pickupDocument && in_array($contract->current_status, ['delivery', 'awaiting_return', 'returned'], true)) {
            $add('missing_pickup_document', 70, 'Pickup document is missing', [], route('rental-requests.pickup-document', $contract->id));
        }
        if (! $contract->returnDocument && $contract->return_date?->isPast() && in_array($contract->current_status, ['awaiting_return', 'returned'], true)) {
            $add('missing_return_document', 100, 'Return documentation is overdue', [], route('rental-requests.return-document', $contract->id));
        }
        if ($contract->return_date?->isPast() && in_array($contract->current_status, ['delivery', 'awaiting_return'], true)) {
            $add('overdue_return', 100, 'Planned return date has passed', ['hours_overdue' => (int) $contract->return_date->diffInHours(now())], route('rental-requests.details', $contract->id));
        }
        if (! $contract->customerDocument) {
            $add('missing_customer_document', 40, 'Customer document has not been attached', [], route('rental-requests.edit', $contract->id));
        }
        $pendingAmendments = $contract->amendments()->where('status', 'pending_approval')->count();
        if ($pendingAmendments) {
            $add('pending_amendment', 40, 'Contract amendment is waiting for approval', ['count' => $pendingAmendments], route('rental-requests.edit', $contract->id));
        }
        if ($contract->car && in_array($contract->car->operationalStatus(), [Car::STATUS_UNAVAILABLE, Car::STATUS_SOLD], true) && in_array($contract->current_status, ['assigned', 'under_review'], true)) {
            $add('vehicle_unavailable', 70, 'Assigned vehicle is not currently available', [], route('rental-requests.edit', $contract->id));
        }
        if ($contract->car?->latestInsurance?->expiry_date?->isBefore(now()->startOfDay())) {
            $add('vehicle_insurance_expired', 85, 'Vehicle insurance has expired', [], route('car.detail', $contract->car_id));
        }
        if ($contract->car?->service_due_date?->isPast()) {
            $add('vehicle_service_due', 55, 'Vehicle service date has passed', [], route('car.detail', $contract->car_id));
        }

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

    public function customer(Customer $customer): array
    {
        $facts = [];
        $url = route('customer.detail', $customer->id);
        if ($customer->passport_expiry_date?->isPast()) {
            $facts[] = $this->fact('passport_expired', 85, 'Customer passport has expired', [], $url, 'customer');
        } elseif ($customer->passport_expiry_date?->between(now(), now()->addDays(30))) {
            $facts[] = $this->fact('passport_expiring', 55, 'Customer passport expires within 30 days', [], $url, 'customer');
        }
        $pending = $customer->payments()->where('approval_status', 'pending')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount_in_aed),0) as amount')->first();
        if ($pending?->count) {
            $facts[] = $this->fact('pending_payments', 70, 'Customer has pending payments', ['count' => (int) $pending->count, 'amount_aed' => (float) $pending->amount], route('rental-requests.confirm-payment-list'), 'customer');
        }
        $overdue = $customer->contracts()->whereIn('current_status', ['delivery', 'awaiting_return'])->where('return_date', '<', now())->count();
        if ($overdue) {
            $facts[] = $this->fact('overdue_returns', 100, 'Customer has overdue returns', ['count' => $overdue], route('customer.history', $customer->id), 'customer');
        }

        return $facts ?: [$this->fact('profile_clear', 10, 'No verified customer alerts', [], $url, 'customer')];
    }

    public function vehicle(Car $car): array
    {
        $car->loadMissing('latestInsurance');
        $facts = [];
        $url = route('car.detail', $car->id);
        if (in_array($car->operationalStatus(), [Car::STATUS_UNAVAILABLE, Car::STATUS_SOLD], true)) {
            $facts[] = $this->fact('vehicle_unavailable', 70, 'Vehicle cannot be dispatched now', ['operational_status' => $car->operationalStatus()], $url, 'vehicle');
        }
        if ($car->latestInsurance?->expiry_date?->isBefore(now()->startOfDay())) {
            $facts[] = $this->fact('insurance_expired', 100, 'Vehicle insurance has expired', [], $url, 'vehicle');
        } elseif ($car->latestInsurance?->expiry_date?->between(now()->startOfDay(), now()->addDays(30)->endOfDay())) {
            $facts[] = $this->fact('insurance_expiring', 55, 'Vehicle insurance expires within 30 days', [], $url, 'vehicle');
        }
        if ($car->service_due_date?->isPast()) {
            $facts[] = $this->fact('service_due', 70, 'Vehicle service date has passed', [], $url, 'vehicle');
        }
        $overdue = Contract::where('car_id', $car->id)->whereIn('current_status', ['delivery', 'awaiting_return'])->where('return_date', '<', now())->count();
        if ($overdue) {
            $facts[] = $this->fact('overdue_returns', 100, 'Vehicle has an overdue open contract', ['count' => $overdue], $url, 'vehicle');
        }

        return $facts ?: [$this->fact('vehicle_clear', 10, 'No verified vehicle alerts', [], $url, 'vehicle')];
    }

    public function dashboard(): array
    {
        $facts = [];

        // Critical: overdue returns — کارشناس باید فورا ببیند
        $overdue = Contract::whereIn('current_status', ['delivery', 'awaiting_return'])->where('return_date', '<', now())->count();
        if ($overdue) {
            $facts[] = $this->fact('overdue_returns', 100, 'Overdue returns require attention', ['count' => $overdue], route('expert.dashboard'));
        }

        // Critical: pending payments — پول معلق
        $pending = Payment::where('approval_status', 'pending')->selectRaw('COUNT(*) as count, COALESCE(SUM(amount_in_aed),0) as amount')->first();
        if ($pending?->count) {
            $facts[] = $this->fact('pending_payments', 85, 'Pending payments require review', ['count' => (int) $pending->count, 'amount_aed' => (float) $pending->amount], route('rental-requests.confirm-payment-list'));
        }

        $latestInsuranceIds = Insurance::selectRaw('MAX(id)')->groupBy('car_id');
        $expiredInsurance = Insurance::whereIn('id', $latestInsuranceIds)->whereDate('expiry_date', '<', now()->toDateString())->count();
        if ($expiredInsurance) {
            $facts[] = $this->fact('expired_insurance', 85, 'Vehicle insurance renewals require attention', ['count' => $expiredInsurance], route('insurance.list'));
        }
        $serviceDue = Car::whereDate('service_due_date', '<=', now()->toDateString())->count();
        if ($serviceDue) {
            $facts[] = $this->fact('service_due', 55, 'Vehicle service dates require review', ['count' => $serviceDue], route('car.list'));
        }

        // Today: pickups / returns — کار روزانه کارشناس
        $pickupToday = Contract::whereBetween('pickup_date', [now()->startOfDay(), now()->endOfDay()])->count();
        if ($pickupToday) {
            $facts[] = $this->fact('pickups_today', 55, 'Pickups scheduled for today', ['count' => $pickupToday], route('rental-requests.awaiting.pickup'));
        }
        $returnToday = Contract::whereBetween('return_date', [now()->startOfDay(), now()->endOfDay()])->whereIn('current_status', ['awaiting_return', 'delivery'])->count();
        if ($returnToday) {
            $facts[] = $this->fact('returns_today', 55, 'Returns scheduled for today', ['count' => $returnToday], route('rental-requests.awaiting.return'));
        }
        $pickupTomorrow = Contract::whereBetween('pickup_date', [now()->addDay()->startOfDay(), now()->addDay()->endOfDay()])->count();
        if ($pickupTomorrow && ! $pickupToday) {
            $facts[] = $this->fact('upcoming_pickups', 40, 'Pickups scheduled in the next 24 hours', ['count' => $pickupTomorrow], route('rental-requests.awaiting.pickup'));
        }

        // Business growth — لید و شارژ جدید (۲۴ ساعت اخیر)
        try {
            $newLeads = \App\Models\Lead::where('created_at', '>=', now()->subDay())->count();
            if ($newLeads) {
                $facts[] = $this->fact('new_leads_24h', 30, 'New leads in the last 24 hours', ['count' => $newLeads], route('leads.index'));
            }
        } catch (\Throwable $e) {
        }
        try {
            $newCharges = \App\Models\ContractCharges::where('created_at', '>=', now()->subDay())->count();
            if ($newCharges) {
                $facts[] = $this->fact('new_charges_24h', 30, 'New contract charges in the last 24 hours', ['count' => $newCharges], route('rental-requests.list'));
            }
        } catch (\Throwable $e) {
        }
        try {
            $pendingAmendments = \App\Models\ContractAmendment::where('status', 'pending_approval')->count();
            if ($pendingAmendments) {
                $facts[] = $this->fact('pending_amendments', 60, 'Contract amendments waiting for approval', ['count' => $pendingAmendments], route('rental-requests.list'));
            }
        } catch (\Throwable $e) {
        }

        // Fleet health — خودروهای نیازمند تصمیم
        try {
            $needAction = \App\Models\Car::where('status', 'unavailable')->count();
            if ($needAction) {
                $facts[] = $this->fact('fleet_attention', 50, 'Vehicles requiring attention', ['count' => $needAction], route('car.list'));
            }
        } catch (\Throwable $e) {
        }

        // If still empty, give a positive empty state so AI can say "all clear"
        if (empty($facts)) {
            $facts[] = $this->fact('operations_clear', 10, 'Operations are stable — no urgent items', ['count' => 0], route('expert.dashboard'));
        }

        usort($facts, fn (array $a, array $b) => $b['severity'] <=> $a['severity']);

        return array_slice($facts, 0, 8);
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
        if (! $userId) {
            return [];
        }
        $previousLogin = AuditEvent::where('actor_user_id', $userId)
            ->where('action', 'auth_login_success')
            ->orderByDesc('occurred_at')
            ->skip(1)
            ->value('occurred_at');
        $from = $previousLogin ? Carbon::parse($previousLogin) : now()->subDay();

        // Only business-meaningful audit events — hide infra noise (http_request, livewire_call, business_read)
        $businessActions = ['model_created', 'model_updated', 'model_deleted', 'status_changed', 'document_uploaded', 'payment_processed'];
        $rows = AuditEvent::where('occurred_at', '>', $from)
            ->where(function ($q) use ($userId) {
                $q->where('actor_user_id', '!=', $userId)->orWhereNull('actor_user_id');
            })
            ->whereNotNull('entity_type')
            ->where('entity_type', 'like', 'App%')
            ->whereIn('action', $businessActions)
            ->selectRaw('entity_type, action, COUNT(*) as event_count')
            ->groupBy('entity_type', 'action')
            ->orderByDesc('event_count')
            ->limit(10)
            ->get();

        // Fallback: if no business changes, still show a helpful empty fact so AI can explain "no changes"
        if ($rows->isEmpty()) {
            return [$this->fact(
                'change:none',
                10,
                'No business changes since your previous login',
                ['event_count' => 0, 'since' => $from->toIso8601String(), 'since_human' => $from->diffForHumans()],
                route('expert.dashboard'),
                'changes'
            )];
        }

        return $rows->map(function ($row) use ($from) {
            $short = class_basename((string) $row->entity_type); // e.g. Contract, Payment
            $actionLabel = match ($row->action) {
                'model_created' => 'created',
                'model_updated' => 'updated',
                'model_deleted' => 'deleted',
                'status_changed' => 'status changed',
                'document_uploaded' => 'document uploaded',
                'payment_processed' => 'payment processed',
                default => str_replace('_', ' ', (string) $row->action),
            };
            $severity = match (true) {
                $row->action === 'model_created' && in_array($short, ['Payment', 'ContractStatus', 'Contract'], true) => 50,
                $row->action === 'model_updated' && $short === 'Contract' => 45,
                default => 35,
            };

            return $this->fact(
                'change:'.strtolower($short).':'.$row->action,
                $severity,
                trim($short.' '.$actionLabel),
                [
                    'entity_type' => $short,
                    'full_entity_type' => $row->entity_type,
                    'action' => $row->action,
                    'action_label' => $actionLabel,
                    'event_count' => (int) $row->event_count,
                    'since' => $from->toIso8601String(),
                    'since_human' => $from->diffForHumans(),
                ],
                $this->resolveEvidenceUrl($short),
                'changes'
            );
        })->all();
    }

    private function resolveEvidenceUrl(string $short): string
    {
        return match ($short) {
            'Contract', 'ContractStatus', 'ContractCharges', 'ContractAmendment' => route('rental-requests.list'),
            'Payment' => route('rental-requests.confirm-payment-list'),
            'PickupDocument' => route('rental-requests.tars-inspection-list'),
            'ReturnDocument' => route('rental-requests.awaiting.return'),
            'Lead' => route('leads.index'),
            'Customer' => route('customer.list'),
            'Car' => route('car.list'),
            'User' => route('users.create'),
            default => route('expert.dashboard'),
        };
    }

    private function fact(string $type, int $severity, string $title, array $metrics, string $url, string $entityType = 'dashboard'): array
    {
        return ['fact_id' => strtoupper($entityType).':'.$type, 'type' => $type, 'severity' => $severity, 'entity_type' => $entityType, 'entity_id' => null, 'title' => $title, 'metrics' => $metrics, 'evidence_url' => $url];
    }
}
