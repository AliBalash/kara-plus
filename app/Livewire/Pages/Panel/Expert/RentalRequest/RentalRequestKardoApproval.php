<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use Illuminate\Support\Facades\Auth;

class RentalRequestKardoApproval extends BaseRentalRequestApproval
{
    public function approveKardo(): void
    {
        if (! $this->contract->kardo_required) {
            session()->flash('info', 'KARDO is not required for this contract.');
            return;
        }

        if ($this->pickupDocument->kardo_contract) {
            $this->pickupDocument->kardo_approved_at = now();
            $this->pickupDocument->kardo_approved_by = Auth::id();
            $this->pickupDocument->save();
            session()->flash('success', 'KARDO approved successfully.');
            return;
        }

        session()->flash('error', 'KARDO contract not uploaded.');
    }

    public function completeInspection(): void
    {
        if (! $this->pickupDocument->tars_approved_at) {
            session()->flash('error', 'Please approve TARS first.');
            return;
        }

        if ($this->contract->kardo_required && ! $this->pickupDocument->kardo_approved_at) {
            session()->flash('error', 'Please approve KARDO first.');
            return;
        }

        $userId = Auth::id();
        $this->contract->changeStatus('agreement_inspection', $userId);
        $this->contract->changeStatus('awaiting_return', $userId);

        session()->flash('success', 'Inspection completed and status changed to awaiting_return.');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-kardo-approval');
    }
}
