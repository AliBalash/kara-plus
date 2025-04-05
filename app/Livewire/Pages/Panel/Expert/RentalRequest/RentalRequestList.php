<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use Livewire\Component;
use App\Models\Contract;

class RentalRequestList extends Component
{
    public $contracts;

    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    public function mount()
    {
        // Load the contracts in descending order (latest first)
        $this->contracts = Contract::with(['customer', 'car', 'user'])
            ->latest() // Order by created_at DESC
            ->get();
    }

    public function assignToMe($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        if (is_null($contract->user_id)) {
            // اختصاص کاربر به قرارداد
            $contract->update([
                'user_id' => auth()->id(),
            ]);

            // تغییر وضعیت به 'assigned'
            $contract->changeStatus('assigned', auth()->id());

            session()->flash('success', 'Contract assigned to you successfully.');

            // ارسال دستور برای به‌روزرسانی داده‌ها
            $this->dispatch('refreshContracts');
        } else {
            session()->flash('error', 'This contract is already assigned.');
        }
    }

    public function changeStatusToReserve($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        if ($contract->user_id === auth()->id()) {
            // تغییر وضعیت به 'assigned'
            $contract->changeStatus('reserved', auth()->id());

            // ارسال دستور برای به‌روزرسانی داده‌ها
            $this->dispatch('refreshContracts');
            session()->flash('success', 'Status changed to Reserved successfully.');
        } else {
            session()->flash('error', 'You are not authorized to perform this action.');
        }
    }


    public $search = '';  // متغیر جستجو
    // متد برای فیلتر کردن داده‌ها بر اساس جستجو
    public function updatedSearch()
    {
        $this->contracts = Contract::query()
            ->whereHas('customer', function ($query) {
                $query->where('first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('last_name', 'like', '%' . $this->search . '%');
            })
            ->orWhere('id', 'like', '%' . $this->search . '%')
            ->latest() // Order by created_at DESC
            ->get();
    }



    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-list', [
            'contracts' => $this->contracts,
        ]);
    }
}
