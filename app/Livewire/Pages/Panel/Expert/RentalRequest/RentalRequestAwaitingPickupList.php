<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\HandlesContractCancellation;

class RentalRequestAwaitingPickupList extends Component
{
    use WithPagination;
    use HandlesContractCancellation;

    public $search = '';
    public $searchInput = '';
    public $perPage = 10;

    protected $queryString = ['search', 'perPage'];

    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    // برگشت query برای لیست با پشتیبانی از جستجو و مرتب‌سازی ثابت بر اساس pickup_date
    public function loadContracts()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $query = Contract::query()
            ->with(['customer', 'car.carModel', 'user', 'pickupDocument'])
            ->where('current_status', 'reserved'); // یا وضعیت مورد نظر شما

        if ($search !== '') {
            $query->where(function ($q) use ($likeSearch) {
                $q->where('contracts.id', 'like', $likeSearch)
                    ->orWhereHas('customer', function ($q2) use ($likeSearch) {
                        $q2->where('first_name', 'like', $likeSearch)
                            ->orWhere('last_name', 'like', $likeSearch);
                    })
                    ->orWhereHas('car', function ($q3) use ($likeSearch) {
                        $q3->where('plate_number', 'like', $likeSearch)
                            ->orWhereHas('carModel', function ($modelQuery) use ($likeSearch) {
                                $modelQuery->where('brand', 'like', $likeSearch)
                                    ->orWhere('model', 'like', $likeSearch);
                            });
                    });
            });
        }

        return $query->orderBy('pickup_date', 'asc') // مرتب‌سازی ثابت بر اساس نزدیک‌ترین تاریخ pickup
            ->paginate($this->perPage);
    }

    // اگر بخوای تعداد آیتم در صفحه رو تغییر بدی
    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-awaiting-pickup-list', [
            'contracts' => $this->loadContracts(),
        ]);
    }
}
