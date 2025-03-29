<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use Livewire\Component;
use App\Models\Payment;
use App\Models\Contract;
use App\Models\CustomerDocument;
use App\Models\Fine;

class RentalRequestPayment extends Component
{
    public $contractId;
    public $customerId;
    public $amount;
    public $payment_type;
    public $payment_date;

    public $existingPayments;  // List of existing payments
    public $totalPrice;        // Total price from the contract
    public $fines;             // Fines associated with the contract

    public $hasCustomerDocument;
    public $hasPayments;
    
    protected $rules = [
        'amount' => 'required|numeric|min:0',
        'payment_type' => 'required|in:rental_fee,fine',
        'payment_date' => 'required|date',
    ];

    public function mount($contractId, $customerId)
    {
        $this->contractId = $contractId;
        $this->customerId = $customerId;

        // بررسی وجود اسناد مشتری
        $this->hasCustomerDocument = CustomerDocument::where('customer_id', $this->customerId)
            ->where('contract_id', $this->contractId)
            ->exists();

        // بررسی وجود پرداخت‌ها
        $this->hasPayments = Payment::where('customer_id', $this->customerId)
            ->where('contract_id', $this->contractId)
            ->exists();


        // Fetch existing payments for this contract and customer
        $this->existingPayments = Payment::where('contract_id', $contractId)
            ->where('customer_id', $customerId)
            ->get();

        // Fetch total price from the contract
        $this->totalPrice = Contract::where('id', $contractId)->value('total_price');

        // Fetch fines associated with the contract
        $this->fines = Fine::where('contract_id', $contractId)->get();
    }

    public function submitPayment()
    {
        $this->validate();

        try {
            // Create new payment entry
            Payment::create([
                'contract_id' => $this->contractId,
                'customer_id' => $this->customerId,
                'amount' => $this->amount,
                'payment_type' => $this->payment_type,
                'payment_date' => $this->payment_date,
            ]);

            session()->flash('message', 'Payment successfully added!');
            $this->resetForm();
            $this->existingPayments = Payment::where('contract_id', $this->contractId)
                ->where('customer_id', $this->customerId)
                ->get(); // Refresh payments list

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add payment: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->amount = '';
        $this->payment_type = '';
        $this->payment_date = '';
    }

    public function render()
    {
        $rentalPaid = $this->existingPayments->where('payment_type', 'rental_fee')->sum('amount');
        $remainingBalance = $this->totalPrice - $rentalPaid;

        return view('livewire.pages.panel.expert.rental-request.rental-request-payment', compact('rentalPaid', 'remainingBalance'));
    }
}
