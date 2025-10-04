<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;

class RentalRequestReserved extends Component
{
    use WithPagination;

    public $search = ''; // متغیر جستجو
    public $searchInput = '';
    public $sortField = 'pickup_date'; // Default sort field
    public $sortDirection = 'asc'; // Default sort direction

    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    // Load contracts with sorting and search
    public function loadContracts()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        return Contract::query()
            ->where('current_status', 'reserved')
            ->with(['customer', 'car', 'user']) // Eager load relationships
            ->when($search !== '', function ($query) use ($likeSearch) {
                $query->where(function ($scoped) use ($likeSearch) {
                    $scoped->whereHas('customer', function ($q) use ($likeSearch) {
                        $q->where('first_name', 'like', $likeSearch)
                            ->orWhere('last_name', 'like', $likeSearch);
                    })->orWhere('contracts.id', 'like', $likeSearch);
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10); // Paginate with 10 items per page
    }

    // Toggle sort direction when clicking on the column header
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            // Toggle direction if the same field is clicked
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // Set new sort field and default to ascending
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage(); // Reset to first page when sorting changes
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
}
