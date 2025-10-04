<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;

class RentalRequestInspectionList extends Component
{
    use WithPagination;

    public $search = '';
    public $searchInput = '';

    protected $queryString = ['search'];

    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    public function render()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $inspectionContracts = Contract::where('current_status', 'delivery')
            ->when($search !== '', function ($query) use ($likeSearch) {
                $query->where(function ($q) use ($likeSearch) {
                    $q->whereHas('customer', function ($customerQuery) use ($likeSearch) {
                        $customerQuery->where('first_name', 'like', $likeSearch)
                            ->orWhere('last_name', 'like', $likeSearch);
                    })->orWhere('contracts.id', 'like', $likeSearch);
                });
            })
            ->latest()
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
}
