<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\HandlesContractCancellation;
use App\Livewire\Concerns\SearchesCustomerPhone;

class RentalRequestReserved extends Component
{
    use WithPagination;
    use HandlesContractCancellation;
    use SearchesCustomerPhone;

    public $search = '';
    public $searchInput = '';
    public $sortField = 'pickup_date';
    public $sortDirection = 'asc';
    public $statusFilter = 'reserved';
    public $userFilter = '';
    public $pickupFrom;
    public $pickupTo;
    public $returnFrom;
    public $returnTo;
    public $agentFilter = '';
    public $kardoFilter = '';
    public array $salesAgents = [];

    protected array $allowedSortFields = [
        'id',
        'pickup_date',
        'agent_sale',
        'created_at',
    ];

    protected array $statusScope = [
        'reserved',
        'delivery',
        'agreement_inspection',
        'awaiting_return',
        'returned',
        'payment',
        'complete',
        'cancelled',
    ];

    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'reserved'],
        'userFilter' => ['except' => ''],
        'pickupFrom' => ['except' => null],
        'pickupTo' => ['except' => null],
        'returnFrom' => ['except' => null],
        'returnTo' => ['except' => null],
        'agentFilter' => ['except' => ''],
        'kardoFilter' => ['except' => ''],
        'sortField' => ['except' => 'pickup_date'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function mount(): void
    {
        $this->searchInput = $this->search;
        $this->salesAgents = config('agents.sales_agents', []);
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'searchInput',
            'statusFilter',
            'userFilter',
            'pickupFrom',
            'pickupTo',
            'returnFrom',
            'returnTo',
            'agentFilter',
            'kardoFilter',
            'sortField',
            'sortDirection',
        ]);

        $this->resetPage();
    }

    public function loadContracts()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';
        $isPhoneSearch = $this->isCustomerPhoneSearch($search);

        $sortField = in_array($this->sortField, $this->allowedSortFields, true)
            ? $this->sortField
            : 'pickup_date';

        $sortDirection = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $statuses = $this->statusFilter === 'all'
            ? $this->statusScope
            : [$this->resolveStatus($this->statusFilter)];

        return Contract::query()
            ->with(['customer', 'car.carModel', 'user', 'latestStatus.user'])
            ->whereIn('current_status', $statuses)
            ->when($search !== '', function ($query) use ($likeSearch, $isPhoneSearch) {
                $query->where(function ($scoped) use ($likeSearch, $isPhoneSearch) {
                    $scoped->whereHas('customer', function ($q) use ($likeSearch, $isPhoneSearch) {
                        $q->where('first_name', 'like', $likeSearch)
                            ->orWhere('last_name', 'like', $likeSearch);

                        if ($isPhoneSearch) {
                            $q->orWhere('phone', 'like', $likeSearch);
                        }
                    })
                        ->orWhere('contracts.id', 'like', $likeSearch)
                        ->orWhereHas('car', function ($carQuery) use ($likeSearch) {
                            $carQuery->where('plate_number', 'like', $likeSearch)
                                ->orWhereHas('carModel', function ($modelQuery) use ($likeSearch) {
                                    $modelQuery->where('brand', 'like', $likeSearch)
                                        ->orWhere('model', 'like', $likeSearch);
                                });
                        });
                });
            })
            ->when($this->userFilter === 'assigned', fn($query) => $query->whereNotNull('user_id'))
            ->when($this->userFilter === 'unassigned', fn($query) => $query->whereNull('user_id'))
            ->when($this->pickupFrom, fn($query) => $query->where('pickup_date', '>=', $this->pickupFrom))
            ->when($this->pickupTo, fn($query) => $query->where('pickup_date', '<=', $this->pickupTo))
            ->when($this->returnFrom, fn($query) => $query->where('return_date', '>=', $this->returnFrom))
            ->when($this->returnTo, fn($query) => $query->where('return_date', '<=', $this->returnTo))
            ->when($this->agentFilter === 'none', fn($query) => $query->whereNull('agent_sale'))
            ->when($this->agentFilter && $this->agentFilter !== 'none', fn($query) => $query->where('agent_sale', $this->agentFilter))
            ->when($this->kardoFilter === 'required', fn($query) => $query->where('kardo_required', true))
            ->when($this->kardoFilter === 'not_required', fn($query) => $query->where('kardo_required', false))
            ->orderBy($sortField, $sortDirection)
            ->paginate(10);
    }

    public function sortBy($field)
    {
        if (! in_array($field, $this->allowedSortFields, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-reserved', [
            'reservedContracts' => $this->loadContracts(),
        ]);
    }

    private function resolveStatus(?string $status): string
    {
        if (! $status || ! in_array($status, $this->statusScope, true)) {
            return 'reserved';
        }

        return $status;
    }
}
