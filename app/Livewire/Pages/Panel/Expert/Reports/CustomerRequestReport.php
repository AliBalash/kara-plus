<?php

namespace App\Livewire\Pages\Panel\Expert\Reports;

use App\Livewire\Concerns\PaginatesReportRows;
use App\Models\Agent;
use App\Services\Reports\OperationsReportService;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerRequestReport extends Component
{
    use PaginatesReportRows;
    use WithPagination;

    public string $search = '';
    public string $dateField = 'created_at';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $status = 'all';
    public string $agentId = '';
    public string $kardo = 'all';
    public int $perPage = 12;

    /** @var array<int, array{id: string, name: string}> */
    public array $agents = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'dateField' => ['except' => 'created_at'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'status' => ['except' => 'all'],
        'agentId' => ['except' => ''],
        'kardo' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        $this->agents = Agent::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($agent) => ['id' => (string) $agent->id, 'name' => $agent->name])
            ->all();
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
        $this->dateField = 'created_at';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->status = 'all';
        $this->agentId = '';
        $this->kardo = 'all';

        $this->resetPage();
    }

    public function exportUrl(): string
    {
        return route('reports.customer-requests.export', $this->exportQuery());
    }

    public function render()
    {
        $report = app(OperationsReportService::class)->customerRequests($this->filters());
        $rows = $this->paginateRows($report['rows'], $this->perPage);

        return view('livewire.pages.panel.expert.reports.customer-request-report', [
            'report' => $report,
            'rows' => $rows,
            'statusOptions' => $this->statusOptions(),
            'kardoOptions' => $this->kardoOptions(),
            'agents' => $this->agents,
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
            'status' => $this->status,
            'agent_id' => $this->agentId,
            'kardo' => $this->kardo,
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
    protected function statusOptions(): array
    {
        return [
            ['value' => 'all', 'label' => 'All statuses'],
            ['value' => 'pending', 'label' => 'Pending'],
            ['value' => 'assigned', 'label' => 'Assigned'],
            ['value' => 'under_review', 'label' => 'Under Review'],
            ['value' => 'reserved', 'label' => 'Booking'],
            ['value' => 'delivery', 'label' => 'Delivery'],
            ['value' => 'agreement_inspection', 'label' => 'Agreement Inspection'],
            ['value' => 'awaiting_return', 'label' => 'Awaiting Return'],
            ['value' => 'payment', 'label' => 'Payment'],
            ['value' => 'returned', 'label' => 'Returned'],
            ['value' => 'complete', 'label' => 'Complete'],
            ['value' => 'cancelled', 'label' => 'Cancelled'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function kardoOptions(): array
    {
        return [
            ['value' => 'all', 'label' => 'All contracts'],
            ['value' => 'required', 'label' => 'KARDO required'],
            ['value' => 'not_required', 'label' => 'KARDO not required'],
        ];
    }
}
