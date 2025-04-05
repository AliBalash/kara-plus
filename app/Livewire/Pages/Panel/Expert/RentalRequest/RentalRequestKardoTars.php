<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;

class RentalRequestKardoTars extends Component
{

    public $kardotarsContracts;
    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    public function mount()
    {
        $this->kardotarsContracts = Contract::where('current_status', 'delivery')->get();
    }

    public $search = '';  // متغیر جستجو
    // متد برای فیلتر کردن داده‌ها بر اساس جستجو
    public function updatedSearch()
    {
        $this->kardotarsContracts = Contract::query()
            ->whereHas('customer', function ($query) {
                $query->where('first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('last_name', 'like', '%' . $this->search . '%');
            })
            ->orWhere('id', 'like', '%' . $this->search . '%')
            ->latest() // Order by created_at DESC
            ->get();
    }

    public function changeStatusToAwaitingReturn($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        // تغییر وضعیت به 'delivery'
        $contract->changeStatus('awaiting_return', auth()->id());
        
        // **بروزرسانی لیست قراردادها**
        $this->kardotarsContracts = Contract::where('current_status', 'delivery')->get();

        // ارسال دستور برای به‌روزرسانی داده‌ها
        $this->dispatch('refreshContracts');
        session()->flash('success', 'Status changed to Reserved successfully.');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-kardotars', [
            'contracts' => $this->kardotarsContracts,
        ]);
    }
}
