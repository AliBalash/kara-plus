<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;

class RentalRequestInspectionList extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        $inspectionContracts = Contract::where('current_status', 'delivery')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('customer', function ($q) {
                        $q->where('first_name', 'like', '%' . $this->search . '%')
                            ->orWhere('last_name', 'like', '%' . $this->search . '%');
                    })->orWhere('id', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.pages.panel.expert.rental-request.rental-request-inspection-list', [
            'inspectionContracts' => $inspectionContracts
        ]);
    }
}
