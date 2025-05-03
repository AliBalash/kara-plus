<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;

class RentalRequestPaymentList extends Component
{
    public $paymentContracts;
    public $search = '';  // متغیر جستجو

    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    public function mount()
    {
        // فقط قراردادهایی که در وضعیت 'payment' هستند و حداقل یک پرداخت مرتبط دارند
        $this->paymentContracts = Contract::where('current_status', 'payment')
            ->latest()
            ->get();
    }

    public function updatedSearch()
    {
        $this->paymentContracts = Contract::query()
            ->where('current_status', 'payment')
            ->whereHas('payments') // فقط قراردادهایی که پرداختی دارند
            ->where(function ($query) {
                $query->whereHas('customer', function ($query) {
                    $query->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%');
                })
                    ->orWhere('id', 'like', '%' . $this->search . '%');
            })
            ->latest() // Order by created_at DESC
            ->get();
    }

    public function changeStatusToComplete($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        // تغییر وضعیت قرارداد به 'complete'
        $contract->changeStatus('complete', auth()->id());
        session()->flash('success', 'Status changed to complete successfully.');
    }

    public function render()
    {
        return view(
            'livewire.pages.panel.expert.rental-request.rental-request-payment-list',
            [
                'contracts' => $this->paymentContracts
            ]
        );
    }
}
