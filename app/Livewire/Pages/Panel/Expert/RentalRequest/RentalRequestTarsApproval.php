<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use Illuminate\Support\Facades\Auth;

class RentalRequestTarsApproval extends BaseRentalRequestApproval
{
    public function approveTars(): void
    {
        $this->pickupDocument->tars_approved_at = now();
        $this->pickupDocument->tars_approved_by = Auth::id();
        $this->pickupDocument->save();

        $this->toast('success', 'TARS approved successfully.');
        $this->refreshApprovalState();
    }

    public function revokeTars(): void
    {
        if (! $this->pickupDocument->tars_approved_at) {
            $this->toast('info', 'TARS approval is already cleared.', false);
            return;
        }

        $this->pickupDocument->tars_approved_at = null;
        $this->pickupDocument->tars_approved_by = null;
        $this->pickupDocument->save();

        $this->toast('success', 'TARS approval reverted.');
        $this->refreshApprovalState();
    }

    public function changeStatusToDelivery(): void
    {
        $userId = Auth::id();

        if (! $userId) {
            $this->toast('error', 'You need to be logged in to change the status.', false);
            return;
        }

        $this->contract->changeStatus('delivery', $userId);
        $this->toast('success', 'Status changed to delivery successfully.');

        $this->refreshApprovalState();
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-tars-approval');
    }
}
