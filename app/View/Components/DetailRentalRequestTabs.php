<?php

namespace App\View\Components;

use App\Models\Contract;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DetailRentalRequestTabs extends Component
{
    public $contract;
    public $customerDocumentsCompleted;
    public $pickupDocumentsCompleted;
    public $returnDocumentsCompleted;
    public $paymentsExist;

    public function __construct($contractId)
    {
        $this->contract = Contract::with(['car', 'customer'])->findOrFail($contractId);

        // Document & Payment Status Flags
        $this->customerDocumentsCompleted = !is_null($this->contract->customerDocument);
        $this->pickupDocumentsCompleted = !is_null($this->contract->pickupDocument);
        $this->returnDocumentsCompleted = !is_null($this->contract->returnDocument);
        $this->paymentsExist = $this->contract->payments()->exists();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.detail-rental-request-tabs');
    }
}
