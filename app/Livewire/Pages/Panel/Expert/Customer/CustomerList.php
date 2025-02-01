<?php

namespace App\Livewire\Pages\Panel\Expert\Customer;

use Livewire\Component;
use App\Models\Customer;
use Livewire\WithPagination;

class CustomerList extends Component
{
    use WithPagination;

    public $search = ''; // Search query

    // Reset pagination when the search query is updated
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deleteCustomer($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        $customer->delete();
        session()->flash('message', 'Customer deleted successfully.');
    }

    public function render()
    {
        $customers = Customer::query()
            ->when($this->search, function ($query) {
                $query->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('national_code', 'like', "%{$this->search}%");
            })
            ->paginate(10);

        return view('livewire.pages.panel.expert.customer.customer-list', compact('customers'));
    }
}
