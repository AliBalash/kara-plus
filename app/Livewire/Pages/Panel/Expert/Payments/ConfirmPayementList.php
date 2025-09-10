<?php

namespace App\Livewire\Pages\Panel\Expert\Payments;

use Livewire\Component;
use App\Models\Payment;
use Livewire\WithPagination;

class ConfirmPayementList extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $currencyFilter = '';
    public $paymentTypeFilter = '';
    public $dateFrom;
    public $dateTo;
    public $page;
    public $openAccordions = [];

    protected $queryString = ['search', 'statusFilter', 'currencyFilter', 'paymentTypeFilter', 'dateFrom', 'dateTo'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'statusFilter', 'currencyFilter', 'paymentTypeFilter', 'dateFrom', 'dateTo']);
    }

    public function toggleAccordion($contractId)
    {
        if (in_array($contractId, $this->openAccordions)) {
            $this->openAccordions = array_diff($this->openAccordions, [$contractId]);
        } else {
            $this->openAccordions[] = $contractId;
        }
    }

    public function approve($paymentId)
    {
        $payment = Payment::findOrFail($paymentId);
        $payment->is_paid = true;
        $payment->save();

        session()->flash('success', "Payment #{$paymentId} approved successfully.");
    }

    public function reject($paymentId)
    {
        $payment = Payment::findOrFail($paymentId);
        $payment->is_paid = false;
        $payment->save();

        session()->flash('error', "Payment #{$paymentId} rejected.");
    }

    public function render()
    {
        $query = Payment::query()
            ->with(['customer', 'contract', 'car'])
            ->when($this->search, function ($q) {
                $search = $this->search;

                if (is_numeric($search)) {
                    $q->whereHas('contract', function ($q2) use ($search) {
                        $q2->where('id', $search);
                    });
                } else {
                    $q->whereHas('customer', function ($q2) use ($search) {
                        $q2->where('last_name', 'like', '%' . $search . '%');
                    });
                }
            })
            ->when($this->statusFilter !== '', fn($q) => $q->where('is_paid', $this->statusFilter))
            ->when($this->currencyFilter, fn($q) => $q->where('currency', $this->currencyFilter))
            ->when($this->paymentTypeFilter, fn($q) => $q->where('payment_type', $this->paymentTypeFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('payment_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('payment_date', '<=', $this->dateTo))
            ->orderByDesc('id');

        // Group payments by contract_id
        $groupedPayments = $query->get()->groupBy('contract_id');
        $groupedPayments = new \Illuminate\Pagination\LengthAwarePaginator(
            $groupedPayments->forPage($this->page, 10),
            $groupedPayments->count(),
            10,
            $this->page,
            ['path' => url()->current()]
        );

        return view('livewire.pages.panel.expert.payments.confirm-payement-list', [
            'groupedPayments' => $groupedPayments
        ]);
    }
}
