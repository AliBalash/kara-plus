<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use Livewire\Component;
use App\Models\Contract; // Assuming Contract is your model for rental requests

class RentalRequestDetail extends Component
{
    public $contract;
    public $customerDocumentsCompleted = false;
    public $pickupDocumentsCompleted = false;
    public $returnDocumentsCompleted = false;
    public $paymentsExist = false;

    public function mount($contractId)
    {
        $this->contract = Contract::with(['car', 'customer', 'user', 'driver'])->findOrFail($contractId);

        // Document & Payment Status Flags
        $this->customerDocumentsCompleted = !is_null($this->contract->customerDocument);
        $this->pickupDocumentsCompleted = !is_null($this->contract->pickupDocument);
        $this->returnDocumentsCompleted = !is_null($this->contract->returnDocument);
        $this->paymentsExist = $this->contract->payments()->exists();
    }


    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-detail');
    }
}
