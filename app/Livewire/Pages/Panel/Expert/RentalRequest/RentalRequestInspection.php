<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\PickupDocument;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class RentalRequestInspection extends Component
{
    public $contractId;
    public $contract;
    public $pickupDocument;
    public $existingFiles = [];

    public function mount($contractId)
    {
        $this->contractId = $contractId;
        $this->contract = Contract::findOrFail($contractId);
        $this->pickupDocument = PickupDocument::firstOrNew([
            'contract_id' => $contractId,
        ]);

        $storage = Storage::disk('myimage');
        $this->existingFiles = [
            'tarsContract' => $storage->exists("PickupDocument/tars_contract_{$contractId}.jpg")
                ? Storage::url("PickupDocument/tars_contract_{$contractId}.jpg")
                : null,
            'kardoContract' => $storage->exists("PickupDocument/kardo_contract_{$contractId}.jpg")
                ? Storage::url("PickupDocument/kardo_contract_{$contractId}.jpg")
                : null,
            'factorContract' => $storage->exists("PickupDocument/factor_contract_{$contractId}.jpg")
                ? Storage::url("PickupDocument/factor_contract_{$contractId}.jpg")
                : null,
            'carDashboard' => $storage->exists("PickupDocument/car_dashboard_{$contractId}.jpg")
                ? Storage::url("PickupDocument/car_dashboard_{$contractId}.jpg")
                : null,
        ];
    }

    public function approveTars()
    {
        if ($this->pickupDocument->tars_contract) {
            $this->pickupDocument->tars_approved_at = now();
            $this->pickupDocument->tars_approved_by = auth()->id();
            $this->pickupDocument->save();
            session()->flash('success', 'TARS approved successfully.');
        } else {
            session()->flash('error', 'TARS contract not uploaded.');
        }
    }

    public function approveKardo()
    {
        if (!$this->contract->kardo_required) {
            session()->flash('info', 'KARDO is not required for this contract.');
            return;
        }
        if ($this->pickupDocument->kardo_contract) {
            $this->pickupDocument->kardo_approved_at = now();
            $this->pickupDocument->kardo_approved_by = auth()->id();
            $this->pickupDocument->save();
            session()->flash('success', 'KARDO approved successfully.');
        } else {
            session()->flash('error', 'KARDO contract not uploaded.');
        }
    }

    public function completeInspection()
    {
        if (!$this->pickupDocument->tars_approved_at) {
            session()->flash('error', 'Please approve TARS first.');
            return;
        }
        if ($this->contract->kardo_required && !$this->pickupDocument->kardo_approved_at) {
            session()->flash('error', 'Please approve KARDO first.');
            return;
        }

        // Set status to agreement_inspection first
        $this->contract->changeStatus('agreement_inspection', auth()->id());
        // Then immediately set to awaiting_return
        $this->contract->changeStatus('awaiting_return', auth()->id());
        session()->flash('success', 'Inspection completed and status changed to awaiting_return.');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-inspection');
    }
}
