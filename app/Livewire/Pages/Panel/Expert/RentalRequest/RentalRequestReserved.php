<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;

class RentalRequestReserved extends Component
{
    use WithPagination;

    public $search = ''; // متغیر جستجو
    public $sortField = 'pickup_date'; // Default sort field
    public $sortDirection = 'asc'; // Default sort direction

    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    // Load contracts with sorting and search
    public function loadContracts()
    {
        return Contract::query()
            ->where('current_status', 'reserved')
            ->with(['customer', 'car', 'user']) // Eager load relationships
            ->when($this->search, function ($query) {
                $query->whereHas('customer', function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%');
                })->orWhere('id', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10); // Paginate with 10 items per page
    }

    // Handle search updates
    public function updatedSearch()
    {
        $this->resetPage(); // Reset to first page when search changes
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

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-reserved', [
            'reservedContracts' => $this->loadContracts(),
        ]);
    }
}