<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\HandlesContractCancellation;
use Illuminate\Support\Facades\Auth;

class RentalRequestAwaitingReturnList extends Component
{
    use WithPagination;
    use HandlesContractCancellation;

    public $search = '';
    public $searchInput = '';
    public $sortField = 'return_date';
    public $sortDirection = 'asc';
    public $statusFilter = 'awaiting_return';
    public $userFilter = '';
    public $pickupFrom;
    public $pickupTo;
    public $returnFrom;
    public $returnTo;
    public bool $isDriver = false;
    public ?int $driverId = null;

    protected array $allowedSortFields = [
        'id',
        'return_date',
        'agent_sale',
        'created_at',
    ];

    protected array $statusScope = [
        'awaiting_return',
        'returned',
        'complete',
        'cancelled',
    ];

    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'awaiting_return'],
        'userFilter' => ['except' => ''],
        'pickupFrom' => ['except' => null],
        'pickupTo' => ['except' => null],
        'returnFrom' => ['except' => null],
        'returnTo' => ['except' => null],
        'sortField' => ['except' => 'return_date'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function mount(): void
    {
        $this->searchInput = $this->search;

        $user = Auth::user();
        $this->isDriver = $user?->hasRole('driver') ?? false;
        $this->driverId = $this->isDriver ? $user?->id : null;
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

        $this->searchInput = $this->search;

        $this->resetPage();
    }

    public function loadContracts()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $sortField = in_array($this->sortField, $this->allowedSortFields, true)
            ? $this->sortField
            : 'return_date';

        $sortDirection = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $statuses = $this->statusFilter === 'all'
            ? $this->statusScope
            : [$this->resolveStatus($this->statusFilter)];

        return Contract::query()
            ->with(['customer', 'car.carModel', 'user', 'returnDriver', 'latestStatus.user'])
            ->whereIn('current_status', $statuses)
            ->when($search !== '', function ($query) use ($likeSearch) {
                $query->where(function ($scoped) use ($likeSearch) {
                    $scoped->whereHas('customer', function ($q) use ($likeSearch) {
                        $q->where('first_name', 'like', $likeSearch)
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
            ->whereHas('pickupDocument', fn($doc) => $doc->whereNotNull('tars_approved_at'))
            ->where(function ($query) {
                $query->where('kardo_required', false)
                    ->orWhereHas('pickupDocument', fn($doc) => $doc->whereNotNull('kardo_approved_at'));
            })
            ->when($this->pickupFrom, fn($query) => $query->where('pickup_date', '>=', $this->pickupFrom))
            ->when($this->pickupTo, fn($query) => $query->where('pickup_date', '<=', $this->pickupTo))
            ->when($this->returnFrom, fn($query) => $query->where('return_date', '>=', $this->returnFrom))
            ->when($this->returnTo, fn($query) => $query->where('return_date', '<=', $this->returnTo))
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
        return view(
            'livewire.pages.panel.expert.rental-request.rental-request-awaiting-return-list',
            [
                'awaitContracts' => $this->loadContracts(),
            ]
        );
    }

    private function resolveStatus(?string $status): string
    {
        if (! $status || ! in_array($status, $this->statusScope, true)) {
            return 'awaiting_return';
        }

        return $status;
    }

    public function assignReturnToDriver(int $contractId): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasRole('driver')) {
            $this->toast('error', 'Only drivers can claim return tasks.', false);
            return;
        }

        $contract = Contract::query()->whereKey($contractId)->first();

        if (! $contract) {
            $this->toast('error', 'The selected contract could not be found.', false);
            return;
        }

        if ($contract->return_driver_id && $contract->return_driver_id !== $user->id) {
            $this->toast('error', 'This return is already assigned to another driver.', false);
            return;
        }

        $contract->return_driver_id = $user->id;
        $contract->save();

        $this->driverId = $user->id;

        $this->toast('success', 'Return assigned to you successfully.');
        $this->dispatch('refreshContracts');
    }
}
