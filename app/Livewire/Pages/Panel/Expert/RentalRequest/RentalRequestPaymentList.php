<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use Livewire\Component;
use App\Livewire\Concerns\HandlesContractCancellation;

class RentalRequestPaymentList extends Component
{
    use HandlesContractCancellation;

    public $paymentContracts;
    public $search = '';
    public $searchInput = '';

    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    public function mount()
    {
        $this->searchInput = $this->search;
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
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $this->paymentContracts = Contract::query()
            ->where('current_status', 'payment')
            ->when($search !== '', function ($query) use ($likeSearch) {
                $query->where(function ($q) use ($likeSearch) {
                    $q->whereHas('customer', function ($customerQuery) use ($likeSearch) {
                        $customerQuery->where('first_name', 'like', $likeSearch)
                            ->orWhere('last_name', 'like', $likeSearch);
                    })->orWhere('contracts.id', 'like', $likeSearch);
                });
            })
            ->with(['payments', 'customer', 'car']) // Eager load relationships
            ->latest()
            ->get();

        return view('livewire.pages.panel.expert.rental-request.rental-request-payment-list', [
            'contracts' => $this->paymentContracts
        ]);
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
    }
}
