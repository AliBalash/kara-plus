<?php

namespace App\Livewire\Pages\Panel\Expert\Customer;

use Livewire\Component;
use App\Models\Customer;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\SearchesCustomerPhone;
use Livewire\WithPagination;

class CustomerList extends Component
{
    use WithPagination;
    use InteractsWithToasts;
    use SearchesCustomerPhone;

    public $search = '';
    public $searchInput = '';

    protected $queryString = ['search'];

    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    public function deleteCustomer($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        $customer->delete();
        $this->toast('success', 'Customer deleted successfully.');
    }

    public function render()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';
        $isPhoneSearch = $this->isCustomerPhoneSearch($search);

        $customers = Customer::query()
            ->when($search !== '', function ($query) use ($likeSearch, $isPhoneSearch) {
                $query->where(function ($scoped) use ($likeSearch, $isPhoneSearch) {
                    $scoped->where('first_name', 'like', $likeSearch)
                        ->orWhere('last_name', 'like', $likeSearch)
                        ->orWhere('national_code', 'like', $likeSearch);

                    if ($isPhoneSearch) {
                        $scoped->orWhere('phone', 'like', $likeSearch);
                    }
                });
            })
            ->paginate(10);

        return view('livewire.pages.panel.expert.customer.customer-list', compact('customers'));
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }
}
