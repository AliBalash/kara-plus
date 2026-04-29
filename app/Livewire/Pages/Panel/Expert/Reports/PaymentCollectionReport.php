<?php

namespace App\Livewire\Pages\Panel\Expert\Reports;

use App\Livewire\Concerns\PaginatesReportRows;
use Carbon\Carbon;
use App\Services\Reports\OperationsReportService;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentCollectionReport extends Component
{
    use PaginatesReportRows;
    use WithPagination;

    public string $search = '';
    public string $dateField = 'payment_date';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $paymentType = 'all';
    public string $approvalStatus = 'all';
    public string $paymentState = 'all';
    public string $paymentMethod = 'all';
    public int $perPage = 12;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateField' => ['except' => 'payment_date'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'paymentType' => ['except' => 'all'],
        'approvalStatus' => ['except' => 'all'],
        'paymentState' => ['except' => 'all'],
        'paymentMethod' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        if ($this->dateFrom === '') {
            $this->dateFrom = Carbon::now()->startOfMonth()->toDateString();
        }

        if ($this->dateTo === '') {
            $this->dateTo = Carbon::now()->toDateString();
        }
    }

    public function updated($property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->dateField = 'payment_date';
        $this->dateFrom = Carbon::now()->startOfMonth()->toDateString();
        $this->dateTo = Carbon::now()->toDateString();
        $this->paymentType = 'all';
        $this->approvalStatus = 'all';
        $this->paymentState = 'all';
        $this->paymentMethod = 'all';

        $this->resetPage();
    }

    public function exportUrl(): string
    {
        return route('reports.payment-collections.export', $this->exportQuery());
    }

    public function render()
    {
        $report = app(OperationsReportService::class)->paymentCollections($this->filters());
        $rows = $this->paginateRows($report['rows'], $this->perPage);

        return view('livewire.pages.panel.expert.reports.payment-collection-report', [
            'report' => $report,
            'rows' => $rows,
            'paymentTypes' => $this->paymentTypes(),
            'approvalOptions' => $this->approvalOptions(),
            'paymentStates' => $this->paymentStates(),
            'paymentMethods' => $this->paymentMethods(),
            'exportUrl' => $this->exportUrl(),
        ]);
    }

    protected function filters(): array
    {
        return [
            'search' => $this->search,
            'date_field' => $this->dateField,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'payment_type' => $this->paymentType,
            'approval_status' => $this->approvalStatus,
            'payment_state' => $this->paymentState,
            'payment_method' => $this->paymentMethod,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function exportQuery(): array
    {
        return array_filter($this->filters(), fn ($value) => $value !== '' && $value !== null && $value !== 'all');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function paymentTypes(): array
    {
        return [
            ['value' => 'all', 'label' => 'All types'],
            ['value' => 'rental_fee', 'label' => 'Rental Fee'],
            ['value' => 'security_deposit', 'label' => 'Security Deposit'],
            ['value' => 'salik', 'label' => 'Salik'],
            ['value' => 'salik_4_aed', 'label' => 'Salik 4 AED'],
            ['value' => 'salik_6_aed', 'label' => 'Salik 6 AED'],
            ['value' => 'salik_other_revenue', 'label' => 'Salik Other Revenue'],
            ['value' => 'fine', 'label' => 'Fine'],
            ['value' => 'parking', 'label' => 'Parking'],
            ['value' => 'damage', 'label' => 'Damage'],
            ['value' => 'discount', 'label' => 'Discount'],
            ['value' => 'payment_back', 'label' => 'Payment Back'],
            ['value' => 'carwash', 'label' => 'Carwash'],
            ['value' => 'fuel', 'label' => 'Fuel'],
            ['value' => 'no_deposit_fee', 'label' => 'No Deposit Fee'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function approvalOptions(): array
    {
        return [
            ['value' => 'all', 'label' => 'All approvals'],
            ['value' => 'approved', 'label' => 'Approved'],
            ['value' => 'pending', 'label' => 'Pending'],
            ['value' => 'rejected', 'label' => 'Rejected'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function paymentStates(): array
    {
        return [
            ['value' => 'all', 'label' => 'Paid + unpaid'],
            ['value' => 'paid', 'label' => 'Paid only'],
            ['value' => 'unpaid', 'label' => 'Unpaid only'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function paymentMethods(): array
    {
        return [
            ['value' => 'all', 'label' => 'All methods'],
            ['value' => 'cash', 'label' => 'Cash'],
            ['value' => 'transfer', 'label' => 'Transfer'],
            ['value' => 'ticket', 'label' => 'Ticket'],
        ];
    }
}
