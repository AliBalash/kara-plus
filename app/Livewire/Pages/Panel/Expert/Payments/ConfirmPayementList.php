<?php

namespace App\Livewire\Pages\Panel\Expert\Payments;

use Livewire\Component;
use App\Models\Payment;
use App\Livewire\Concerns\InteractsWithToasts;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

class ConfirmPayementList extends Component
{
    use WithPagination;
    use InteractsWithToasts;

    public $search = '';
    public $searchInput = '';
    public $statusFilter = '';
    public $currencyFilter = '';
    public $paymentTypeFilter = '';
    public $dateFrom;
    public $dateTo;
    public $page;
    public $openAccordions = [];

    protected $queryString = ['search', 'statusFilter', 'currencyFilter', 'paymentTypeFilter', 'dateFrom', 'dateTo'];
    
    public function mount(): void
    {
        $this->searchInput = $this->search;
        $this->page = max(1, (int) ($this->page ?? 1));
    }

    public function clearFilters()
    {
        $this->reset(['search', 'statusFilter', 'currencyFilter', 'paymentTypeFilter', 'dateFrom', 'dateTo']);
        $this->searchInput = '';
        $this->page = 1;
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
        $payment->approval_status = 'approved';
        $payment->save();

        $this->toast('success', "Payment #{$paymentId} approved successfully.");
    }

    public function reject($paymentId)
    {
        $payment = Payment::findOrFail($paymentId);
        $payment->is_paid = false;
        $payment->approval_status = 'rejected';
        $payment->save();

        $this->toast('error', "Payment #{$paymentId} rejected.");
    }

    public function deletePayment($paymentId)
    {
        $payment = Payment::findOrFail($paymentId);

        if ($payment->receipt) {
            Storage::disk('myimage')->delete($payment->receipt);
        }

        $payment->delete();

        $this->toast('success', "Payment #{$paymentId} deleted successfully.");
    }

    public function render()
    {
        $search = trim($this->search);
        $isNumericSearch = is_numeric($search);

        $query = Payment::query()
            ->with(['customer', 'contract', 'car'])
            ->when($search !== '', function ($q) use ($search, $isNumericSearch) {
                $likeSearch = '%' . $search . '%';

                if ($isNumericSearch) {
                    $numeric = (int) $search;
                    $q->whereHas('contract', function ($q2) use ($numeric) {
                        $q2->where('id', $numeric);
                    });
                } else {
                    $q->whereHas('customer', function ($q2) use ($likeSearch) {
                        $q2->where('last_name', 'like', $likeSearch);
                    });
                }
            })
            ->when($this->statusFilter !== '', fn($q) => $q->where('approval_status', $this->statusFilter))
            ->when($this->currencyFilter, fn($q) => $q->where('currency', $this->currencyFilter))
            ->when($this->paymentTypeFilter, fn($q) => $q->where('payment_type', $this->paymentTypeFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('payment_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('payment_date', '<=', $this->dateTo))
            ->orderByDesc('id');

        // Group payments by contract_id
        $groupedPayments = $query->get()->groupBy('contract_id');
        $currentPage = max(1, (int) ($this->page ?? 1));
        $groupedPayments = new \Illuminate\Pagination\LengthAwarePaginator(
            $groupedPayments->forPage($currentPage, 10),
            $groupedPayments->count(),
            10,
            $currentPage,
            ['path' => url()->current()]
        );

        return view('livewire.pages.panel.expert.payments.confirm-payement-list', [
            'groupedPayments' => $groupedPayments
        ]);
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->page = 1;
    }
}
