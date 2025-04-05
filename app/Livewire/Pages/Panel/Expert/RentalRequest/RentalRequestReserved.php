<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;

class RentalRequestReserved extends Component
{

    public $reservedContracts;
    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    public function mount()
    {
        $this->reservedContracts = Contract::where('current_status', 'reserved')->latest()->get();
    }

    public $search = '';  // متغیر جستجو
    // متد برای فیلتر کردن داده‌ها بر اساس جستجو
    public function updatedSearch()
    {
        $this->reservedContracts = Contract::query()
            ->whereHas('customer', function ($query) {
                $query->where('first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('last_name', 'like', '%' . $this->search . '%');
            })
            ->orWhere('id', 'like', '%' . $this->search . '%')
            ->latest() // Order by created_at DESC
            ->get();
    }

    public function changeStatusToDelivery($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        // تغییر وضعیت به 'delivery'
        $contract->changeStatus('delivery', auth()->id());
        
        // **بروزرسانی لیست قراردادها**
        $this->reservedContracts = Contract::where('current_status', 'reserved')->get();

        // ارسال دستور برای به‌روزرسانی داده‌ها
        $this->dispatch('refreshContracts');
        session()->flash('success', 'Status changed to Reserved successfully.');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-reserved', [
            'contracts' => $this->reservedContracts,
        ]);
    }
}
