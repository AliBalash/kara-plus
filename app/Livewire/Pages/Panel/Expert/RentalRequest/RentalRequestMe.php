<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;
use App\Livewire\Concerns\HandlesContractCancellation;

class RentalRequestMe extends Component
{
    use HandlesContractCancellation;
    public $contracts = [];
    public $search = '';
    public $searchInput = '';

    protected $queryString = ['search'];

    public function mount(): void
    {
        $this->searchInput = $this->search;
        $this->loadContracts();
    }
    /**
     * بارگذاری قراردادهای کاربر
     */
    public function loadContracts()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $query = Contract::query()
            ->where('user_id', auth()->id())
            ->with(['customer', 'car.carModel', 'user']);

        if ($search !== '') {
            $query->where(function ($scoped) use ($likeSearch) {
                $scoped->where('contracts.id', 'like', $likeSearch)
                    ->orWhereHas('customer', function ($customerQuery) use ($likeSearch) {
                        $customerQuery->where('first_name', 'like', $likeSearch)
                            ->orWhere('last_name', 'like', $likeSearch);
                    })
                    ->orWhereHas('car', function ($carQuery) use ($likeSearch) {
                        $carQuery->where('plate_number', 'like', $likeSearch)
                            ->orWhereHas('carModel', function ($modelQuery) use ($likeSearch) {
                                $modelQuery->where('brand', 'like', $likeSearch)
                                    ->orWhere('model', 'like', $likeSearch);
                            });
                    });
            });
        }

        $this->contracts = $query->latest('pickup_date')->get();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->loadContracts();
    }

    protected function afterContractCancelled(): void
    {
        $this->loadContracts();
    }
    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-me', [
            'contracts' => $this->contracts,
        ]);
    }
}
