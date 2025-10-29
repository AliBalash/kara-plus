<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Livewire\Concerns\HandlesContractCancellation;
use App\Models\Contract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

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
    public $tarsStatus = 'all';
    public $kardoStatus = 'pending';
    public string $type = 'tars';

    protected array $statusScope = [
        'delivery',
        'inspection',
        'agreement_inspection',
    ];

    protected array $allowedTypes = ['tars', 'kardo'];

    protected array $allowedSortFields = [
        'id',
        'pickup_date',
        'return_date',
        'created_at',
    ];

    protected $listeners = ['refreshContracts' => '$refresh'];

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
        'tarsStatus' => ['except' => 'all'],
        'kardoStatus' => ['except' => 'pending'],
        'type' => ['except' => 'tars'],
    ];

    public function mount(string $type = 'tars'): void
    {
        $this->type = $this->normaliseType($type);
        $this->searchInput = $this->search;
        $this->applyDefaultApprovalFilters();
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
            'tarsStatus',
            'kardoStatus',
        ]);

        $this->applyDefaultApprovalFilters();
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    public function updatedType(string $value): void
    {
        $this->type = $this->normaliseType($value);
        $this->applyDefaultApprovalFilters();
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
            : $this->statusesForFilter($this->statusFilter);

        $contractsQuery = Contract::with(['customer', 'car.carModel', 'user', 'pickupDocument', 'latestStatus.user'])
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
            ->when($this->returnTo, fn($query) => $query->where('return_date', '<=', $this->returnTo));

        if ($this->type === 'tars') {
            $contractsQuery
                ->when($this->tarsStatus === 'pending', function ($query) {
                    $query->where(function ($inner) {
                        $inner->whereDoesntHave('pickupDocument')
                            ->orWhereHas('pickupDocument', fn($doc) => $doc->whereNull('tars_approved_at'));
                    });
                })
                ->when($this->tarsStatus === 'approved', function ($query) {
                    $query->whereHas('pickupDocument', fn($doc) => $doc->whereNotNull('tars_approved_at'));
                });
        } else {
            $contractsQuery
                ->where('kardo_required', true)
                ->when($this->kardoStatus === 'pending', function ($query) {
                    $query->where(function ($inner) {
                        $inner->whereDoesntHave('pickupDocument')
                            ->orWhereHas('pickupDocument', fn($doc) => $doc->whereNull('kardo_approved_at'));
                    });
                })
                ->when($this->kardoStatus === 'approved', function ($query) {
                    $query->whereHas('pickupDocument', fn($doc) => $doc->whereNotNull('kardo_approved_at'));
                });
        }

        $contracts = $contractsQuery
            ->orderBy($sortField, $sortDirection)
            ->paginate(10);

        return view('livewire.pages.panel.expert.rental-request.rental-request-inspection-list', [
            'contracts' => $contracts,
            'isTarsList' => $this->type === 'tars',
            'hasPendingKardoContracts' => $this->hasPendingKardoContracts(),
        ]);
    }

    public function moveToAwaitingReturn(int $contractId): void
    {
        $contract = Contract::with('pickupDocument')->find($contractId);

        if (! $contract) {
            session()->flash('error', 'Contract not found.');
            return;
        }

        $pickupDocument = $contract->pickupDocument;

        if (! $pickupDocument || ! $pickupDocument->tars_approved_at) {
            session()->flash('error', 'Please approve TARS first.');
            return;
        }

        if ($contract->kardo_required && (! $pickupDocument || ! $pickupDocument->kardo_approved_at)) {
            session()->flash('error', 'Please approve KARDO first.');
            return;
        }

        $userId = Auth::id();

        if (! $userId) {
            session()->flash('error', 'You need to be logged in to change the status.');
            return;
        }

        $currentStatus = $contract->current_status;

        if (! in_array($currentStatus, ['delivery', 'inspection', 'agreement_inspection', 'awaiting_return'], true)) {
            session()->flash('error', 'Contract must be in inspection before moving to awaiting return.');
            return;
        }

        if ($currentStatus === 'awaiting_return') {
            session()->flash('success', 'Contract is already awaiting return.');
            return;
        }

        try {
            DB::transaction(function () use ($contract, $userId, $currentStatus) {
                if (in_array($currentStatus, ['delivery', 'inspection'], true)) {
                    $contract->changeStatus('agreement_inspection', $userId);
                }

                $contract->changeStatus('awaiting_return', $userId);
            });
        } catch (Throwable $exception) {
            Log::error('Failed to advance contract to awaiting return.', [
                'contract_id' => $contractId,
                'message' => $exception->getMessage(),
            ]);

            session()->flash('error', 'Failed to move contract to awaiting return.');
            return;
        }

        session()->flash('success', 'Contract moved to awaiting return.');
        $this->dispatch('refreshContracts');
        $this->resetPage();
    }

    private function normaliseType(string $type): string
    {
        return in_array($type, $this->allowedTypes, true) ? $type : 'tars';
    }

    private function applyDefaultApprovalFilters(): void
    {
        if (! in_array($this->tarsStatus, ['pending', 'approved', 'all'], true)) {
            $this->tarsStatus = 'all';
        }

        if (! in_array($this->kardoStatus, ['pending', 'approved', 'all'], true)) {
            $this->kardoStatus = 'pending';
        }

        if ($this->type === 'tars') {
            $this->tarsStatus = 'all';
        }

        if ($this->type === 'kardo') {
            $this->kardoStatus = 'pending';
        }
    }

    private function hasPendingKardoContracts(): bool
    {
        return Contract::query()
            ->where('kardo_required', true)
            ->whereIn('current_status', $this->statusScope)
            ->where(function ($inner) {
                $inner->whereDoesntHave('pickupDocument')
                    ->orWhereHas('pickupDocument', fn($doc) => $doc->whereNull('kardo_approved_at'));
            })
            ->exists();
    }

    private function resolveStatus(?string $status): string
    {
        if (! $status || ! in_array($status, $this->statusScope, true)) {
            return 'delivery';
        }

        return $status;
    }

    /**
     * Normalise filter selections to the statuses that should be queried.
     */
    private function statusesForFilter(?string $status): array
    {
        $resolved = $this->resolveStatus($status);

        return match ($resolved) {
            'delivery' => ['delivery', 'inspection', 'agreement_inspection'],
            'inspection' => ['inspection', 'agreement_inspection'],
            'agreement_inspection' => ['agreement_inspection'],
            default => [$resolved],
        };
    }
}
