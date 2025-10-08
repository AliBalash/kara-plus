<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Livewire\Concerns\HandlesContractCancellation;
use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;

class RentalRequestMe extends Component
{
    use HandlesContractCancellation;
    use WithPagination;

    public $search = '';
    public $searchInput = '';
    public $statusFilter = '';
    public $pickupFrom;
    public $pickupTo;
    public $returnFrom;
    public $returnTo;
    public $sortField = 'pickup_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshContracts' => '$refresh'];

    protected array $allowedSortFields = [
        'id',
        'pickup_date',
        'return_date',
        'current_status',
        'created_at',
    ];

    protected $queryString = [
        'search',
        'statusFilter',
        'pickupFrom',
        'pickupTo',
        'returnFrom',
        'returnTo',
        'sortField',
        'sortDirection',
    ];

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
            'pickupFrom',
            'pickupTo',
            'returnFrom',
            'returnTo',
        ]);

        $this->sortField = 'pickup_date';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPickupFrom(): void
    {
        $this->resetPage();
    }

    public function updatedPickupTo(): void
    {
        $this->resetPage();
    }

    public function updatedReturnFrom(): void
    {
        $this->resetPage();
    }

    public function updatedReturnTo(): void
    {
        $this->resetPage();
    }

    protected function afterContractCancelled(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $query = Contract::query()
            ->where('user_id', auth()->id())
            ->with(['customer', 'car.carModel', 'user']);

        if ($search !== '') {
            $query->where(function ($scoped) use ($likeSearch) {
                $scoped->where('contracts.id', 'like', $likeSearch)
                    ->orWhereHas('customer', function ($customerQuery) use ($likeSearch) {
                        $customerQuery->where('first_name', 'like', $likeSearch)
                            ->orWhere('last_name', 'like', $likeSearch);
                    })
                    ->orWhereHas('car', function ($carQuery) use ($likeSearch) {
                        $carQuery->where('plate_number', 'like', $likeSearch)
                            ->orWhereHas('carModel', function ($modelQuery) use ($likeSearch) {
                                $modelQuery->where('brand', 'like', $likeSearch)
                                    ->orWhere('model', 'like', $likeSearch);
                            });
                    });
            });
        }

        $query
            ->when($this->statusFilter, fn($builder) => $builder->where('current_status', $this->statusFilter))
            ->when($this->pickupFrom, fn($builder) => $builder->whereDate('pickup_date', '>=', $this->pickupFrom))
            ->when($this->pickupTo, fn($builder) => $builder->whereDate('pickup_date', '<=', $this->pickupTo))
            ->when($this->returnFrom, fn($builder) => $builder->whereDate('return_date', '>=', $this->returnFrom))
            ->when($this->returnTo, fn($builder) => $builder->whereDate('return_date', '<=', $this->returnTo));

        $sortField = in_array($this->sortField, $this->allowedSortFields, true)
            ? $this->sortField
            : 'pickup_date';

        $contracts = $query
            ->orderByRaw("FIELD(current_status, 'pending') DESC")
            ->orderBy($sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.pages.panel.expert.rental-request.rental-request-me', [
            'contracts' => $contracts,
        ]);
    }
}

