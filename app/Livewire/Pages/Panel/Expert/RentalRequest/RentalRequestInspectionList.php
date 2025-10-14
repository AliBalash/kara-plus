<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\HandlesContractCancellation;

class RentalRequestInspectionList extends Component
{
    use WithPagination;
    use HandlesContractCancellation;

    public $search = '';
    public $statusFilter = 'delivery';
    public $userFilter = '';
    public $pickupFrom;
    public $pickupTo;
    public $returnFrom;
    public $returnTo;
    public $sortField = 'pickup_date';
    public $sortDirection = 'asc';
    public $searchInput = '';

    protected array $statusScope = [
        'delivery',
        'agreement_inspection',
        'awaiting_return',
        'returned',
        'complete',
        'cancelled',
    ];

    protected array $allowedSortFields = [
        'id',
        'pickup_date',
        'return_date',
        'created_at',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'delivery'],
        'userFilter' => ['except' => ''],
        'pickupFrom' => ['except' => null],
        'pickupTo' => ['except' => null],
        'returnFrom' => ['except' => null],
        'returnTo' => ['except' => null],
        'sortField' => ['except' => 'pickup_date'],
        'sortDirection' => ['except' => 'asc'],
    ];
    protected $listeners = ['refreshContracts' => '$refresh'];

    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    public function sortBy(string $field): void
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
            'sortField',
            'sortDirection',
        ]);

        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $sortField = in_array($this->sortField, $this->allowedSortFields, true)
            ? $this->sortField
            : 'pickup_date';

        $sortDirection = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $statuses = $this->statusFilter === 'all'
            ? $this->statusScope
            : [$this->resolveStatus($this->statusFilter)];

        $inspectionContracts = Contract::with(['customer', 'car.carModel', 'user', 'pickupDocument'])
            ->whereIn('current_status', $statuses)
            ->when($search !== '', function ($query) use ($likeSearch) {
                $query->where(function ($q) use ($likeSearch) {
                    $q->whereHas('customer', function ($customerQuery) use ($likeSearch) {
                        $customerQuery->where('first_name', 'like', $likeSearch)
                            ->orWhere('last_name', 'like', $likeSearch);
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
            ->orderBy($sortField, $sortDirection)
            ->paginate(10);

        return view('livewire.pages.panel.expert.rental-request.rental-request-inspection-list', [
            'inspectionContracts' => $inspectionContracts
        ]);
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    private function resolveStatus(?string $status): string
    {
        if (! $status || ! in_array($status, $this->statusScope, true)) {
            return 'delivery';
        }

        return $status;
    }
}
