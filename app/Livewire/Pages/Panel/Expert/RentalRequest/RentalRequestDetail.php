<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\PickupDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class RentalRequestDetail extends Component
{
    public Contract $contract;
    public bool $customerDocumentsCompleted = false;
    public bool $pickupDocumentsCompleted = false;
    public bool $returnDocumentsCompleted = false;
    public bool $paymentsExist = false;
    public ?PickupDocument $pickupDocument = null;
    public bool $showStatusAdvance = false;
    public bool $canAdvanceStatus = false;
    public string $statusAdvanceButtonLabel = '';
    public string $statusAdvanceHelpText = '';

    public function mount(int $contractId): void
    {
        $this->contract = Contract::with([
            'car',
            'customer',
            'user',
            'driver',
            'deliveryDriver',
            'returnDriver',
            'pickupDocument',
            'returnDocument',
        ])->findOrFail($contractId);

        $this->pickupDocument = $this->contract->pickupDocument;

        $this->updateCompletionFlags();
        $this->updateStatusAdvanceState();
    }

    public function advanceStatus(): void
    {
        if (! $this->canAdvanceStatus) {
            return;
        }

        $userId = Auth::id();

        if (! $userId) {
            session()->flash('message', 'You need to be logged in to change status.');
            return;
        }

        $this->contract->changeStatus('agreement_inspection', $userId);
        $this->contract->changeStatus('awaiting_return', $userId);

        $this->contract->refresh()->load([
            'car',
            'customer',
            'user',
            'driver',
            'deliveryDriver',
            'returnDriver',
            'pickupDocument',
            'returnDocument',
        ]);

        $this->pickupDocument = $this->contract->pickupDocument;

        $this->updateCompletionFlags();
        $this->updateStatusAdvanceState();

        session()->flash('message', 'Status changed to awaiting return successfully.');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-detail');
    }

    private function updateCompletionFlags(): void
    {
        $this->customerDocumentsCompleted = (bool) $this->contract->customerDocument;
        $this->pickupDocumentsCompleted = (bool) $this->contract->pickupDocument;
        $this->returnDocumentsCompleted = (bool) $this->contract->returnDocument;
        $this->paymentsExist = $this->contract->payments()->exists();
    }

    private function updateStatusAdvanceState(): void
    {
        $pickupDocument = $this->pickupDocument;
        $currentStatus = $this->contract->current_status;

        $this->statusAdvanceButtonLabel = 'Move to Awaiting Return';
        $this->showStatusAdvance = true;

        if ($currentStatus !== 'delivery') {
            $this->statusAdvanceButtonLabel = 'Status up to date';
            $this->canAdvanceStatus = false;
            $statusHeadline = Str::headline($currentStatus ?? 'draft');
            $this->statusAdvanceHelpText = "Current status is {$statusHeadline}.";
            return;
        }

        if (! $pickupDocument) {
            $this->canAdvanceStatus = false;
            $this->statusAdvanceHelpText = 'Complete the pickup inspection before changing the status.';
            return;
        }

        if (! $pickupDocument->tars_approved_at) {
            $this->canAdvanceStatus = false;
            $this->statusAdvanceHelpText = 'Approve TARS to unlock the next stage.';
            return;
        }

        if ($this->contract->kardo_required && ! $pickupDocument->kardo_approved_at) {
            $this->canAdvanceStatus = false;
            $this->statusAdvanceHelpText = 'Approve KARDO to unlock the next stage.';
            return;
        }

        $this->canAdvanceStatus = true;
        $this->statusAdvanceHelpText = 'All approvals completed. Continue to awaiting return.';
    }
}
