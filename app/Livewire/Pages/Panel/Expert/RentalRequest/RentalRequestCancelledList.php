<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Livewire\Concerns\HandlesContractCancellation;
use App\Livewire\Concerns\SearchesCustomerPhone;
use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;

class RentalRequestCancelledList extends Component
{
    use WithPagination;
    use HandlesContractCancellation;
    use SearchesCustomerPhone;

    public $search = '';
    public $searchInput = '';
    public $userFilter = '';
    public $pickupFrom;
    public $pickupTo;
    public $returnFrom;
    public $returnTo;
    public $sortField = 'updated_at';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshContracts' => '$refresh'];
    protected $queryString = [
        'search',
        'userFilter',
        'pickupFrom',
        'pickupTo',
        'returnFrom',
        'returnTo',
        'sortField',
        'sortDirection',
    ];

    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            return;
        }

        $this->sortField = $field;
        $this->sortDirection = 'asc';
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'userFilter',
            'pickupFrom',
            'pickupTo',
            'returnFrom',
            'returnTo',
        ]);

        $this->searchInput = '';
        $this->sortField = 'updated_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    protected function afterContractCancelled(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';
        $isPhoneSearch = $this->isCustomerPhoneSearch($search);

        $contracts = Contract::with(['customer', 'car.carModel', 'user', 'latestStatus.user', 'agent'])
            ->where('current_status', 'cancelled')
            ->when($search !== '', function ($query) use ($likeSearch, $isPhoneSearch) {
                $query->where(function ($scopedQuery) use ($likeSearch, $isPhoneSearch) {
                    $scopedQuery
                        ->where('contracts.id', 'like', $likeSearch)
                        ->orWhereHas('customer', function ($customerQuery) use ($likeSearch, $isPhoneSearch) {
                            $customerQuery
                                ->where('first_name', 'like', $likeSearch)
                                ->orWhere('last_name', 'like', $likeSearch);

                            if ($isPhoneSearch) {
                                $customerQuery->orWhere('phone', 'like', $likeSearch);
                            }
                        })
                        ->orWhereHas('car', function ($carQuery) use ($likeSearch) {
                            $carQuery
                                ->where('plate_number', 'like', $likeSearch)
                                ->orWhereHas('carModel', function ($modelQuery) use ($likeSearch) {
                                    $modelQuery
                                        ->where('brand', 'like', $likeSearch)
                                        ->orWhere('model', 'like', $likeSearch);
                                });
                        });
                });
            })
            ->when($this->userFilter === 'assigned', fn($query) => $query->whereNotNull('user_id'))
            ->when($this->userFilter === 'unassigned', fn($query) => $query->whereNull('user_id'))
            ->when($this->pickupFrom, fn($query) => $query->whereDate('pickup_date', '>=', $this->pickupFrom))
            ->when($this->pickupTo, fn($query) => $query->whereDate('pickup_date', '<=', $this->pickupTo))
            ->when($this->returnFrom, fn($query) => $query->whereDate('return_date', '>=', $this->returnFrom))
            ->when($this->returnTo, fn($query) => $query->whereDate('return_date', '<=', $this->returnTo))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.pages.panel.expert.rental-request.rental-request-cancelled-list', [
            'contracts' => $contracts,
        ]);
    }
}
