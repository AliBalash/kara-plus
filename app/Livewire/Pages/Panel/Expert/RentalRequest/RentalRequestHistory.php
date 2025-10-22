<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\ContractStatus;
use Livewire\Component;
use App\Livewire\Concerns\HandlesContractCancellation;

class RentalRequestHistory extends Component
{
    use HandlesContractCancellation;
    public $contractId;
    public $contract;
    public $statuses;

    public function mount($contractId)
    {
        $this->contractId = $contractId;

        // گرفتن اطلاعات قرارداد
        $this->contract = Contract::findOrFail($this->contractId);

        // گرفتن تاریخچه وضعیت‌ها
        $this->statuses = ContractStatus::with(['user.roles'])
            ->where('contract_id', $this->contractId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-history', [
            'statuses' => $this->statuses
        ]);
    }

    protected function afterContractCancelled(): void
    {
        $this->contract->refresh();
        $this->statuses = ContractStatus::with(['user.roles'])
            ->where('contract_id', $this->contractId)
            ->orderByDesc('created_at')
            ->get();
    }
}
