<?php

namespace App\Livewire\Pages\Panel\Expert\Customer;

use App\Models\Contract;
use Livewire\Component;

class CustomerHistory extends Component
{

    public $customerId; // Holds the customer ID
    public $contracts; // Holds the contracts related to the customer
    
    public function mount($customerId)
    {
        $this->customerId = $customerId;
    
        // فقط قراردادهایی که مربوط به این مشتری هستند بارگذاری کن
        $this->contracts = Contract::with(['customer', 'car', 'user'])
            ->where('customer_id', $this->customerId) // فیلتر کردن بر اساس ID مشتری
            ->get();
    }
    

    public function render()
    {
        return view('livewire.pages.panel.expert.customer.customer-history');
    }
}
