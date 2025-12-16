<?php

namespace App\Livewire\Pages\Panel\Expert\Payments;

use Livewire\Component;
use App\Models\ContractBalanceTransfer;
use App\Models\Payment;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\SearchesCustomerPhone;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ConfirmPayementList extends Component
{
    use WithPagination;
    use InteractsWithToasts;
    use SearchesCustomerPhone;

    public $search = '';
    public $searchInput = '';
    public $statusFilter = '';
    public $currencyFilter = '';
    public $paymentTypeFilter = '';
    public $dateFrom;
    public $dateTo;
    public $openAccordions = [];

    protected $queryString = [
        'search',
        'statusFilter',
        'currencyFilter',
        'paymentTypeFilter',
        'dateFrom',
        'dateTo',
        'page' => ['except' => 1],
    ];
    
    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    public function clearFilters()
    {
        $this->reset(['search', 'statusFilter', 'currencyFilter', 'paymentTypeFilter', 'dateFrom', 'dateTo']);
        $this->searchInput = '';
        $this->resetPage();
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

    protected function statusMeta(): array
    {
        return [
            'pending' => ['label' => 'Pending', 'bg' => 'warning', 'text' => 'dark'],
            'approved' => ['label' => 'Approved', 'bg' => 'success', 'text' => 'white'],
            'rejected' => ['label' => 'Rejected', 'bg' => 'danger', 'text' => 'white'],
        ];
    }

    public function render()
    {
        $statusMeta = $this->statusMeta();
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';
        $isNumericSearch = is_numeric($search);
        $isPhoneSearch = $this->isCustomerPhoneSearch($search);

        $baseQuery = Payment::query()
            ->when($search !== '', function ($q) use ($search, $isNumericSearch, $isPhoneSearch, $likeSearch) {
                if ($isPhoneSearch) {
                    $q->whereHas('customer', function ($q2) use ($likeSearch) {
                        $q2->where('phone', 'like', $likeSearch);
                    });

                    return;
                }

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
            ->when($this->dateTo, fn($q) => $q->whereDate('payment_date', '<=', $this->dateTo));

        $contractsPaginator = (clone $baseQuery)
            ->select('contract_id')
            ->groupBy('contract_id')
            ->orderByDesc(DB::raw('MAX(id)'))
            ->paginate(10, ['contract_id']);

        $payments = (clone $baseQuery)
            ->with(['customer', 'contract', 'car'])
            ->whereIn('contract_id', $contractsPaginator->pluck('contract_id'))
            ->orderByDesc('id')
            ->get()
            ->groupBy('contract_id');

        $groupedPaymentsCollection = $contractsPaginator->getCollection()->mapWithKeys(
            fn($contract) => [
                $contract->contract_id => $payments->get($contract->contract_id, collect())
            ]
        );

        $contractIds = $groupedPaymentsCollection->keys()->all();
        $transferSnapshots = $this->buildTransferSnapshots($contractIds);

        $groupedPayments = new \Illuminate\Pagination\LengthAwarePaginator(
            $groupedPaymentsCollection,
            $contractsPaginator->total(),
            $contractsPaginator->perPage(),
            $contractsPaginator->currentPage(),
            [
                'path' => $contractsPaginator->path(),
                'pageName' => $contractsPaginator->getPageName(),
            ]
        );

        return view('livewire.pages.panel.expert.payments.confirm-payement-list', [
            'groupedPayments' => $groupedPayments,
            'statusMeta' => $statusMeta,
            'transferSnapshots' => $transferSnapshots,
        ]);
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    protected function buildTransferSnapshots(array $contractIds): array
    {
        if (empty($contractIds)) {
            return [];
        }

        $snapshots = [];

        foreach ($contractIds as $contractId) {
            $snapshots[$contractId] = [
                'incoming' => 0.0,
                'outgoing' => 0.0,
                'net' => 0.0,
                'recent' => [],
            ];
        }

        $transfers = ContractBalanceTransfer::query()
            ->where(function ($query) use ($contractIds) {
                $query->whereIn('from_contract_id', $contractIds)
                    ->orWhereIn('to_contract_id', $contractIds);
            })
            ->orderByDesc('transferred_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        foreach ($transfers as $transfer) {
            $incomingContract = $transfer->to_contract_id;
            $outgoingContract = $transfer->from_contract_id;

            if ($incomingContract && isset($snapshots[$incomingContract])) {
                $snapshots[$incomingContract]['incoming'] += (float) $transfer->amount;
                $snapshots[$incomingContract]['recent'][] = [
                    'id' => $transfer->id,
                    'direction' => 'incoming',
                    'amount' => (float) $transfer->amount,
                    'reference' => $transfer->reference,
                    'notes' => $transfer->notes,
                    'meta' => $transfer->meta ?? [],
                    'timestamp' => optional($transfer->transferred_at ?? $transfer->created_at)->format('d M Y · H:i'),
                ];
            }

            if ($outgoingContract && isset($snapshots[$outgoingContract])) {
                $snapshots[$outgoingContract]['outgoing'] += (float) $transfer->amount;
                $snapshots[$outgoingContract]['recent'][] = [
                    'id' => $transfer->id,
                    'direction' => 'outgoing',
                    'amount' => (float) $transfer->amount,
                    'reference' => $transfer->reference,
                    'notes' => $transfer->notes,
                    'meta' => $transfer->meta ?? [],
                    'timestamp' => optional($transfer->transferred_at ?? $transfer->created_at)->format('d M Y · H:i'),
                ];
            }
        }

        foreach ($snapshots as $contractId => $data) {
            $incoming = round($data['incoming'], 2);
            $outgoing = round($data['outgoing'], 2);

            $snapshots[$contractId]['incoming'] = $incoming;
            $snapshots[$contractId]['outgoing'] = $outgoing;
            $snapshots[$contractId]['net'] = round($incoming - $outgoing, 2);
            $snapshots[$contractId]['recent'] = array_slice($data['recent'], 0, 3);
        }

        return $snapshots;
    }
}
