<?php

namespace App\Livewire\Pages\Panel\Expert\Reports;

use App\Livewire\Concerns\PaginatesReportRows;
use App\Services\Reports\OperationsReportService;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerBalanceReport extends Component
{
    use PaginatesReportRows;
    use WithPagination;

    public string $search = '';
    public string $dateField = 'pickup_date';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $balanceStatus = 'all';
    public int $perPage = 12;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateField' => ['except' => 'pickup_date'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'balanceStatus' => ['except' => 'all'],
    ];

    public function updated($property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->dateField = 'pickup_date';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->balanceStatus = 'all';

        $this->resetPage();
    }

    public function exportUrl(): string
    {
        return route('reports.customer-balances.export', $this->exportQuery());
    }

    public function render()
    {
        $report = app(OperationsReportService::class)->customerBalances($this->filters());
        $rows = $this->paginateRows($report['rows'], $this->perPage);

        return view('livewire.pages.panel.expert.reports.customer-balance-report', [
            'report' => $report,
            'rows' => $rows,
            'balanceOptions' => $this->balanceOptions(),
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
            'balance_status' => $this->balanceStatus,
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
    protected function balanceOptions(): array
    {
        return [
            ['value' => 'all', 'label' => 'All balances'],
            ['value' => 'open', 'label' => 'Open balance'],
            ['value' => 'overdue', 'label' => 'Overdue'],
            ['value' => 'settled', 'label' => 'Settled'],
            ['value' => 'credit', 'label' => 'Credit'],
        ];
    }
}
