<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;

class RentalRequestAwaitingReturnList extends Component
{

    public $awaitContracts;
    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    public function mount()
    {
        $this->awaitContracts = Contract::where('current_status', 'awaiting_return')->latest()->get();
    }

    public $search = '';  // متغیر جستجو
    // متد برای فیلتر کردن داده‌ها بر اساس جستجو
    public function updatedSearch()
    {
        $this->awaitContracts = Contract::query()
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
        return view(
            'livewire.pages.panel.expert.rental-request.rental-request-awaiting-return-list',
            [
                'contracts' => $this->awaitContracts
            ]
        );
    }
}
