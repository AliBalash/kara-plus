<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\HandlesContractCancellation;
use App\Livewire\Concerns\InteractsWithToasts;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class RentalRequestAwaitingPickupList extends Component
{
    use WithPagination;
    use HandlesContractCancellation;
    use InteractsWithToasts;

    public $search = '';
    public $searchInput = '';
    public $perPage = 10;
    public $sortField = 'pickup_date';
    public $sortDirection = 'asc';
    public $statusFilter = 'reserved';
    public $userFilter = '';
    public $pickupFrom;
    public $pickupTo;
    public $returnFrom;
    public $returnTo;
    public bool $isDriver = false;
    public ?int $driverId = null;

    protected array $allowedSortFields = [
        'id',
        'pickup_date',
        'return_date',
        'agent_sale',
        'created_at',
    ];

    protected array $statusScope = [
        'reserved',
        'delivery',
        'agreement_inspection',
        'awaiting_return',
        'returned',
        'complete',
        'cancelled',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortField' => ['except' => 'pickup_date'],
        'sortDirection' => ['except' => 'asc'],
        'statusFilter' => ['except' => 'reserved'],
        'userFilter' => ['except' => ''],
        'pickupFrom' => ['except' => null],
        'pickupTo' => ['except' => null],
        'returnFrom' => ['except' => null],
        'returnTo' => ['except' => null],
    ];

    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    public function mount(): void
    {
        $this->searchInput = $this->search;

        $user = Auth::user();
        $this->isDriver = $user?->hasRole('driver') ?? false;
        $this->driverId = $this->isDriver ? $user?->id : null;
    }

    public function loadContracts()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $statuses = $this->statusFilter === 'all'
            ? $this->statusScope
            : [$this->resolveStatus($this->statusFilter)];

        $query = Contract::query()
            ->with(['customer', 'car.carModel', 'user', 'pickupDocument', 'deliveryDriver', 'latestStatus.user'])
            ->whereIn('current_status', $statuses)
            ->when($search !== '', function ($q) use ($likeSearch) {
                $q->where(function ($scoped) use ($likeSearch) {
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
            })
            ->when($this->userFilter === 'assigned', fn($q) => $q->whereNotNull('user_id'))
            ->when($this->userFilter === 'unassigned', fn($q) => $q->whereNull('user_id'))
            ->when($this->pickupFrom, fn($q) => $q->where('pickup_date', '>=', $this->pickupFrom))
            ->when($this->pickupTo, fn($q) => $q->where('pickup_date', '<=', $this->pickupTo))
            ->when($this->returnFrom, fn($q) => $q->where('return_date', '>=', $this->returnFrom))
            ->when($this->returnTo, fn($q) => $q->where('return_date', '<=', $this->returnTo));

        $sortField = in_array($this->sortField, $this->allowedSortFields, true)
            ? $this->sortField
            : 'pickup_date';

        $sortDirection = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sortField, $sortDirection)
            ->paginate($this->perPage);
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'searchInput',
            'sortField',
            'sortDirection',
            'statusFilter',
            'userFilter',
            'pickupFrom',
            'pickupTo',
            'returnFrom',
            'returnTo',
        ]);

        $this->searchInput = $this->search;

        $this->resetPage();
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

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-awaiting-pickup-list', [
            'contracts' => $this->loadContracts(),
        ]);
    }

    private function resolveStatus(?string $status): string
    {
        if (! $status || ! in_array($status, $this->statusScope, true)) {
            return 'reserved';
        }

        return $status;
    }

    public function assignToDriver(int $contractId): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasRole('driver')) {
            $this->toast('error', 'Only drivers can claim delivery tasks.', false);
            return;
        }

        $contract = Contract::query()->whereKey($contractId)->first();

        if (! $contract) {
            $this->toast('error', 'The selected contract could not be found.', false);
            return;
        }

        if ($contract->delivery_driver_id && $contract->delivery_driver_id !== $user->id) {
            $this->toast('error', 'This delivery is already assigned to another driver.', false);
            return;
        }

        $contract->delivery_driver_id = $user->id;
        $contract->save();

        $this->driverId = $user->id;

        $this->toast('success', 'Delivery assigned to you successfully.');
        $this->dispatch('refreshContracts');
    }


    public function deleteContract(int $contractId): void
    {
        try {
            $contract = Contract::findOrFail($contractId);
            $contract->delete();
            $this->toast('success', 'Contract deleted successfully.');
            $this->resetPage();
            $this->dispatch('refreshContracts');
        } catch (Throwable $exception) {
            Log::error('Failed to delete contract', ['contract_id' => $contractId, 'message' => $exception->getMessage()]);
            $this->toast('error', 'Failed to delete contract. Please try again.');
        }
    }
}
