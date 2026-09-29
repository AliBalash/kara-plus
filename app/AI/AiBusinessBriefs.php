<?php

namespace App\AI;

use App\Models\Car;
use App\Models\Contract;
use App\Models\Insurance;
use App\Services\Reservations\ReviewReservationApprovalService;
use Illuminate\Support\Facades\Auth;

/** Builds small, read-only business facts before any text is sent to Ajil. */
class AiBusinessBriefs
{
    public function reservation(int $id): array
    {
        $contract = Contract::query()->with('car')->findOrFail($id);
        abort_unless($contract->isReviewPending() && $contract->isWebsiteIntake(), 404);

        $url = route('rental-requests.edit', $contract->id);
        $diagnostic = app(ReviewReservationApprovalService::class)->diagnostics($contract);
        $facts = [];
        foreach ($diagnostic['issues'] as $issue) {
            $facts[] = $this->fact('reservation', $issue['code'], 85, $issue['title'], ['detail' => $issue['detail']], $url, $id);
        }
        if ($diagnostic['ready']) {
            $facts[] = $this->fact('reservation', 'checks_clear', 20, 'Current reservation checks found no blocker', [], $url, $id);
        }
        if ($contract->pickup_date?->isPast()) {
            $facts[] = $this->fact('reservation', 'pickup_elapsed', 70, 'Requested pickup time has passed', ['pickup_at' => $contract->pickup_date->toIso8601String()], $url, $id);
        }
        if ($contract->requested_car_id && $contract->requested_car_id !== $contract->car_id) {
            $facts[] = $this->fact('reservation', 'vehicle_changed', 55, 'Review vehicle differs from requested vehicle', ['requested_car_id' => $contract->requested_car_id, 'review_car_id' => $contract->car_id], $url, $id);
        }
        if ($contract->user_id && $contract->user_id !== Auth::id()) {
            $facts[] = $this->fact('reservation', 'owned_by_other', 55, 'Request belongs to another expert', [], $url, $id);
        }
        $quotedTotal = data_get($contract->meta, 'quote_snapshot.final_total');
        if (is_numeric($quotedTotal) && abs((float) $quotedTotal - (float) $contract->total_price) >= 0.01) {
            $facts[] = $this->fact('reservation', 'quote_changed', 70, 'Saved total differs from website quote snapshot', ['quote_aed' => round((float) $quotedTotal, 2), 'saved_total_aed' => round((float) $contract->total_price, 2)], $url, $id);
        }

        return [$facts, [
            'request_id' => $id,
            'submitted_at' => $contract->created_at?->toIso8601String(),
            'pickup_at' => $contract->pickup_date?->toIso8601String(),
            'return_at' => $contract->return_date?->toIso8601String(),
            'requested_car_id' => $contract->requested_car_id,
            'review_car_id' => $contract->car_id,
            'quoted_total_aed' => is_numeric($quotedTotal) ? round((float) $quotedTotal, 2) : null,
            'saved_total_aed' => round((float) $contract->total_price, 2),
            'checks_clear_now' => $diagnostic['ready'],
            'assigned_to_current_expert' => $contract->user_id === Auth::id(),
        ], 'reservation'];
    }

    public function reservationQueue(): array
    {
        $base = Contract::query()->where('current_status', Contract::STATUS_REVIEW_PENDING)
            ->where('intake_source', Contract::INTAKE_SOURCE_WEBSITE);
        $url = route('rental-requests.website-review');
        $total = (clone $base)->count();
        $unassigned = (clone $base)->whereNull('user_id')->count();
        $mine = Auth::id() ? (clone $base)->where('user_id', Auth::id())->count() : 0;
        $old = (clone $base)->where('created_at', '<', now()->subDay())->count();
        $facts = [];
        if ($total) {
            $facts[] = $this->fact('reservation_queue', 'open', 55, 'Website requests await expert review', ['count' => $total], $url);
        }
        if ($unassigned) {
            $facts[] = $this->fact('reservation_queue', 'unassigned', 70, 'Website requests have no owner', ['count' => $unassigned], route('rental-requests.website-review', ['assignmentFilter' => 'unassigned']));
        }
        if ($old) {
            $facts[] = $this->fact('reservation_queue', 'older_than_day', 70, 'Website requests have waited over 24 hours', ['count' => $old], $url);
        }
        if ($mine) {
            $facts[] = $this->fact('reservation_queue', 'mine', 40, 'Your website requests need review', ['count' => $mine], route('rental-requests.website-review', ['assignmentFilter' => 'mine']));
        }
        foreach ((clone $base)->orderBy('created_at')->limit(5)->get(['id', 'user_id', 'created_at', 'pickup_date']) as $request) {
            $pickupPassed = $request->pickup_date?->isPast() ?? false;
            $pickupSoon = $request->pickup_date?->between(now(), now()->addDay()) ?? false;
            $facts[] = $this->fact('reservation_queue', 'request_waiting', $pickupPassed ? 85 : ($pickupSoon ? 75 : 50),
                'Website request #'.$request->id.' needs review', [
                    'waiting_hours' => $request->created_at ? (int) $request->created_at->diffInHours(now()) : null,
                    'pickup_at' => $request->pickup_date?->toIso8601String(),
                    'assigned' => $request->user_id !== null,
                ], route('rental-requests.edit', $request->id), $request->id);
        }
        if (! $facts) {
            $facts[] = $this->fact('reservation_queue', 'clear', 10, 'No website requests await review', ['count' => 0], $url);
        }

        return [$facts, ['open_count' => $total, 'unassigned_count' => $unassigned, 'assigned_to_me_count' => $mine, 'older_than_24h_count' => $old], 'reservation_queue'];
    }

