<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;

class RentalRequestMe extends Component
{

    public $contracts;

    public function mount()
    {
        // بارگذاری اولیه قراردادهای کاربر
        $this->loadContracts();
    }
    /**
     * بارگذاری قراردادهای کاربر
     */
    public function loadContracts()
    {
        $this->contracts = Contract::query()
            ->where('user_id', auth()->id()) // فیلتر بر اساس کاربر لاگین شده
            ->with(['customer', 'car', 'user']) // بارگذاری روابط مورد نیاز
            ->get();
    }


    public $search = '';  // متغیر جستجو
    // متد برای فیلتر کردن داده‌ها بر اساس جستجو
    public function updatedSearch()
    {
        if (!empty($this->search)) {

            $this->contracts = Contract::query()
                ->where('user_id', auth()->id()) // فقط قراردادهای کاربر لاگین شده
                ->whereHas('customer', function ($query) {
                    $query->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('id', 'like', '%' . $this->search . '%')
                ->get();
        } else {
            $this->loadContracts();
        }
    }
    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-me', [
            'contracts' => $this->contracts,
        ]);
    }
}
