<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use Illuminate\Support\Facades\Auth;

class RentalRequestKardoApproval extends BaseRentalRequestApproval
{
    public function approveKardo(): void
    {
        if (! $this->contract->kardo_required) {
            $this->toast('info', 'KARDO is not required for this contract.', false);
            return;
        }

        $this->pickupDocument->kardo_approved_at = now();
        $this->pickupDocument->kardo_approved_by = Auth::id();
        $this->pickupDocument->save();

        $this->toast('success', 'KARDO approved successfully.');
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
        $this->contract->changeStatus('agreement_inspection', $userId);
        $this->contract->changeStatus('awaiting_return', $userId);

        $this->toast('success', 'Inspection completed and status changed to awaiting_return.');
        $this->refreshApprovalState();
    }

    public function revokeKardo(): void
    {
        if (! $this->contract->kardo_required) {
            $this->toast('info', 'KARDO is not required for this contract.', false);
            return;
        }

        if (! $this->pickupDocument->kardo_approved_at) {
            $this->toast('info', 'KARDO approval is already cleared.', false);
            return;
        }

        $this->pickupDocument->kardo_approved_at = null;
        $this->pickupDocument->kardo_approved_by = null;
        $this->pickupDocument->save();

        $this->toast('success', 'KARDO approval reverted.');
        $this->refreshApprovalState();
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-kardo-approval');
    }
}
