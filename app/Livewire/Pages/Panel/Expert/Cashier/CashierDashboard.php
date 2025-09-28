<?php

namespace App\Livewire\Pages\Panel\Expert\Cashier;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class CashierDashboard extends Component
{
    use WithPagination;

    public $search = '';
    public $currencyFilter = '';
    public $dateFrom;
    public $dateTo;
    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';
    protected $queryString = ['search', 'currencyFilter', 'dateFrom', 'dateTo'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCurrencyFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage($value): void
    {
        $allowed = [10, 20, 50];
        if (!in_array((int) $value, $allowed, true)) {
            $this->perPage = 10;
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'currencyFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        try {
            $baseQuery = Payment::query()
                ->with(['customer', 'contract.car'])
                ->where('is_paid', 1)
                ->where(function ($query) {
                    $query->whereNull('payment_method')
                        ->orWhereRaw('LOWER(TRIM(payment_method)) = ?', ['cash'])
                        ->orWhereRaw('LOWER(payment_method) LIKE ?', ['cash%'])
                        ->orWhereRaw('LOWER(payment_method) LIKE ?', ['%cash%']);
                });

            $totalCashAed = (clone $baseQuery)->sum('amount_in_aed');
            $todayCashAed = (clone $baseQuery)
                ->whereDate('payment_date', Carbon::today())
                ->sum('amount_in_aed');
            $weeklyCashAed = (clone $baseQuery)
                ->whereBetween('payment_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->sum('amount_in_aed');
            $totalReceipts = (clone $baseQuery)->count();

            $currencyBreakdown = (clone $baseQuery)
                ->selectRaw('currency, COUNT(*) as receipts_count, SUM(amount) as total_amount, SUM(amount_in_aed) as total_aed')
                ->groupBy('currency')
                ->orderByDesc('total_aed')
                ->get();

            $filteredQuery = (clone $baseQuery)
                ->when($this->search, function ($query) {
                    $search = trim($this->search);
                    $numericSearch = is_numeric($search) ? (int) $search : null;

                    $query->where(function ($inner) use ($search, $numericSearch) {
                        if (!is_null($numericSearch)) {
                            $inner->orWhere('id', $numericSearch)
                                ->orWhere('contract_id', $numericSearch)
                                ->orWhere('amount', 'like', "%{$numericSearch}%")
                                ->orWhere('amount_in_aed', 'like', "%{$numericSearch}%");
                        }

                        $inner->orWhereHas('contract', function ($contractQuery) use ($search, $numericSearch) {
                            if (!is_null($numericSearch)) {
                                $contractQuery->where('id', $numericSearch);
                            } else {
                                $contractQuery->where('id', 'like', "%{$search}%");
                            }
                        })
                            ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                $customerQuery->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                            })
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when($this->currencyFilter, fn($query) => $query->where('currency', $this->currencyFilter))
                ->when($this->dateFrom, fn($query) => $query->whereDate('payment_date', '>=', $this->dateFrom))
                ->when($this->dateTo, fn($query) => $query->whereDate('payment_date', '<=', $this->dateTo));

            $filteredTotalAed = (clone $filteredQuery)->sum('amount_in_aed');

            $perPage = (int) $this->perPage ?: 10;

            $payments = $filteredQuery
                ->orderByDesc('payment_date')
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString();
        } catch (\Throwable $exception) {
            Log::error('Cashier dashboard failed to load', [
                'message' => $exception->getMessage(),
            ]);

            $totalCashAed = 0;
            $todayCashAed = 0;
            $weeklyCashAed = 0;
            $totalReceipts = 0;
            $filteredTotalAed = 0;
            $currencyBreakdown = Collection::make();

            $payments = new LengthAwarePaginator(
                items: Collection::make(),
                total: 0,
                perPage: max(1, (int) $this->perPage),
                currentPage: 1,
                options: [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );
        }

        return view('livewire.pages.panel.expert.cashier.cashier-dashboard', [
            'payments' => $payments,
            'totalCashAed' => $totalCashAed,
            'todayCashAed' => $todayCashAed,
            'weeklyCashAed' => $weeklyCashAed,
            'totalReceipts' => $totalReceipts,
            'currencyBreakdown' => $currencyBreakdown,
            'filteredTotalAed' => $filteredTotalAed,
        ]);
    }
}