    public function finance(int $id): array
    {
        $contract = Contract::findOrFail($id);
        $contract->load(['incomingBalanceTransfers:id,to_contract_id,amount,transferred_at', 'outgoingBalanceTransfers:id,from_contract_id,amount,transferred_at']);
        $payments = $contract->payments()->get(['id', 'contract_id', 'payment_type', 'amount_in_aed', 'approval_status', 'payment_date']);
        $balance = (float) $contract->calculateRemainingBalance($payments);
        $pending = $payments->where('approval_status', 'pending');
        $byType = $payments->groupBy('payment_type')->map(fn ($rows) => [
            'count' => $rows->count(),
            'amount_aed' => round((float) $rows->sum('amount_in_aed'), 2),
            'pending_count' => $rows->where('approval_status', 'pending')->count(),
        ])->all();
        $url = route('rental-requests.payment', [$contract->id, $contract->customer_id]);
        $facts = [
            $this->fact('finance', 'balance', 50, 'Calculated operational contract balance', ['amount_aed' => $balance], $url, $id),
        ];
        if ($pending->isNotEmpty()) {
            $facts[] = $this->fact('finance', 'pending_approval', 70, 'Ledger entries await approval', ['count' => $pending->count(), 'types' => $pending->groupBy('payment_type')->map->count()->all()], $url, $id);
        }
        if ($contract->incomingBalanceTransfers->isNotEmpty() || $contract->outgoingBalanceTransfers->isNotEmpty()) {
            $facts[] = $this->fact('finance', 'balance_transfers', 55, 'Balance transfers affect this contract', [
                'incoming_aed' => round((float) $contract->incomingBalanceTransfers->sum('amount'), 2),
                'outgoing_aed' => round((float) $contract->outgoingBalanceTransfers->sum('amount'), 2),
            ], route('rental-requests.balance-transfer', $id), $id);
        }
        if ($payments->where('payment_type', 'discount')->isNotEmpty()) {
            $facts[] = $this->fact('finance', 'discounts', 40, 'Discount ledger entries affect the balance', ['count' => $payments->where('payment_type', 'discount')->count()], $url, $id);
        }
        if ($payments->where('payment_type', 'payment_back')->isNotEmpty()) {
            $facts[] = $this->fact('finance', 'refunds', 40, 'Payment-back entries affect the balance', ['count' => $payments->where('payment_type', 'payment_back')->count()], $url, $id);
        }

        return [$facts, [
            'contract_id' => $id,
            'status' => $contract->current_status,
            'total_price_aed' => round((float) $contract->total_price, 2),
            'operational_balance_aed' => $balance,
            'ledger_includes_pending_entries' => true,
            'payment_types' => $byType,
            'recent_ledger_entries' => $payments->sortByDesc('id')->take(5)->map(fn ($payment) => [
                'type' => $payment->payment_type,
                'approval_status' => $payment->approval_status,
                'amount_aed' => round((float) $payment->amount_in_aed, 2),
                'payment_date' => $payment->payment_date?->toDateString(),
            ])->values()->all(),
            'incoming_transfer_aed' => round((float) $contract->incomingBalanceTransfers->sum('amount'), 2),
            'outgoing_transfer_aed' => round((float) $contract->outgoingBalanceTransfers->sum('amount'), 2),
            'recent_balance_transfers' => $contract->incomingBalanceTransfers->map(fn ($transfer) => [
                'direction' => 'incoming',
                'amount_aed' => round((float) $transfer->amount, 2),
                'at' => $transfer->transferred_at?->toIso8601String(),
            ])->concat($contract->outgoingBalanceTransfers->map(fn ($transfer) => [
                'direction' => 'outgoing',
                'amount_aed' => round((float) $transfer->amount, 2),
                'at' => $transfer->transferred_at?->toIso8601String(),
            ]))->sortByDesc('at')->take(5)->values()->all(),
        ], 'contract'];
    }

