<?php

namespace App\Livewire\Pages\Panel\Expert\Reports;

use App\Livewire\Concerns\PaginatesReportRows;
use App\Services\Reports\OperationsReportService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class FleetPerformanceReport extends Component
{
    use PaginatesReportRows;
    use WithPagination;

    public string $search = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $ownership = 'all';
    public int $perPage = 12;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'ownership' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        if ($this->dateFrom === '') {
            $this->dateFrom = Carbon::now()->subDays(89)->toDateString();
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
        $this->ownership = 'all';
        $this->dateFrom = Carbon::now()->subDays(89)->toDateString();
        $this->dateTo = Carbon::now()->toDateString();

        $this->resetPage();
    }

    public function exportUrl(): string
    {
        return route('reports.fleet-performance.export', $this->exportQuery());
    }

    public function render()
    {
        $report = app(OperationsReportService::class)->fleetPerformance($this->filters());
        $rows = $this->paginateRows($report['rows'], $this->perPage);

        return view('livewire.pages.panel.expert.reports.fleet-performance-report', [
            'report' => $report,
            'rows' => $rows,
            'ownershipOptions' => $this->ownershipOptions(),
            'exportUrl' => $this->exportUrl(),
        ]);
    }

    protected function filters(): array
    {
        return [
            'search' => $this->search,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'ownership' => $this->ownership,
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
    protected function ownershipOptions(): array
    {
        return [
            ['value' => 'all', 'label' => 'All fleets'],
            ['value' => 'company', 'label' => 'Our fleet'],
            ['value' => 'golden_key', 'label' => 'Golden Key'],
            ['value' => 'liverpool', 'label' => 'Liverpool'],
            ['value' => 'safe_drive', 'label' => 'Safe Drive'],
            ['value' => 'other', 'label' => 'Other fleet'],
        ];
    }
}
