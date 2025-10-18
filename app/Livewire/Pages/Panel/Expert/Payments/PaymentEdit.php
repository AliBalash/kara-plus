<?php

namespace App\Livewire\Pages\Panel\Expert\Payments;

use App\Models\Payment;
use App\Models\Contract;
use App\Services\Media\OptimizedUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Carbon\Carbon;

class PaymentEdit extends Component
{
    use WithFileUploads;

    public Payment $payment;
    public $paymentId;
    public $contractId;

    public $amount;
    public $currency = 'AED';
    public $rate;
    public $payment_type;
    public $payment_method = 'cash';
    public $payment_date;
    public $is_refundable = false;
    public $receipt;
    public $existingReceipt;

    protected OptimizedUploadService $imageUploader;

    public function boot(OptimizedUploadService $imageUploader): void
    {
        $this->imageUploader = $imageUploader;
    }

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
        $types = implode(',', self::PAYMENT_TYPES);

        return [
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:IRR,USD,AED,EUR',
            'payment_type' => 'required|in:' . $types,
            'payment_method' => 'required|in:cash,transfer,ticket',
            'payment_date' => 'required|date',
            'is_refundable' => 'required|boolean',
            'rate' => 'nullable|numeric|min:0.0001',
            'receipt' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    public function mount($paymentId)
    {
        $this->paymentId = $paymentId;
        $this->payment = Payment::with('contract')->findOrFail($paymentId);

        $this->authorizeUser();

        $this->contractId = $this->payment->contract_id;
        $this->amount = $this->payment->amount;
        $this->currency = $this->payment->currency;
        $this->rate = $this->payment->rate;
        $this->payment_type = $this->payment->payment_type;
        $this->payment_method = $this->payment->payment_method;
        $this->payment_date = $this->payment->payment_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->is_refundable = (bool) $this->payment->is_refundable;
        $this->existingReceipt = $this->payment->receipt;
    }

    private function authorizeUser(): void
    {
        if (!Auth::check()) {
            abort(403);
        }

        // if ($this->payment->contract && $this->payment->contract->user_id && $this->payment->contract->user_id !== Auth::id()) {
        //     abort(403, 'You are not allowed to edit this payment.');
        // }
    }

    public function updatedCurrency(string $value): void
    {
        if ($value === 'AED') {
            $this->rate = null;
        }
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

    public function updatePayment()
    {
        $this->validate();

        if ($this->currency !== 'AED' && empty($this->rate)) {
            $this->addError('rate', 'Exchange rate is required for non-AED currencies.');
            return;
        }

        if (in_array($this->payment_type, ['fine', 'parking', 'damage']) && !$this->existingReceipt && !$this->receipt) {
            $this->addError('receipt', 'Receipt is required for fines, parking, or damage charges.');
            return;
        }

        $aedAmount = match ($this->currency) {
            'AED' => $this->amount,
            'IRR' => round($this->amount / $this->rate, 2),
            default => round($this->amount * $this->rate, 2),
        };

        $receiptPath = $this->existingReceipt;

        if ($this->receipt) {
            if ($this->existingReceipt && Storage::disk('myimage')->exists($this->existingReceipt)) {
                Storage::disk('myimage')->delete($this->existingReceipt);
            }

            $baseName = "payment_receipt_{$this->payment->contract_id}_" . time();
            $receiptPath = $this->imageUploader->store(
                $this->receipt,
                "payment_receipts/{$baseName}.webp",
                'myimage',
                ['quality' => 30, 'max_width' => 1600, 'max_height' => 1600]
            );
        }

        $this->payment->update([
            'amount' => $this->amount,
            'currency' => $this->currency,
            'rate' => $this->currency !== 'AED' ? $this->rate : null,
            'amount_in_aed' => $aedAmount,
            'payment_type' => $this->payment_type,
            'payment_method' => $this->payment_method,
            'payment_date' => Carbon::parse($this->payment_date)->format('Y-m-d'),
            'is_refundable' => $this->is_refundable,
            'receipt' => $receiptPath,
        ]);

        session()->flash('success', 'Payment updated successfully.');

        return redirect()->route('rental-requests.payment', [$this->payment->contract_id, $this->payment->customer_id]);
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.payments.payment-edit', [
            'payment' => $this->payment,
            'contract' => $this->payment->contract ?? Contract::find($this->contractId),
        ]);
    }
}
