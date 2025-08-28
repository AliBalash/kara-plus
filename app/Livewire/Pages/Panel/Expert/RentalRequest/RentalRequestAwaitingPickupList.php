<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;

class RentalRequestAwaitingPickupList extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    protected $queryString = ['search', 'perPage'];

    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    // برگشت query برای لیست با پشتیبانی از جستجو و مرتب‌سازی ثابت بر اساس pickup_date
    public function loadContracts()
    {
        $query = Contract::query()
            ->with(['customer', 'car', 'user', 'pickupDocument']) // eager load مورد نیاز
            ->where('current_status', 'reserved'); // یا وضعیت مورد نظر شما

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('id', 'like', '%' . $this->search . '%')
                    ->orWhereHas('customer', function ($q2) {
                        $q2->where('first_name', 'like', '%' . $this->search . '%')
                            ->orWhere('last_name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('car', function ($q3) {
                        $q3->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        return $query->orderBy('pickup_date', 'asc') // مرتب‌سازی ثابت بر اساس نزدیک‌ترین تاریخ pickup
            ->paginate($this->perPage);
    }

    // ریست شماره صفحه هنگام تغییر جستجو
    public function updatedSearch()
    {
        $this->resetPage();
    }

    // اگر بخوای تعداد آیتم در صفحه رو تغییر بدی
    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-awaiting-pickup-list', [
            'contracts' => $this->loadContracts(),
        ]);
    }
}
