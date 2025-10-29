<?php

namespace App\Livewire\Pages\Panel\Expert\Payments;

use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class ProcessedPaymentList extends Component
{
    use WithPagination;

    public $search = '';
    public $searchInput = '';
    public $statusFilter = 'approved';
    public $currencyFilter = '';
    public $paymentTypeFilter = '';
    public $dateFrom;
    public $dateTo;
    public $openAccordions = [];

    protected $queryString = [
        'search',
        'statusFilter',
        'currencyFilter',
        'paymentTypeFilter',
        'dateFrom',
        'dateTo',
        'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        $this->searchInput = $this->search;

        if (! in_array($this->statusFilter, ['approved', 'rejected', 'all'], true)) {
            $this->statusFilter = 'approved';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'currencyFilter', 'paymentTypeFilter', 'dateFrom', 'dateTo']);
        $this->statusFilter = 'approved';
        $this->searchInput = '';
        $this->resetPage();
    }

    public function toggleAccordion($contractId): void
    {
        if (in_array($contractId, $this->openAccordions)) {
            $this->openAccordions = array_diff($this->openAccordions, [$contractId]);
        } else {
            $this->openAccordions[] = $contractId;
        }
    }

    protected function statusMeta(): array
    {
        return [
            'approved' => ['label' => 'Approved', 'bg' => 'success', 'text' => 'white'],
            'rejected' => ['label' => 'Rejected', 'bg' => 'danger', 'text' => 'white'],
        ];
    }

    public function render()
    {
        $statusMeta = $this->statusMeta();

        try {
            $search = trim($this->search);
            $isNumericSearch = is_numeric($search);

            $baseQuery = Payment::query()
                ->when($search !== '', function ($q) use ($search, $isNumericSearch) {
                    $likeSearch = '%' . $search . '%';

                    if ($isNumericSearch) {
                        $numeric = (int) $search;
                        $q->whereHas('contract', function ($q2) use ($numeric) {
                            $q2->where('id', $numeric);
                        });
                    } else {
                        $q->whereHas('customer', function ($q2) use ($likeSearch) {
                            $q2->where('last_name', 'like', $likeSearch);
                        });
                    }
                })
                ->when($this->currencyFilter, fn($q) => $q->where('currency', $this->currencyFilter))
                ->when($this->paymentTypeFilter, fn($q) => $q->where('payment_type', $this->paymentTypeFilter))
                ->when($this->dateFrom, fn($q) => $q->whereDate('payment_date', '>=', $this->dateFrom))
                ->when($this->dateTo, fn($q) => $q->whereDate('payment_date', '<=', $this->dateTo))
                ->whereIn('approval_status', ['approved', 'rejected']);

            if ($this->statusFilter !== 'all') {
                $baseQuery->where('approval_status', $this->statusFilter);
            }

            $summary = Payment::query()
                ->select('approval_status', DB::raw('COUNT(*) as total_payments'), DB::raw('COALESCE(SUM(amount), 0) as total_amount'), DB::raw('COALESCE(SUM(amount_in_aed), 0) as total_amount_aed'))
                ->whereIn('approval_status', ['approved', 'rejected'])
                ->groupBy('approval_status')
                ->get()
                ->keyBy('approval_status');

            $summaryData = collect(['approved', 'rejected'])->mapWithKeys(function ($status) use ($summary) {
                $item = $summary->get($status);

                return [
                    $status => [
                        'count' => $item?->total_payments ?? 0,
                        'total_amount' => $item?->total_amount ?? 0,
                        'total_amount_aed' => $item?->total_amount_aed ?? 0,
                    ],
                ];
            })->toArray();

            $contractsPaginator = (clone $baseQuery)
                ->select('contract_id')
                ->groupBy('contract_id')
                ->orderByDesc(DB::raw('MAX(id)'))
                ->paginate(10, ['contract_id']);

            $payments = (clone $baseQuery)
                ->with(['customer', 'contract', 'car'])
                ->whereIn('contract_id', $contractsPaginator->pluck('contract_id'))
                ->orderByDesc('id')
                ->get()
                ->groupBy('contract_id');

            $groupedPayments = $contractsPaginator->getCollection()->mapWithKeys(
                fn ($contract) => [
                    $contract->contract_id => $payments->get($contract->contract_id, collect()),
                ]
            );

            $groupedPayments = new LengthAwarePaginator(
                $groupedPayments,
                $contractsPaginator->total(),
                $contractsPaginator->perPage(),
                $contractsPaginator->currentPage(),
                [
                    'path' => $contractsPaginator->path(),
                    'pageName' => $contractsPaginator->getPageName(),
                ]
            );
        } catch (\Throwable $exception) {
            Log::error('Failed to load processed payments', [
                'message' => $exception->getMessage(),
            ]);

            $groupedPayments = new LengthAwarePaginator(
                items: collect(),
                total: 0,
                perPage: 10,
                currentPage: 1,
                options: [
                    'path' => request()->url(),
                    'pageName' => 'page',
                ]
            );

            $summaryData = [
                'approved' => ['count' => 0, 'total_amount' => 0, 'total_amount_aed' => 0],
                'rejected' => ['count' => 0, 'total_amount' => 0, 'total_amount_aed' => 0],
            ];
        }

        return view('livewire.pages.panel.expert.payments.processed-payment-list', [
            'groupedPayments' => $groupedPayments,
            'summaryData' => $summaryData,
            'statusMeta' => $statusMeta,
        ]);
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }
}
