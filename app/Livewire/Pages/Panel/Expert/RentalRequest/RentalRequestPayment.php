<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\CustomerDocument;
use App\Models\Payment;
use App\Services\Media\OptimizedUploadService;
use App\Livewire\Concerns\InteractsWithToasts;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class RentalRequestPayment extends Component
{

    use WithFileUploads;
    use InteractsWithToasts;
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
    public $payment_back;
    public $carwash;
    public $fuel;
    public $effectivePaid;
    public $rate;
    public $security_note = '';
    public $contract;
    public $contractMeta = [];
    public $payment_method = 'cash';
    protected OptimizedUploadService $imageUploader;

    protected array $messages = [
        'amount.required' => 'Payment amount is required.',
        'amount.numeric' => 'Payment amount must be a number.',
        'amount.min' => 'Payment amount cannot be negative.',
        'currency.required' => 'Currency is required.',
        'currency.in' => 'Selected currency is not supported.',
        'payment_type.required' => 'Please choose a payment type.',
        'payment_type.in' => 'Selected payment type is invalid.',
        'payment_date.required' => 'Payment date is required.',
        'payment_date.date' => 'Please provide a valid payment date.',
        'payment_method.required' => 'Please choose a payment method.',
        'payment_method.in' => 'Selected payment method is invalid.',
        'is_refundable.required' => 'Please specify whether the payment is refundable.',
        'is_refundable.boolean' => 'Refundable selection must be yes or no.',
        'rate.numeric' => 'Exchange rate must be a number.',
        'rate.min' => 'Exchange rate must be greater than zero.',
        'receipt.image' => 'Receipt must be an image file.',
        'receipt.mimes' => 'Receipt must be a JPG, JPEG, PNG, or WEBP file.',
        'receipt.max' => 'Receipt may not be greater than 2MB.',
    ];

    protected array $validationAttributes = [
        'amount' => 'amount',
        'currency' => 'currency',
        'payment_type' => 'payment type',
        'payment_date' => 'payment date',
        'payment_method' => 'payment method',
        'is_refundable' => 'refundable selection',
        'rate' => 'exchange rate',
        'receipt' => 'receipt upload',
    ];




    public function boot(OptimizedUploadService $imageUploader): void
    {
        $this->imageUploader = $imageUploader;
    }

    private const PAYMENT_METHODS = ['cash', 'transfer', 'ticket'];
    private const PAYMENT_TYPES = [
        'rental_fee',
        'security_deposit',
        'salik',
        'fine',
        'parking',
        'damage',
        'discount',
        'payment_back',
        'carwash',
        'fuel',
    ];

    protected function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(['IRR', 'USD', 'AED', 'EUR'])],
            'payment_type' => ['required', Rule::in(self::PAYMENT_TYPES)],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(self::PAYMENT_METHODS)],
            'is_refundable' => ['required', 'boolean'],
            'rate' => ['nullable', 'numeric', 'min:0.0001'],
            'receipt' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // optional receipt image
        ];
    }

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

        $this->payment_back = $this->existingPayments
            ->where('payment_type', 'payment_back')
            ->sum('amount_in_aed');

        $this->carwash = $this->existingPayments
            ->where('payment_type', 'carwash')
            ->sum('amount_in_aed');

        $this->fuel = $this->existingPayments
            ->where('payment_type', 'fuel')
            ->sum('amount_in_aed');

        $this->effectivePaid = $this->rentalPaid - $this->payment_back;

        $this->remainingBalance = $this->contract->calculateRemainingBalance($allPayments);
    }

    public function getPaymentTypeOptionsProperty(): array
    {
        return [
            'rental_fee' => 'Rental Fee',
            'security_deposit' => 'Security Deposit',
            'salik' => 'Salik',
            'fine' => 'Fine',
            'parking' => 'Parking',
            'damage' => 'Damage',
            'discount' => 'Discount',
            'payment_back' => 'Payment Back',
            'carwash' => 'Carwash',
            'fuel' => 'Fuel',
        ];
    }



    public function submitPayment()
    {
        if (is_string($this->payment_method)) {
            $this->payment_method = strtolower(trim($this->payment_method));
        }

        if (! in_array($this->payment_method, self::PAYMENT_METHODS, true)) {
            $this->payment_method = self::PAYMENT_METHODS[0];
        }

        $this->validateWithScroll();
        if ($this->currency !== 'AED' && empty($this->rate)) {
            $this->addError('rate', 'Exchange rate is required for non-AED currencies.');
            $this->dispatch('kara-scroll-to-error', field: 'rate');
            return;
        }

        if (in_array($this->payment_type, ['fine', 'parking', 'damage']) && !$this->receipt) {
            $this->addError('receipt', 'Receipt is required for fines, parking, or damage charges.');
            $this->dispatch('kara-scroll-to-error', field: 'receipt');
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
                $baseName = "payment_receipt_{$this->contractId}_" . time();
                $receiptPath = $this->imageUploader->store(
                    $this->receipt,
                    "payment_receipts/{$baseName}.webp",
                    'myimage',
                    ['quality' => 30, 'max_width' => 1600, 'max_height' => 1600]
                );
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
                'payment_method' => $this->payment_method,
                'payment_date' => Carbon::parse($this->payment_date)->format('Y-m-d'),
                'is_paid' => false,
                'is_refundable' => $this->is_refundable,
                'receipt' => $receiptPath,
                'approval_status' => 'pending',
            ]);

            $this->toast('success', 'Payment was successfully added!');
            $this->resetForm();
            $this->loadData();
            $this->dispatch('payment-updated');
        } catch (\Exception $e) {
            $this->toast('error', 'Error adding payment: ' . $e->getMessage(), false);
        }
    }

    public function submitDeposit()
    {
        if (!empty($this->security_note)) {
            $contract = Contract::find($this->contractId);
            if (!$contract) {
                $this->toast('error', 'Contract not found.', false);
                return;
            }

            $meta = $contract->meta ?? [];
            $meta['security_deposit_note'] = $this->security_note;

            $contract->meta = $meta;
            $contract->save();

            // هم در دیتابیس ذخیره شد، هم برای ویو آپدیت شد
            $this->contractMeta = $meta;

            $this->toast('success', 'Security deposit information was successfully saved.');
            $this->security_note = '';
        }
    }

    private function validateWithScroll(?array $rules = null): array
    {
        try {
            return $this->validate($rules ?? $this->rules(), $this->messages, $this->validationAttributes);
        } catch (ValidationException $exception) {
            $this->dispatch('kara-scroll-to-error', field: $this->firstErrorField($exception));
            throw $exception;
        }
    }

    private function firstErrorField(ValidationException $exception): string
    {
        $errors = $exception->errors();
        $firstKey = array_key_first($errors);

        if (!is_string($firstKey) || $firstKey === '') {
            return '';
        }

        return Str::before($firstKey, '.');
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

        $this->toast('success', 'Payment deleted successfully.');
        $this->loadData();
        $this->dispatch('payment-updated');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-payment');
    }
}
