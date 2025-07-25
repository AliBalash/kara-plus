<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use Livewire\Component;
use App\Models\Payment;
use App\Models\Contract;
use App\Models\CustomerDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class RentalRequestPayment extends Component
{

    use WithFileUploads;
    public $contractId;
    public $customerId;
    public $amount;
    public $currency = 'IRR';
    public $payment_type;
    public $payment_date;
    public $is_refundable = false;

    public $existingPayments;
    public $totalPrice;
    public $rentalPaid;
    public $remainingBalance;

    public $hasCustomerDocument;
    public $hasPayments;

    public $receipt;


    public $rate;


    protected $rules = [
        'amount' => 'required|numeric|min:0',
        'currency' => 'required|in:IRR,USD,AED',
        'payment_type' => 'required|in:rental_fee,prepaid_fine,toll,fine',
        'payment_date' => 'required|date',
        'is_refundable' => 'required|boolean',
        'rate' => 'nullable|numeric|min:0.0001',
        'receipt' => 'nullable|image|max:2048', // optional receipt image
    ];

    public function mount($contractId, $customerId)
    {
        $this->contractId = $contractId;
        $this->customerId = $customerId;

        // دریافت total_price از مدل Contract
        $this->totalPrice = Contract::where('id', $this->contractId)->value('total_price') ?? 0;

        // بررسی وجود اسناد مشتری
        $this->hasCustomerDocument = CustomerDocument::where('customer_id', $this->customerId)
            ->where('contract_id', $this->contractId)
            ->exists();

        // بررسی وجود پرداخت‌ها
        $this->hasPayments = Payment::where('customer_id', $this->customerId)
            ->where('contract_id', $this->contractId)
            ->exists();

        $this->loadData();
    }

    public function loadData()
    {
        $this->existingPayments = Payment::where('contract_id', $this->contractId)
            ->where('customer_id', $this->customerId)
            ->get();

        $this->rentalPaid = $this->existingPayments
            ->where('payment_type', 'rental_fee')
            ->sum('amount');

        $this->remainingBalance = $this->totalPrice - $this->rentalPaid;
    }


    public function submitPayment()
    {
        $this->validate();

        try {
            $receiptPath = null;

            if ($this->receipt) {
                $filename = "payment_receipt_{$this->contractId}_" . time() . '.' . $this->receipt->getClientOriginalExtension();
                $receiptPath = $this->receipt->storeAs('payment_receipts', $filename, 'myimage');
            }

            Payment::create([
                'contract_id' => $this->contractId,
                'customer_id' => $this->customerId,
                'user_id' => Auth::id(), 
                'amount' => $this->amount,
                'currency' => $this->currency,
                'payment_type' => $this->payment_type,
                'payment_date' => Carbon::parse($this->payment_date)->format('Y-m-d'),
                'is_paid' => false,
                'is_refundable' => $this->is_refundable,
                'rate' => $this->currency !== 'AED' ? $this->rate : null,
                'receipt' => $receiptPath,
            ]);

            session()->flash('message', 'Payment was successfully added!');
            $this->resetForm();
            $this->loadData();
            $this->dispatch('payment-updated');
        } catch (\Exception $e) {
            session()->flash('error', 'Error adding payment: ' . $e->getMessage());
        }
    }


    public function resetForm()
    {
        $this->amount = '';
        $this->currency = 'AUD';
        $this->payment_type = '';
        $this->payment_date = '';
        $this->rate = '';
        $this->receipt = '';
        $this->is_refundable = false;
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-payment');
    }
}
