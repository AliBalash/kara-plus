<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\ContractStatus;
use Livewire\Component;

class RentalRequestHistory extends Component
{
    public $contractId;
    public $contract;
    public $statuses;

    public function mount($contractId)
    {
        $this->contractId = $contractId;

        // گرفتن اطلاعات قرارداد
        $this->contract = Contract::findOrFail($this->contractId);

        // گرفتن تاریخچه وضعیت‌ها
        $this->statuses = ContractStatus::where('contract_id', $this->contractId)->orderBy('created_at', 'desc')->get();
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-history', [
            'statuses' => $this->statuses
        ]);
    }
}
