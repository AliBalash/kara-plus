<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use Livewire\Component;
use App\Models\Contract; // Assuming Contract is your model for rental requests

class RentalRequestDetail extends Component
{
    public $contract;

    public function mount($contractId)
    {
        // Retrieve the contract details from the database
        $this->contract = Contract::with(['car', 'customer'])->findOrFail($contractId);
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-detail');
    }
}
