<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Contract;

class RentalRequestList extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $userFilter = '';
    public $pickupFrom;
    public $pickupTo;
    public $returnFrom;
    public $returnTo;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshContracts' => '$refresh'];
    protected $queryString = [
        'search',
        'statusFilter',
        'userFilter',
        'pickupFrom',
        'pickupTo',
        'returnFrom',
        'returnTo',
        'sortField',
        'sortDirection'
    ];

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
        $this->reset(['search', 'statusFilter', 'userFilter', 'pickupFrom', 'pickupTo', 'returnFrom', 'returnTo']);
        $this->resetPage();
    }

    public function assignToMe($contractId)
    {
        $contract = Contract::findOrFail($contractId);
        if (is_null($contract->user_id)) {
            $contract->update(['user_id' => auth()->id()]);
            $contract->changeStatus('assigned', auth()->id());
            session()->flash('success', 'Contract assigned to you successfully.');
            $this->emit('refreshContracts');
        } else {
            session()->flash('error', 'This contract is already assigned.');
        }
    }

    public function render()
    {
        $contracts = Contract::with(['customer', 'car', 'user'])
            ->when($this->search, function ($q) {
                $q->where('id', 'like', "%{$this->search}%")
                    ->orWhereHas('customer', fn($q2) => $q2->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%"))
                    ->orWhereHas('car', fn($q3) => $q3->where('brand', 'like', "%{$this->search}%")
                        ->orWhere('model', 'like', "%{$this->search}%"));
            })
            ->when($this->statusFilter, fn($q) => $q->where('current_status', $this->statusFilter))
            ->when($this->userFilter === 'assigned', fn($q) => $q->whereNotNull('user_id'))
            ->when($this->userFilter === 'unassigned', fn($q) => $q->whereNull('user_id'))
            ->when($this->pickupFrom, fn($q) => $q->where('pickup_date', '>=', $this->pickupFrom))
            ->when($this->pickupTo, fn($q) => $q->where('pickup_date', '<=', $this->pickupTo))
            ->when($this->returnFrom, fn($q) => $q->where('return_date', '>=', $this->returnFrom))
            ->when($this->returnTo, fn($q) => $q->where('return_date', '<=', $this->returnTo))
            ->orderByRaw("FIELD(current_status, 'pending') DESC") // Pending همیشه بالا
            ->when(!in_array($this->sortField, ['customer', 'car']), fn($q) => $q->orderBy($this->sortField, $this->sortDirection))
            ->paginate(10);

        return view('livewire.pages.panel.expert.rental-request.rental-request-list', [
            'contracts' => $contracts
        ]);
    }
}
