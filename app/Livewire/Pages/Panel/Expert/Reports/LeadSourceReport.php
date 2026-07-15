<?php

namespace App\Livewire\Pages\Panel\Expert\Reports;

use App\Livewire\Concerns\PaginatesReportRows;
use App\Models\Contract;
use App\Models\Lead;
use App\Services\Reports\OperationsReportService;
use Livewire\Component;
use Livewire\WithPagination;

class LeadSourceReport extends Component
{
    use PaginatesReportRows;
    use WithPagination;

    public string $search = '';
    public string $dateField = 'request_date';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $source = 'all';
    public string $status = 'all';
    public string $priority = 'all';
    public int $perPage = 12;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateField' => ['except' => 'request_date'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'source' => ['except' => 'all'],
        'status' => ['except' => 'all'],
        'priority' => ['except' => 'all'],
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
        $this->dateField = 'request_date';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->source = 'all';
        $this->status = 'all';
        $this->priority = 'all';

        $this->resetPage();
    }

    public function exportUrl(): string
    {
        return route('reports.lead-sources.export', $this->exportQuery());
    }

    public function render()
    {
        $report = app(OperationsReportService::class)->leadSources($this->filters());
        $rows = $this->paginateRows($report['rows'], $this->perPage);

        return view('livewire.pages.panel.expert.reports.lead-source-report', [
            'report' => $report,
            'rows' => $rows,
            'sourceOptions' => $this->sourceOptions(),
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
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
            'source' => $this->source,
            'status' => $this->status,
            'priority' => $this->priority,
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
    protected function sourceOptions(): array
    {
        $options = [['value' => 'all', 'label' => 'All channels']];

        foreach (Contract::COMMUNICATION_CHANNELS as $channel) {
            $options[] = [
                'value' => $channel,
                'label' => Contract::communicationChannelLabel($channel),
            ];
        }

        return $options;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function statusOptions(): array
    {
        $options = [['value' => 'all', 'label' => 'All statuses']];

        foreach (Lead::statuses() as $value => $label) {
            $options[] = compact('value', 'label');
        }

        return $options;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function priorityOptions(): array
    {
        $options = [['value' => 'all', 'label' => 'All priorities']];

        foreach (Lead::priorities() as $value => $label) {
            $options[] = compact('value', 'label');
        }

        return $options;
    }
}
