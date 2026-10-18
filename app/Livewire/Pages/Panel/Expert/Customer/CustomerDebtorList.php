<?php

namespace App\Livewire\Pages\Panel\Expert\Customer;

use App\Livewire\Concerns\SearchesCustomerPhone;
use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerDebtorList extends Component
{
    use SearchesCustomerPhone;
    use WithPagination;

    public $search = '';

    public string $searchInput = '';

    public $status = 'all';

    #[Locked]
    public $dateField = 'pickup_date';

    #[Locked]
    public $dateFrom = '';

    #[Locked]
    public $dateTo = '';

    #[Locked]
    public $minOutstanding = '';

    #[Locked]
    public $maxOutstanding = '';

    public array $filterInputs = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'all'],
        'dateField' => ['except' => 'pickup_date'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'minOutstanding' => ['except' => ''],
        'maxOutstanding' => ['except' => ''],
    ];

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->search = is_string($this->search) ? $this->search : '';
        $this->status = in_array($this->status, ['all', 'open', 'overdue', 'credit'], true) ? $this->status : 'all';
        $this->searchInput = $this->search;
        $this->filterInputs = $this->dateAndAmountFilters();

        $validator = Validator::make(['filterInputs' => $this->filterInputs], $this->filterRules());
        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            $this->reset('dateField', 'dateFrom', 'dateTo', 'minOutstanding', 'maxOutstanding');
        } else {
            foreach ($this->filterInputs as $field => $value) {
                $this->{$field} = $value ?? '';
            }
        }
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

    public function applyFilters(): void
    {
        $validated = $this->validate($this->filterRules(), [
            'filterInputs.*.regex' => 'Enter a non-negative amount with up to two decimal places.',
        ], [
            'filterInputs.dateField' => 'date basis',
            'filterInputs.dateFrom' => 'from date',
            'filterInputs.dateTo' => 'to date',
            'filterInputs.minOutstanding' => 'minimum outstanding',
            'filterInputs.maxOutstanding' => 'maximum outstanding',
        ]);

        foreach ($validated['filterInputs'] as $field => $value) {
            $this->{$field} = $value ?? '';
        }
        $this->applySearch();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'searchInput', 'status', 'dateField', 'dateFrom', 'dateTo', 'minOutstanding', 'maxOutstanding');
        $this->filterInputs = $this->dateAndAmountFilters();
        $this->resetValidation();
        $this->resetPage();
    }

    protected function dateAndAmountFilters(): array
    {
        return collect(['dateField', 'dateFrom', 'dateTo', 'minOutstanding', 'maxOutstanding'])
            ->mapWithKeys(fn ($field) => [$field => $this->{$field}])->all();
    }

    protected function filterRules(): array
    {
        $amountRules = ['bail', 'nullable', 'numeric', 'min:0', 'regex:/^\d+(?:\.\d{1,2})?$/D'];
        $rules = [
            'filterInputs.dateField' => ['required', 'in:pickup_date,created_at'],
            'filterInputs.dateFrom' => ['bail', 'nullable', 'string', 'date_format:Y-m-d'],
            'filterInputs.dateTo' => ['bail', 'nullable', 'string', 'date_format:Y-m-d'],
            'filterInputs.minOutstanding' => $amountRules,
            'filterInputs.maxOutstanding' => $amountRules,
        ];

        // Reject malformed URL arrays before passing a boundary to a comparison rule.
        if (is_string($this->filterInputs['dateFrom'] ?? null) && $this->filterInputs['dateFrom'] !== '') {
            $rules['filterInputs.dateTo'][] = 'after_or_equal:filterInputs.dateFrom';
        }
        if (is_scalar($this->filterInputs['minOutstanding'] ?? null) && $this->filterInputs['minOutstanding'] !== '') {
            $rules['filterInputs.maxOutstanding'][] = 'gte:filterInputs.minOutstanding';
        }

        return $rules;
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
        $likeSearch = '%'.$search.'%';
        $isPhoneSearch = $this->isCustomerPhoneSearch($search);

        $eligibleContracts = function ($query) {
            $query->includedInCustomerBalance()
                ->when($this->dateFrom !== '', fn ($query) => $query->where($this->dateField, '>=', $this->dateFrom.' 00:00:00'))
                ->when($this->dateTo !== '', fn ($query) => $query->where($this->dateField, '<=', $this->dateTo.' 23:59:59.999999'))
                ->with(['payments', 'car.carModel', 'incomingBalanceTransfers', 'outgoingBalanceTransfers']);
        };

        $customers = Customer::query()
            ->with(['contracts' => $eligibleContracts])
            ->when($this->dateFrom !== '' || $this->dateTo !== '', fn ($query) => $query->whereHas('contracts', $eligibleContracts))
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
            ->map(fn (Customer $customer) => $this->mapCustomerDebt($customer))
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
            ->filter(fn (array $row) => ($this->minOutstanding === '' || $row['total_outstanding'] >= (float) $this->minOutstanding)
                && ($this->maxOutstanding === '' || $row['total_outstanding'] <= (float) $this->maxOutstanding))
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
                'label' => 'Contract #'.$contract->id,
                'car' => optional($contract->car)->fullName() ?? '—',
                'pickup_date' => optional($contract->pickup_date)?->format('Y-m-d'),
                'status' => $status,
                'outstanding' => $outstanding,
                'credit' => $credit,
                'last_activity' => $lastTouchpoint instanceof Carbon ? $lastTouchpoint->format('Y-m-d') : null,
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
