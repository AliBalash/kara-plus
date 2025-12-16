<?php

namespace App\Livewire\Pages\Panel\Expert\Customer;

use App\Models\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class CustomerDebt extends Component
{
    public Customer $customer;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $debtContracts = [];

    public array $debtTotals = [];

    public array $insights = [];

    public string $statusFilter = 'all';

    public function mount(int $customerId): void
    {
        $this->customer = Customer::with(['contracts.car.carModel', 'contracts.payments'])->findOrFail($customerId);
        $this->prepareDebtSnapshot();
    }

    public function updatedStatusFilter(): void
    {
        $this->prepareDebtSnapshot();
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.customer.customer-debt');
    }

    protected function prepareDebtSnapshot(): void
    {
        $this->customer->loadMissing(['contracts.car.carModel', 'contracts.payments']);

        $contracts = $this->customer->contracts
            ->sortByDesc(function ($contract) {
                $date = $contract->pickup_date ?? $contract->created_at;

                if ($date instanceof Carbon) {
                    return $date->timestamp;
                }

                if ($date instanceof \DateTimeInterface) {
                    return $date->getTimestamp();
                }

                if (is_string($date)) {
                    return strtotime($date) ?: 0;
                }

                return 0;
            })
            ->map(fn($contract) => $this->mapContractDebt($contract));

        $this->debtTotals = $this->buildDebtTotals($contracts);
        $this->insights = $this->buildInsights($contracts);

        $filtered = $contracts;

        if ($this->statusFilter !== 'all') {
            $filtered = $contracts->filter(function (array $contract) {
                if ($this->statusFilter === 'open') {
                    return $contract['status'] === 'open';
                }

                if ($this->statusFilter === 'overdue') {
                    return $contract['status'] === 'overdue';
                }

                if ($this->statusFilter === 'settled') {
                    return $contract['status'] === 'settled';
                }

                if ($this->statusFilter === 'credit') {
                    return $contract['status'] === 'credit';
                }

                return true;
            });
        }

        $this->debtContracts = $filtered->values()->all();
    }

    protected function mapContractDebt($contract): array
    {
        $contract->loadMissing(['payments']);
        $payments = $contract->payments ?? collect();

        $sum = fn(array $types): float => (float) $payments
            ->whereIn('payment_type', $types)
            ->sum(fn($payment) => (float) ($payment->amount_in_aed ?? $payment->amount ?? 0));

        $rentalPaid = $sum(['rental_fee']);
        $depositPaid = $sum(['security_deposit']);
        $fines = $sum(['fine', 'parking', 'damage']);
        $extras = $sum([
            'salik',
            'salik_4_aed',
            'salik_6_aed',
            'salik_other_revenue',
            'carwash',
            'fuel',
            'no_deposit_fee',
        ]);
        $discounts = $sum(['discount']);
        $refunds = $sum(['payment_back']);

        $totalPaid = $rentalPaid + $depositPaid + $fines + $extras;

        $balance = (float) $contract->calculateRemainingBalance($payments);
        $outstanding = max($balance, 0);
        $credit = $balance < 0 ? abs($balance) : 0.0;
        $hasReturned = $contract->return_date instanceof Carbon;
        $isOverdue = $outstanding > 0 && $hasReturned && $contract->return_date->isPast();

        $status = 'settled';

        if ($outstanding > 0) {
            $status = $isOverdue ? 'overdue' : 'open';
        } elseif ($credit > 0) {
            $status = 'credit';
        }

        $progressBase = (float) ($contract->total_price ?? 0);
        $progress = $progressBase > 0 ? round(min(($totalPaid / $progressBase) * 100, 150), 1) : ($outstanding > 0 ? 0 : 100);

        $risk = $this->defineRiskLevel($status, $outstanding);

        $updatedAt = $payments->sortByDesc('payment_date')->first()?->payment_date;
        $latestPayment = $updatedAt instanceof Carbon ? $updatedAt->format('d M Y') : null;

        return [
            'id' => $contract->id,
            'label' => 'Contract #' . $contract->id,
            'car' => optional($contract->car)->fullName() ?? '—',
            'pickup_date' => optional($contract->pickup_date)?->format('d M Y'),
            'return_date' => optional($contract->return_date)?->format('d M Y'),
            'current_status' => $contract->current_status,
            'total' => (float) ($contract->total_price ?? 0),
            'paid' => $totalPaid,
            'outstanding' => $outstanding,
            'credit' => $credit,
            'discounts' => $discounts,
            'refunds' => $refunds,
            'fines' => $fines,
            'extras' => $extras,
            'deposit' => $depositPaid,
            'status' => $status,
            'progress' => $progress,
            'risk' => $risk,
            'latest_payment' => $latestPayment,
            'notes' => $contract->notes,
            'timeline_days' => $this->calculateOutstandingDays($contract),
        ];
    }

    protected function buildDebtTotals(Collection $contracts): array
    {
        $totalOutstanding = round($contracts->sum('outstanding'), 2);
        $openContracts = $contracts->where('status', 'open')->count();
        $overdueContracts = $contracts->where('status', 'overdue')->count();
        $credit = round($contracts->sum('credit'), 2);
        $largestDebtContract = $contracts->sortByDesc('outstanding')->first();
        $largestDebt = round($largestDebtContract['outstanding'] ?? 0, 2);
        $debtScore = $this->calculateDebtScore($contracts);

        return [
            'total_outstanding' => $totalOutstanding,
            'open_contracts' => $openContracts,
            'overdue_contracts' => $overdueContracts,
            'largest_debt' => $largestDebt,
            'credit' => $credit,
            'debt_score' => $debtScore,
        ];
    }

    protected function buildInsights(Collection $contracts): array
    {
        $mostCritical = $contracts->filter(fn($contract) => $contract['outstanding'] > 0)
            ->sortByDesc('outstanding')
            ->first();

        $oldestDebt = $contracts->filter(fn($contract) => $contract['outstanding'] > 0)
            ->sortByDesc('timeline_days')
            ->first();

        $creditContract = $contracts->filter(fn($contract) => $contract['credit'] > 0)
            ->sortByDesc('credit')
            ->first();

        return [
            'most_critical' => $mostCritical,
            'oldest_debt' => $oldestDebt,
            'credit_contract' => $creditContract,
        ];
    }

    protected function defineRiskLevel(string $status, float $outstanding): array
    {
        if ($status === 'credit') {
            return ['label' => 'Credit', 'class' => 'bg-label-info'];
        }

        if ($status === 'settled') {
            return ['label' => 'Settled', 'class' => 'bg-label-success'];
        }

        if ($status === 'overdue') {
            return ['label' => 'Overdue', 'class' => 'bg-label-danger'];
        }

        if ($outstanding >= 5000) {
            return ['label' => 'High risk', 'class' => 'bg-label-danger'];
        }

        if ($outstanding >= 1500) {
            return ['label' => 'Medium risk', 'class' => 'bg-label-warning'];
        }

        return ['label' => 'Low risk', 'class' => 'bg-label-primary'];
    }

    protected function calculateOutstandingDays($contract): ?int
    {
        if (! $contract->return_date instanceof Carbon) {
            return null;
        }

        if ($contract->return_date->isFuture()) {
            return null;
        }

        return $contract->return_date->diffInDays(now());
    }

    protected function calculateDebtScore(Collection $contracts): int
    {
        $total = max($contracts->count(), 1);
        $open = $contracts->where('status', 'open')->count();
        $overdue = $contracts->where('status', 'overdue')->count();

        $score = 100 - ($open / $total) * 35 - ($overdue / $total) * 40;

        return max(10, (int) round($score));
    }
}
