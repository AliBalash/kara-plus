<?php

namespace App\Livewire\Pages\Panel\Expert\Customer;

use Livewire\Component;
use App\Models\Customer;
use App\Livewire\Concerns\InteractsWithToasts;

class CustomerDetail extends Component
{
    use InteractsWithToasts;
    public $customer; // Holds the customer data

    public function mount($customerId)
    {
        // Fetch the customer by ID and convert to array
        $customer = Customer::findOrFail($customerId);
        $this->customer = $customer->toArray();
    }

    protected $rules = [
        'customer.first_name' => 'required|string|max:255',
        'customer.last_name' => 'required|string|max:255',
        'customer.national_code' => 'nullable|string|max:20',
        'customer.email' => 'required|nullable|email|max:255',
        'customer.phone' => 'required|nullable|string|max:20',
        'customer.address' => 'nullable|string|max:500',
        'customer.birth_date' => 'nullable|date',
        'customer.passport_number' => 'nullable|string|max:50',
        'customer.passport_expiry_date' => 'nullable|date',
        'customer.nationality' => 'nullable|string|max:100',
        'customer.license_number' => 'nullable|string|max:50',
        'customer.status' => 'required|in:active,inactive', // اعتبارسنجی وضعیت
        'customer.registration_date' => 'nullable|date',
    ];



    public function updateCustomer()
    {
        // Validate and update the customer details
        $this->validate();
        // Find the customer and update with the new data
        $customer = Customer::findOrFail($this->customer['id']); // Ensure 'id' exists in $this->customer
        $customer->update($this->customer);

        $this->toast('success', 'Customer details updated successfully.');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.customer.customer-detail');
    }
}
