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
        // فقط داده‌های ضروری را بارگذاری کنید.
        $this->contracts = Contract::with(['customer', 'car', 'user'])->get();
    }

    public function assignToMe($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        if (is_null($contract->user_id)) {
            $contract->update([
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', 'Contract assigned to you successfully.');

            // ارسال دستور برای به‌روزرسانی داده‌ها
            $this->dispatch('refreshContracts');
        } else {
            session()->flash('error', 'This contract is already assigned.');
        }
    }

    public $search = '';  // متغیر جستجو
    // متد برای فیلتر کردن داده‌ها بر اساس جستجو
    public function updatedSearch()
    {
        dd($this->search);
        $this->contracts = Contract::query()
            ->whereHas('customer', function ($query) {
                $query->where('first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('last_name', 'like', '%' . $this->search . '%');
            })
            ->orWhere('id', 'like', '%' . $this->search . '%')
            ->get();
    }


    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-list', [
            'contracts' => $this->contracts,
        ]);
    }
}