    public function fleetOutlook(): array
    {
        $start = now();
        $end = now()->addDays(7);
        $today = $start->toDateString();
        $endDate = $end->toDateString();
        $openStatuses = ['reserved', 'assigned', 'under_review', 'delivery', 'awaiting_return'];
        $pickups = Contract::query()->whereIn('current_status', $openStatuses)->whereBetween('pickup_date', [$start, $end])->count();
        $returns = Contract::query()->whereIn('current_status', $openStatuses)->whereBetween('return_date', [$start, $end])->count();
        $overdueQuery = Contract::query()->whereIn('current_status', ['delivery', 'awaiting_return'])->where('return_date', '<', $start);
        $overdue = (clone $overdueQuery)->count();
        $servicePast = Car::query()->where('service_due_date', '<', $today)->count();
        $serviceNext = Car::query()->whereBetween('service_due_date', [$today, $endDate])->count();
        $latestInsuranceIds = Insurance::query()->selectRaw('MAX(id)')->groupBy('car_id');
        $insurancePast = Insurance::query()->whereIn('id', clone $latestInsuranceIds)->where('expiry_date', '<', $today)->count();
        $insuranceNext = Insurance::query()->whereIn('id', clone $latestInsuranceIds)->whereBetween('expiry_date', [$today, $endDate])->count();
        $missingInsurance = Car::query()->where('is_company_car', true)->whereDoesntHave('insurance')->count();
        $missingService = Car::query()->where('is_company_car', true)->whereNull('service_due_date')->count();
        $unavailable = Car::query()->whereIn('status', [Car::STATUS_UNAVAILABLE, Car::STATUS_SOLD])->count();
        $facts = [];
        foreach ((clone $overdueQuery)->orderBy('return_date')->limit(5)->get(['id', 'car_id', 'return_date']) as $contract) {
            $facts[] = $this->fact('fleet', 'overdue_return', 100, 'Open vehicle return is overdue', ['car_id' => $contract->car_id, 'return_at' => $contract->return_date?->toIso8601String()], route('rental-requests.details', $contract->id), $contract->id);
        }
        if ($overdue > 5) {
            $facts[] = $this->fact('fleet', 'more_overdue', 80, 'Additional vehicle returns are overdue', ['count' => $overdue - 5], route('rental-requests.awaiting.return'));
        }
        foreach ([
            ['pickups', $pickups, 45, 'Vehicle pickups in the next 7 days', route('rental-requests.awaiting.pickup')],
            ['returns', $returns, 45, 'Vehicle returns in the next 7 days', route('rental-requests.awaiting.return')],
            ['service_past', $servicePast, 50, 'Past recorded service dates need verification', route('car.list')],
            ['service_next', $serviceNext, 55, 'Recorded service dates in the next 7 days', route('car.list')],
            ['insurance_past', $insurancePast, 65, 'Past recorded insurance expiries need verification', route('insurance.list')],
            ['insurance_next', $insuranceNext, 70, 'Recorded insurance expiries in the next 7 days', route('insurance.list')],
            ['missing_insurance', $missingInsurance, 45, 'Company cars have no insurance record', route('car.list')],
            ['missing_service', $missingService, 40, 'Company cars have no service date', route('car.list')],
            ['unavailable', $unavailable, 40, 'Fleet vehicles have an unavailable status', route('car.list')],
        ] as [$type, $count, $severity, $title, $url]) {
            if ($count) {
                $facts[] = $this->fact('fleet', $type, $severity, $title, ['count' => $count], $url);
            }
        }
        if (! $facts) {
            $facts[] = $this->fact('fleet', 'clear', 10, 'No fleet exceptions found in the 7-day outlook', ['count' => 0], route('car.list'));
        }

        return [$facts, [
            'window_start' => $start->toIso8601String(),
            'window_end' => $end->toIso8601String(),
            'overdue_returns_total' => $overdue,
            'pickups_next_7_days' => $pickups,
            'returns_next_7_days' => $returns,
            'past_recorded_service_dates' => $servicePast,
            'service_dates_next_7_days' => $serviceNext,
            'past_recorded_insurance_expiries' => $insurancePast,
            'insurance_expiries_next_7_days' => $insuranceNext,
            'company_cars_missing_insurance_record' => $missingInsurance,
            'company_cars_missing_service_date' => $missingService,
            'unavailable_vehicle_count' => $unavailable,
        ], 'fleet'];
    }

    private function fact(string $scope, string $type, int $severity, string $title, array $metrics, string $url, ?int $id = null): array
    {
        return [
            'fact_id' => strtoupper($scope).':'.$type.($id ? ':'.$id : ''),
            'type' => $type,
            'severity' => $severity,
            'entity_type' => $scope,
            'entity_id' => $id,
            'title' => $title,
            'metrics' => $metrics,
            'evidence_url' => $url,
        ];
    }
}
