<?php

namespace App\Livewire\Pages\Panel\Expert\Customer;

use App\Livewire\Concerns\SearchesCustomerPhone;
use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerDebtorList extends Component
{
    use WithPagination;
    use SearchesCustomerPhone;

    public string $search = '';

    public string $searchInput = '';

    public string $status = 'all';

    protected $queryString = ['search', 'status'];

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    public function render()
    {
        $summary = $this->buildDebtorSummary();
        $perPage = 10;
        $currentPage = $this->getPage();

        $paginated = new LengthAwarePaginator(
            $summary->forPage($currentPage, $perPage),
            $summary->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        return view('livewire.pages.panel.expert.customer.customer-debtor-list', [
            'debtors' => $paginated,
            'overview' => $this->buildOverview($summary),
        ]);
    }

    protected function buildDebtorSummary(): Collection
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';
        $isPhoneSearch = $this->isCustomerPhoneSearch($search);

        $customers = Customer::query()
            ->with(['contracts' => function ($query) {
                $query->with(['payments', 'car.carModel']);
            }])
            ->when($search !== '', function ($query) use ($likeSearch, $isPhoneSearch) {
                $query->where(function ($scoped) use ($likeSearch, $isPhoneSearch) {
                    $scoped->where('first_name', 'like', $likeSearch)
                        ->orWhere('last_name', 'like', $likeSearch)
                        ->orWhere('national_code', 'like', $likeSearch);

                    if ($isPhoneSearch) {
                        $scoped->orWhere('phone', 'like', $likeSearch);
                    }
                });
            })
            ->get();

        return $customers
            ->map(fn(Customer $customer) => $this->mapCustomerDebt($customer))
            ->filter(function (array $row) {
                if ($this->status === 'credit') {
                    return $row['credit'] > 0 || $row['total_outstanding'] > 0;
                }

                return $row['total_outstanding'] > 0;
            })
            ->filter(function (array $row) {
                if ($this->status === 'overdue') {
                    return $row['overdue_requests'] > 0;
                }

                if ($this->status === 'open') {
                    return $row['overdue_requests'] === 0 && $row['open_requests'] > 0;
                }

                if ($this->status === 'credit') {
                    return $row['credit'] > 0;
                }

                return true;
            })
            ->sortByDesc('total_outstanding')
            ->values();
    }

    protected function buildOverview(Collection $summary): array
    {
        $totalDebt = $summary->sum('total_outstanding');
        $overdue = $summary->sum('overdue_requests');
        $open = $summary->sum('open_requests');
        $credit = $summary->sum('credit');

        return [
            'total_debt' => round($totalDebt, 2),
            'overdue' => $overdue,
            'open' => $open,
            'credit' => round($credit, 2),
            'customers' => $summary->count(),
        ];
    }

    protected function mapCustomerDebt(Customer $customer): array
    {
        $contracts = $customer->contracts ?? collect();

        $contractsSummary = $contracts->map(function ($contract) {
            $payments = $contract->payments ?? collect();
            $balance = (float) $contract->calculateRemainingBalance($payments);
            $outstanding = max($balance, 0);
            $credit = $balance < 0 ? abs($balance) : 0.0;
            $hasReturned = $contract->return_date instanceof Carbon && $contract->return_date->isPast();

            $status = 'settled';

            if ($outstanding > 0) {
                $status = $hasReturned ? 'overdue' : 'open';
            } elseif ($credit > 0) {
                $status = 'credit';
            }

            $latestPayment = $payments->sortByDesc('payment_date')->first()?->payment_date;
            $lastTouchpoint = $latestPayment instanceof Carbon ? $latestPayment : $contract->updated_at;

            return [
                'id' => $contract->id,
                'label' => 'Contract #' . $contract->id,
                'car' => optional($contract->car)->fullName() ?? '—',
                'pickup_date' => optional($contract->pickup_date)?->format('d M Y'),
                'status' => $status,
                'outstanding' => $outstanding,
                'credit' => $credit,
                'last_activity' => $lastTouchpoint instanceof Carbon ? $lastTouchpoint->format('d M Y') : null,
                'risk' => $this->determineRiskBadge($status, $outstanding),
            ];
        });

        $totalOutstanding = round($contractsSummary->sum('outstanding'), 2);
        $openRequests = $contractsSummary->where('status', 'open')->count();
        $overdueRequests = $contractsSummary->where('status', 'overdue')->count();
        $credit = round($contractsSummary->sum('credit'), 2);

        $highlight = $contractsSummary
            ->sortByDesc('outstanding')
            ->first();

        return [
            'customer_id' => $customer->id,
            'name' => $customer->fullName(),
            'phone' => $customer->phone,
            'total_outstanding' => $totalOutstanding,
            'open_requests' => $openRequests,
            'overdue_requests' => $overdueRequests,
            'credit' => $credit,
            'status' => $this->statusLabel($openRequests, $overdueRequests, $credit),
            'primary_contract' => $highlight,
            'last_activity' => $highlight['last_activity'] ?? null,
        ];
    }

    protected function statusLabel(int $open, int $overdue, float $credit): array
    {
        if ($overdue > 0) {
            return ['label' => 'Overdue', 'class' => 'bg-label-danger'];
        }

        if ($open > 0) {
            return ['label' => 'Open', 'class' => 'bg-label-warning text-dark'];
        }

        if ($credit > 0) {
            return ['label' => 'Credit', 'class' => 'bg-label-info text-dark'];
        }

        return ['label' => 'Settled', 'class' => 'bg-label-success'];
    }

    protected function determineRiskBadge(string $status, float $outstanding): array
    {
        if ($status === 'overdue') {
            return ['label' => 'Overdue', 'class' => 'bg-label-danger'];
        }

        if ($outstanding >= 5000) {
            return ['label' => 'High risk', 'class' => 'bg-label-danger'];
        }

        if ($outstanding >= 1500) {
            return ['label' => 'Medium risk', 'class' => 'bg-label-warning text-dark'];
        }

        if ($status === 'open') {
            return ['label' => 'Low risk', 'class' => 'bg-label-primary'];
        }

        return ['label' => 'Credit', 'class' => 'bg-label-info text-dark'];
    }
}
