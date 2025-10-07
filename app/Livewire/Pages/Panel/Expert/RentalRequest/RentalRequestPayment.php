<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use Livewire\Component;
use App\Models\Payment;
use App\Models\Contract;
use App\Models\CustomerDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class RentalRequestPayment extends Component
{

    use WithFileUploads;
    public $contractId;
    public $customerId;
    public $amount;
    public $currency = 'AED';
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
    public $finePaid;
    public $parkingPaid;
    public $damagePaid;
    public $salik;
    public $discounts;
    public $security_deposit;
    public $rate;
    public $security_note = '';
    public $contract;
    public $contractMeta = [];
    public $payment_method = 'cash';




    protected $rules = [
        'amount' => 'required|numeric|min:0',
        'currency' => 'required|in:IRR,USD,AED,EUR',
        'payment_type' => 'required|in:rental_fee,security_deposit,salik,fine,parking,damage,discount',
        'payment_date' => 'required|date',
        'payment_method' => 'required|in:cash,transfer,ticket',
        'is_refundable' => 'required|boolean',
        'rate' => 'nullable|numeric|min:0.0001',
        'receipt' => 'nullable|image|max:2048', // optional receipt image
    ];

    public function mount($contractId, $customerId)
    {
        $this->contractId = $contractId;
        $this->customerId = $customerId;
        $this->hasCustomerDocument = CustomerDocument::where('customer_id', $this->customerId)
            ->where('contract_id', $this->contractId)
            ->exists();

        $this->loadData();
    }

    public function loadData()
    {
        $this->contract = Contract::with('payments')->findOrFail($this->contractId);
        $this->contractMeta = $this->contract->meta ?? [];
        $this->totalPrice = $this->contract->total_price ?? 0;

        $allPayments = $this->contract->payments;
        $this->existingPayments = $allPayments->where('customer_id', $this->customerId);
        $this->hasPayments = $allPayments->isNotEmpty();

        $this->rentalPaid = $this->existingPayments
            ->where('payment_type', 'rental_fee')
            ->sum('amount_in_aed');

        $this->finePaid = $this->existingPayments
            ->where('payment_type', 'fine')
            ->sum('amount_in_aed');

        $this->parkingPaid = $this->existingPayments
            ->where('payment_type', 'parking')
            ->sum('amount_in_aed');

        $this->damagePaid = $this->existingPayments
            ->where('payment_type', 'damage')
            ->sum('amount_in_aed');

        $this->salik = $this->existingPayments
            ->where('payment_type', 'salik')
            ->sum('amount_in_aed');

        $this->discounts = $this->existingPayments
            ->where('payment_type', 'discount')
            ->sum('amount_in_aed');

        $this->security_deposit = $this->existingPayments
            ->where('payment_type', 'security_deposit')
            ->sum('amount_in_aed');

        $this->remainingBalance = $this->contract->calculateRemainingBalance($allPayments);
    }



    public function submitPayment()
    {
        $this->validate();
        if ($this->currency !== 'AED' && empty($this->rate)) {
            $this->addError('rate', 'Exchange rate is required for non-AED currencies.');
            return;
        }

        if (in_array($this->payment_type, ['fine', 'parking', 'damage']) && !$this->receipt) {
            $this->addError('receipt', 'Receipt is required for fines, parking, or damage charges.');
            return;
        }

        $aedAmount = match ($this->currency) {
            'AED' => $this->amount,
            'IRR' => round($this->amount / $this->rate, 2), // ریال باید تقسیم بشه
            default => round($this->amount * $this->rate, 2), // USD, EUR و سایر ارزها ضرب میشن
        };


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
                'rate' => $this->currency !== 'AED' ? $this->rate : null,
                'amount_in_aed' => $aedAmount,
                'payment_type' => $this->payment_type,
                'payment_method' => $this->payment_method, // 👈 اضافه شد
                'payment_date' => Carbon::parse($this->payment_date)->format('Y-m-d'),
                'is_paid' => false,
                'is_refundable' => $this->is_refundable,
                'receipt' => $receiptPath,
                'approval_status' => 'pending',
            ]);

            session()->flash('message', 'Payment was successfully added!');
            $this->resetForm();
            $this->loadData();
            $this->dispatch('payment-updated');
        } catch (\Exception $e) {
            session()->flash('error', 'Error adding payment: ' . $e->getMessage());
        }
    }

    public function submitDeposit()
    {
        if (!empty($this->security_note)) {
            $contract = Contract::find($this->contractId);
            if (!$contract) {
                session()->flash('error', 'Contract not found.');
                return;
            }

            $meta = $contract->meta ?? [];
            $meta['security_deposit_note'] = $this->security_note;

            $contract->meta = $meta;
            $contract->save();

            // هم در دیتابیس ذخیره شد، هم برای ویو آپدیت شد
            $this->contractMeta = $meta;

            session()->flash('message', 'Security deposit information was successfully saved.');
            $this->security_note = '';
        }
    }



    public function resetForm()
    {
        $this->amount = '';
        $this->currency = 'AED';
        $this->payment_type = '';
        $this->payment_date = '';
        $this->payment_method = 'cash';
        $this->rate = '';
        $this->receipt = '';
        $this->is_refundable = false;
    }

    public function deletePayment($paymentId)
    {
        $payment = Payment::where('id', $paymentId)
            ->where('contract_id', $this->contractId)
            ->where('customer_id', $this->customerId)
            ->firstOrFail();

        if ($payment->receipt) {
            Storage::disk('myimage')->delete($payment->receipt);
        }

        $payment->delete();

        session()->flash('message', 'Payment deleted successfully.');
        $this->loadData();
        $this->dispatch('payment-updated');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-payment');
    }
}
