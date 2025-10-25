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

    public function completeInspection(): void
    {
        if (! $this->pickupDocument->tars_approved_at) {
            $this->toast('error', 'Please approve TARS first.', false);
            return;
        }

        if ($this->contract->kardo_required && ! $this->pickupDocument->kardo_approved_at) {
            $this->toast('error', 'Please approve KARDO first.', false);
            return;
        }

        $userId = Auth::id();

        if (! $this->completeDeliveryInspection($userId)) {
            return;
        }

        $this->toast('success', 'Inspection completed and status changed to agreement_inspection.');

        $this->refreshApprovalState();
    }

    public function moveToAwaitingReturn(): void
    {
        $userId = Auth::id();

        if (! $this->advanceContractToAwaitingReturn($userId)) {
            return;
        }

        $this->toast('success', 'Status changed to awaiting_return.');

        $this->refreshApprovalState();
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-tars-approval');
    }
}
