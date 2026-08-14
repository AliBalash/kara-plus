<?php

namespace App\Livewire\Pages\Panel\Expert\Reports;

use App\Livewire\Concerns\PaginatesReportRows;
use App\Models\Contract;
use App\Services\Reports\OperationsReportService;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerCommunicationChannelReport extends Component
{
    use PaginatesReportRows;
    use WithPagination;

    public string $search = '';

    public string $dateField = 'created_at';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $channel = 'all';

    public string $status = 'all';

    public int $perPage = 12;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateField' => ['except' => 'created_at'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'channel' => ['except' => 'all'],
        'status' => ['except' => 'all'],
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
        $this->dateField = 'created_at';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->channel = 'all';
        $this->status = 'all';
        $this->resetPage();
    }

    public function exportUrl(): string
    {
        return route('marketing.customer-communication-channels.export', $this->exportQuery());
    }

    public function render()
    {
        $report = app(OperationsReportService::class)->customerCommunicationChannels($this->filters());

        return view('livewire.pages.panel.expert.reports.customer-communication-channel-report', [
            'report' => $report,
            'rows' => $this->paginateRows($report['rows'], $this->perPage),
            'channelOptions' => $this->channelOptions(),
            'statusOptions' => $this->statusOptions(),
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
            'channel' => $this->channel,
            'status' => $this->status,
        ];
    }

    protected function exportQuery(): array
    {
        return array_filter($this->filters(), fn ($value) => $value !== '' && $value !== null && $value !== 'all');
    }

    protected function channelOptions(): array
    {
        return collect(Contract::COMMUNICATION_CHANNELS)
            ->mapWithKeys(fn (string $channel) => [$channel => Contract::communicationChannelLabel($channel)])
            ->all();
    }

    protected function statusOptions(): array
    {
        return [
            'all' => 'All statuses',
            'pending' => 'Pending',
            'assigned' => 'Assigned',
            'under_review' => 'Under Review',
            'reserved' => 'Booking',
            'delivery' => 'Delivery',
            'awaiting_return' => 'Awaiting Return',
            'returned' => 'Returned',
            'complete' => 'Complete',
            'cancelled' => 'Cancelled',
        ];
    }
}
