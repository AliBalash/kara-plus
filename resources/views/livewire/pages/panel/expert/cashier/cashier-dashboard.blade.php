<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
        <div>
            <h4 class="fw-bold mb-1">Cashier</h4>
            <p class="text-muted mb-0">Approved cash receipts credited to the company cash desk.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="fw-semibold text-muted d-block mb-2">Cash on Hand (AED)</span>
                    <h3 class="mb-2">{{ number_format($totalCashAed, 2) }}</h3>
                    <small class="text-muted">Total approved cash across all receipts.</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="fw-semibold text-muted d-block mb-2">Today</span>
                    <h3 class="mb-2">{{ number_format($todayCashAed, 2) }}</h3>
                    <small class="text-muted">Cash approved on {{ now()->format('d M Y') }}.</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="fw-semibold text-muted d-block mb-2">This Week</span>
                    <h3 class="mb-2">{{ number_format($weeklyCashAed, 2) }}</h3>
                    <small class="text-muted">Approvals from {{ \Carbon\Carbon::now()->startOfWeek()->format('d M') }} to {{ \Carbon\Carbon::now()->endOfWeek()->format('d M') }}.</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="fw-semibold text-muted d-block mb-2">Approved Receipts</span>
                    <h3 class="mb-2">{{ number_format($totalReceipts) }}</h3>
                    <small class="text-muted">Cash-only receipts marked as approved.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8 order-2 order-lg-1">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header d-flex flex-column flex-md-row gap-2 gap-md-0 align-items-md-center justify-content-between">
                    <div>
                        <h5 class="mb-0">Approved Cash Receipts</h5>
                        <small class="text-muted">Filtered total: {{ number_format($filteredTotalAed, 2) }} AED</small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" wire:click="clearFilters">Clear Filters</button>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-4">
                            <form class="input-group" wire:submit.prevent="applySearch">
                                <span class="input-group-text"><i class="bx bx-search"></i></span>
                                <input type="search" class="form-control"
                                    placeholder="Search by customer, contract or note"
                                    wire:model.defer="searchInput">
                                <button class="btn btn-primary" type="submit" wire:loading.attr="disabled"
                                    wire:target="applySearch">
                                    <span wire:loading.remove wire:target="applySearch">Search</span>
                                    <span wire:loading wire:target="applySearch">...</span>
                                </button>
                            </form>
                        </div>
                        <div class="col-6 col-md-3">
                            <select class="form-select" wire:model.live="currencyFilter">
                                <option value="">All currencies</option>
                                <option value="AED">AED</option>
                                <option value="USD">USD</option>
                                <option value="IRR">IRR</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="date" class="form-control" wire:model.live="dateFrom" placeholder="From">
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="date" class="form-control" wire:model.live="dateTo" placeholder="To">
                        </div>
                        <div class="col-6 col-md-1">
                            <select class="form-select" wire:model.live="perPage">
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>

                    <div class="position-relative">
                        <div wire:loading.flex wire:loading.class.remove="d-none" wire:target="search, currencyFilter, dateFrom, dateTo, perPage, page" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 rounded align-items-center justify-content-center d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>

                        <div class="table-responsive text-nowrap">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Contract</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Currency</th>
                                        <th>AED</th>
                                        <th>Type</th>
                                        <th>Approved On</th>
                                        <th>Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($payments as $payment)
                                        <tr>
                                            <td>{{ $payment->id }}</td>
                                            <td>
                                                <span class="fw-semibold">#{{ $payment->contract_id }}</span>
                                                <div class="text-muted small">{{ $payment->contract?->car?->fullName() ?? '-' }}</div>
                                            </td>
                                            <td>
                                                {{ $payment->customer?->fullName() ?? '-' }}
                                                <div class="text-muted small">{{ $payment->customer?->phone ?? '' }}</div>
                                            </td>
                                            <td>{{ number_format($payment->amount, 2) }}</td>
                                            <td>{{ $payment->currency }}</td>
                                            <td>{{ number_format($payment->amount_in_aed, 2) }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $payment->payment_type)) }}</td>
                                            <td>{{ $payment->payment_date ? \Illuminate\Support\Carbon::parse($payment->payment_date)->format('d M Y') : '-' }}</td>
                                            <td>
                                                @if ($payment->receipt)
                                                    <a href="{{ asset('storage/' . ltrim($payment->receipt, '/')) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        View
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if (!empty($payment->description))
                                            <tr class="bg-light">
                                                <td colspan="9" class="text-muted small">
                                                    <strong>Note:</strong> {{ $payment->description }}
                                                </td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">No approved cash receipts match your filters.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} receipts</small>
                        {{ $payments->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 order-1 order-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0">Currency Breakdown</h5>
                </div>
                <div class="card-body">
                    @forelse ($currencyBreakdown as $breakdown)
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <span class="fw-semibold">{{ $breakdown->currency }}</span>
                                <div class="text-muted small">{{ $breakdown->receipts_count }} receipts</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold">{{ number_format($breakdown->total_amount, 2) }} {{ $breakdown->currency }}</div>
                                <small class="text-muted">{{ number_format($breakdown->total_aed, 2) }} AED</small>
                            </div>
                        </div>
                        <hr class="my-2">
                    @empty
                        <p class="text-muted mb-0">No cash receipts recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
