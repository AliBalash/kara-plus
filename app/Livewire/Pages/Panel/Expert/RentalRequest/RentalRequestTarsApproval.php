<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use Illuminate\Support\Facades\Auth;

class RentalRequestTarsApproval extends BaseRentalRequestApproval
{
    public function approveTars(): void
    {
        if ($this->pickupDocument->tars_contract) {
            $this->pickupDocument->tars_approved_at = now();
            $this->pickupDocument->tars_approved_by = Auth::id();
            $this->pickupDocument->save();
            $this->toast('success', 'TARS approved successfully.');
            return;
        }

        $this->toast('error', 'TARS contract not uploaded.', false);
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-tars-approval');
    }
}
