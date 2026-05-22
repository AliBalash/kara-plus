<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Livewire\Concerns\LogsBusinessRead;
use App\Models\Contract;
use App\Models\ContractStatus;
use Livewire\Component;
use App\Livewire\Concerns\HandlesContractCancellation;

class RentalRequestHistory extends Component
{
    use HandlesContractCancellation;
    use LogsBusinessRead;
    public $contractId;
    public $contract;
    public $statuses;

    public function mount($contractId)
    {
        $this->contractId = $contractId;

        // گرفتن اطلاعات قرارداد
        $this->contract = Contract::with('agent')->findOrFail($this->contractId);

        // گرفتن تاریخچه وضعیت‌ها
        $this->statuses = ContractStatus::with(['user.roles', 'contract.agent'])
            ->where('contract_id', $this->contractId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $this->auditBusinessRead([
            'contract_id' => $this->contractId,
            'customer_id' => $this->contract->customer_id,
        ]);
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
        $this->statuses = ContractStatus::with(['user.roles', 'contract.agent'])
            ->where('contract_id', $this->contractId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }
}
