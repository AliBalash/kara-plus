<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Contract;
use App\Livewire\Concerns\HandlesContractCancellation;
use App\Livewire\Concerns\InteractsWithToasts;
use Illuminate\Support\Facades\Log;
use Throwable;

class RentalRequestList extends Component
{
    use WithPagination;
    use HandlesContractCancellation;
    use InteractsWithToasts;

    public $search = '';
    public $statusFilter = '';
    public $userFilter = '';
    public $pickupFrom;
    public $pickupTo;
    public $returnFrom;
    public $returnTo;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $searchInput = '';
    public $agentFilter = '';
    public $kardoFilter = '';
    public array $salesAgents = [];

    protected $listeners = ['refreshContracts' => '$refresh'];
    protected $queryString = [
        'search',
        'statusFilter',
        'userFilter',
        'pickupFrom',
        'pickupTo',
        'returnFrom',
        'returnTo',
        'agentFilter',
        'kardoFilter',
        'sortField',
        'sortDirection'
    ];

    public function mount(): void
    {
        $this->searchInput = $this->search;
        $this->salesAgents = config('agents.sales_agents', []);
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters()
    {
        $this->reset([
            'search',
            'statusFilter',
            'userFilter',
            'pickupFrom',
            'pickupTo',
            'returnFrom',
            'returnTo',
            'agentFilter',
            'kardoFilter',
        ]);
        $this->searchInput = '';
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    public function assignToMe($contractId)
    {
        $contract = Contract::findOrFail($contractId);
        if (is_null($contract->user_id)) {
            $contract->update(['user_id' => auth()->id()]);
            $contract->changeStatus('assigned', auth()->id());
            $this->toast('success', 'Contract assigned to you successfully.');
            $this->dispatch('refreshContracts');
        } else {
            $this->toast('error', 'This contract is already assigned.', false);
        }
    }

    public function render()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $contracts = Contract::with(['customer', 'car.carModel', 'user', 'latestStatus.user'])
            ->when($search !== '', function ($query) use ($search, $likeSearch) {
                $query->where(function ($scopedQuery) use ($search, $likeSearch) {
                    $scopedQuery
                        ->where('contracts.id', 'like', $likeSearch)
                        ->orWhereHas('customer', function ($customerQuery) use ($likeSearch) {
                            $customerQuery
                                ->where('first_name', 'like', $likeSearch)
                                ->orWhere('last_name', 'like', $likeSearch);
                        })
                        ->orWhereHas('car', function ($carQuery) use ($likeSearch) {
                            $carQuery
                                ->where('plate_number', 'like', $likeSearch)
                                ->orWhereHas('carModel', function ($modelQuery) use ($likeSearch) {
                                    $modelQuery
                                        ->where('brand', 'like', $likeSearch)
                                        ->orWhere('model', 'like', $likeSearch);
                                });
                        });
                });
            })
            ->when($this->statusFilter, fn($q) => $q->where('current_status', $this->statusFilter))
            ->when($this->userFilter === 'assigned', fn($q) => $q->whereNotNull('user_id'))
            ->when($this->userFilter === 'unassigned', fn($q) => $q->whereNull('user_id'))
            ->when($this->pickupFrom, fn($q) => $q->where('pickup_date', '>=', $this->pickupFrom))
            ->when($this->pickupTo, fn($q) => $q->where('pickup_date', '<=', $this->pickupTo))
            ->when($this->returnFrom, fn($q) => $q->where('return_date', '>=', $this->returnFrom))
            ->when($this->returnTo, fn($q) => $q->where('return_date', '<=', $this->returnTo))
            ->when($this->agentFilter === 'none', fn($q) => $q->whereNull('agent_sale'))
            ->when($this->agentFilter && $this->agentFilter !== 'none', fn($q) => $q->where('agent_sale', $this->agentFilter))
            ->when($this->kardoFilter === 'required', fn($q) => $q->where('kardo_required', true))
            ->when($this->kardoFilter === 'not_required', fn($q) => $q->where('kardo_required', false))
            ->orderByRaw("FIELD(current_status, 'pending') DESC") // Pending همیشه بالا
            ->when(!in_array($this->sortField, ['customer', 'car']), fn($q) => $q->orderBy($this->sortField, $this->sortDirection))
            ->paginate(10);

        return view('livewire.pages.panel.expert.rental-request.rental-request-list', [
            'contracts' => $contracts
        ]);
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
