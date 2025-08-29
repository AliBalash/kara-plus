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

    protected $queryString = ['search', 'statusFilter', 'currencyFilter', 'paymentTypeFilter', 'dateFrom', 'dateTo'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'statusFilter', 'currencyFilter', 'paymentTypeFilter', 'dateFrom', 'dateTo']);
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
        $payments = Payment::query()
            ->with(['customer', 'contract', 'car'])
            ->when($this->search, function ($q) {
                $search = $this->search;

                if (is_numeric($search)) {
                    // اگر عدد بود، روی شماره قرارداد سرچ کن
                    $q->whereHas('contract', function ($q2) use ($search) {
                        $q2->where('id', $search);
                    });
                } else {
                    // اگر رشته بود، روی last_name مشتری سرچ کن
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
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.pages.panel.expert.payments.confirm-payement-list', compact('payments'));
    }
}
