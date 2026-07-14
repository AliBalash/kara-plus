<?php

namespace App\Livewire\Pages\Panel\Expert\Reports;

use App\Livewire\Concerns\PaginatesReportRows;
use App\Models\Car;
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
    public string $status = 'all';
    public string $unavailabilityReason = 'all';
    public string $reservationDaysAhead = '';
    public int $perPage = 12;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'ownership' => ['except' => 'all'],
        'status' => ['except' => 'all'],
        'unavailabilityReason' => ['except' => 'all'],
        'reservationDaysAhead' => ['except' => ''],
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
        $this->status = 'all';
        $this->unavailabilityReason = 'all';
        $this->dateFrom = Carbon::now()->subDays(89)->toDateString();
        $this->dateTo = Carbon::now()->toDateString();
        $this->reservationDaysAhead = '';

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
            'statusOptions' => $this->statusOptions(),
            'unavailabilityReasonOptions' => $this->unavailabilityReasonOptions(),
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
            'status' => $this->status,
            'unavailability_reason' => $this->unavailabilityReason,
            'reservation_days_ahead' => $this->reservationDaysAhead,
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

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function statusOptions(): array
    {
        return collect(Car::operationalStatusLabels())
            ->prepend('All operational statuses', 'all')
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function unavailabilityReasonOptions(): array
    {
        return collect(Car::operationalUnavailabilityReasonLabels())
            ->prepend('All unavailable reasons', 'all')
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }
}
