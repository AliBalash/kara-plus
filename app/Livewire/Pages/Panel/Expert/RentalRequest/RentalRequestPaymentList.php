<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\HandlesContractCancellation;
use App\Livewire\Concerns\InteractsWithToasts;
use Illuminate\Support\Facades\Log;
use Throwable;

class RentalRequestPaymentList extends Component
{
    use WithPagination;
    use HandlesContractCancellation;
    use InteractsWithToasts;

    public $search = '';
    public $searchInput = '';
    public $sortField = 'pickup_date';
    public $sortDirection = 'desc';
    public $statusFilter = 'payment';
    public $userFilter = '';
    public $pickupFrom;
    public $pickupTo;
    public $returnFrom;
    public $returnTo;
    public $agentFilter = '';
    public $kardoFilter = '';
    public array $salesAgents = [];
    protected $paymentContracts;

    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    protected array $allowedSortFields = [
        'id',
        'pickup_date',
        'total_price',
        'created_at',
    ];

    protected array $statusScope = [
        'payment',
        'awaiting_return',
        'returned',
        'complete',
        'cancelled',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'payment'],
        'userFilter' => ['except' => ''],
        'pickupFrom' => ['except' => null],
        'pickupTo' => ['except' => null],
        'returnFrom' => ['except' => null],
        'returnTo' => ['except' => null],
        'agentFilter' => ['except' => ''],
        'kardoFilter' => ['except' => ''],
        'sortField' => ['except' => 'pickup_date'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
        $this->searchInput = $this->search;
        $this->salesAgents = config('agents.sales_agents', []);
    }

    public function changeStatusToComplete($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        $contract->changeStatus('complete', auth()->id());
        $this->toast('success', 'Status changed to complete successfully.');
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

    public function render()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $sortField = in_array($this->sortField, $this->allowedSortFields, true)
            ? $this->sortField
            : 'pickup_date';

        $sortDirection = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        $statuses = $this->statusFilter === 'all'
            ? $this->statusScope
            : [$this->resolveStatus($this->statusFilter)];

        $paymentContracts = Contract::query()
            ->with(['payments', 'customer', 'car', 'user', 'latestStatus.user'])
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
            ->when($this->agentFilter === 'none', fn($query) => $query->whereNull('agent_sale'))
            ->when($this->agentFilter && $this->agentFilter !== 'none', fn($query) => $query->where('agent_sale', $this->agentFilter))
            ->when($this->kardoFilter === 'required', fn($query) => $query->where('kardo_required', true))
            ->when($this->kardoFilter === 'not_required', fn($query) => $query->where('kardo_required', false))
            ->orderBy($sortField, $sortDirection)
            ->paginate(10);

        $this->paymentContracts = $paymentContracts;

        return view('livewire.pages.panel.expert.rental-request.rental-request-payment-list', [
            'paymentContracts' => $paymentContracts,
        ]);
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
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

    private function resolveStatus(?string $status): string
    {
        if (! $status || ! in_array($status, $this->statusScope, true)) {
            return 'payment';
        }

        return $status;
    }
}
