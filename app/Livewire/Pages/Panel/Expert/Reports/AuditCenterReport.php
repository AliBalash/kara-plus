<?php

namespace App\Livewire\Pages\Panel\Expert\Reports;

use App\Models\AuditEvent;
use App\Models\User;
use App\Services\Audit\AuditReportService;
use Livewire\Component;
use Livewire\WithPagination;

class AuditCenterReport extends Component
{
    use WithPagination;

    public string $search = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $actorUserId = '';
    public string $actionGroup = '';
    public string $action = '';
    public string $entityType = '';
    public string $routeName = '';
    public string $statusCode = '';
    public string $requestId = '';
    public string $contractId = '';
    public string $customerId = '';
    public string $paymentId = '';

    public int $perPage = 25;
    public ?int $selectedEventId = null;

    public array $actionOptions = [];
    public array $actorOptions = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'actorUserId' => ['except' => ''],
        'actionGroup' => ['except' => ''],
        'action' => ['except' => ''],
        'entityType' => ['except' => ''],
        'routeName' => ['except' => ''],
        'statusCode' => ['except' => ''],
        'requestId' => ['except' => ''],
        'contractId' => ['except' => ''],
        'customerId' => ['except' => ''],
        'paymentId' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $this->actionOptions = AuditEvent::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->values()
            ->all();

        $actorIds = AuditEvent::query()
            ->whereNotNull('actor_user_id')
            ->distinct()
            ->orderByDesc('actor_user_id')
            ->limit(200)
            ->pluck('actor_user_id')
            ->all();

        $users = User::query()
            ->whereIn('id', $actorIds)
            ->get(['id', 'first_name', 'last_name'])
            ->sortBy(function (User $user) {
                return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            })
            ->values();

        $this->actorOptions = $users->map(function (User $user) {
            $label = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            return [
                'id' => (string) $user->id,
                'label' => $label !== '' ? $label : ('User #' . $user->id),
            ];
        })->all();
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
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->actorUserId = '';
        $this->actionGroup = '';
        $this->action = '';
        $this->entityType = '';
        $this->routeName = '';
        $this->statusCode = '';
        $this->requestId = '';
        $this->contractId = '';
        $this->customerId = '';
        $this->paymentId = '';
        $this->selectedEventId = null;

        $this->resetPage();
    }

    public function selectEvent(int $eventId): void
    {
        $this->selectedEventId = $eventId;
    }

    public function exportUrl(): string
    {
        return route('reports.audit-center.export', array_filter($this->filters(), fn ($value) => $value !== ''));
    }

    public function render(AuditReportService $reportService)
    {
        $filters = $this->filters();
        $summary = $reportService->summary($filters);
        $dashboard = $reportService->dashboard($filters);

        $events = $reportService->query($filters)
            ->orderByDesc('occurred_at')
            ->paginate($this->perPage);

        $selectedEvent = null;
        $correlationEvents = collect();

        if ($this->selectedEventId) {
            $selectedEvent = AuditEvent::with('actor')->find($this->selectedEventId);

            if ($selectedEvent?->request_id) {
                $correlationEvents = AuditEvent::with('actor')
                    ->where('request_id', $selectedEvent->request_id)
                    ->orderBy('occurred_at')
                    ->get();
            }
        }

        return view('livewire.pages.panel.expert.reports.audit-center-report', [
            'summary' => $summary,
            'dashboard' => $dashboard,
            'events' => $events,
            'selectedEvent' => $selectedEvent,
            'correlationEvents' => $correlationEvents,
            'exportUrl' => $this->exportUrl(),
        ]);
    }

    private function filters(): array
    {
        return [
            'search' => trim($this->search),
            'date_from' => trim($this->dateFrom),
            'date_to' => trim($this->dateTo),
            'actor_user_id' => trim($this->actorUserId),
            'action_group' => trim($this->actionGroup),
            'action' => trim($this->action),
            'entity_type' => trim($this->entityType),
            'route_name' => trim($this->routeName),
            'status_code' => trim($this->statusCode),
            'request_id' => trim($this->requestId),
            'contract_id' => trim($this->contractId),
            'customer_id' => trim($this->customerId),
            'payment_id' => trim($this->paymentId),
        ];
    }
}
