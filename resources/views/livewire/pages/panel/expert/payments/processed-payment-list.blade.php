<div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div>
            <h5 class="mb-1">Processed Payments</h5>
            <p class="mb-0 text-muted small">Browse the payments that have already been approved or rejected.</p>
        </div>
        <a href="{{ route('rental-requests.confirm-payment-list') }}" class="btn btn-outline-secondary mt-2 mt-md-0">
            Back to Confirmation Queue
        </a>
    </div>

    @php
        $summaryData = $summaryData ?? [];
        $statusMeta = $statusMeta ?? [
            'approved' => ['label' => 'Approved', 'bg' => 'success', 'text' => 'white'],
            'rejected' => ['label' => 'Rejected', 'bg' => 'danger', 'text' => 'white'],
        ];
    @endphp

    <div class="row g-3 px-3 pt-3">
        @foreach ($statusMeta as $status => $meta)
            @php($summary = $summaryData[$status] ?? ['count' => 0, 'total_amount' => 0, 'total_amount_aed' => 0])
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 border border-{{ $meta['bg'] }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-semibold text-{{ $meta['bg'] }} mb-2">{{ $meta['label'] }}</h6>
                                <div class="display-6 fw-bold">{{ $summary['count'] }}</div>
                                <p class="mb-0 text-muted small">Payments</p>
                            </div>
                            <span class="badge bg-{{ $meta['bg'] }} text-{{ $meta['text'] }}">{{ strtoupper($status) }}</span>
                        </div>
                        <hr>
                        <p class="mb-1 text-muted small">Total Amount</p>
                        <div class="fw-semibold">{{ number_format($summary['total_amount'], 2) }}</div>
                        <p class="mb-1 text-muted small mt-2">Amount (AED)</p>
                        <div class="fw-semibold">{{ number_format($summary['total_amount_aed'], 2) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row p-3">
        <!-- Search -->
        <div class="col-md-3 mb-2">
            <form class="input-group" wire:submit.prevent="applySearch">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="search" class="form-control" placeholder="Search by Contract ID or Customer Last Name..."
                    wire:model.defer="searchInput">
                <button class="btn btn-primary" type="submit" wire:loading.attr="disabled" wire:target="applySearch">
                    <span wire:loading.remove wire:target="applySearch">Search</span>
                    <span wire:loading wire:target="applySearch">...</span>
                </button>
            </form>
        </div>

        <!-- Status Filter -->
        <div class="col-md-2 mb-2">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="all">Approved &amp; Rejected</option>
            </select>
        </div>

        <!-- Currency Filter -->
        <div class="col-md-2 mb-2">
            <select class="form-select" wire:model.live="currencyFilter">
                <option value="">All Currencies</option>
                <option value="IRR">IRR</option>
                <option value="USD">USD</option>
                <option value="AED">AED</option>
            </select>
        </div>

        <!-- Payment Type Filter -->
        <div class="col-md-2 mb-2">
            <select class="form-select" wire:model.live="paymentTypeFilter">
                <option value="">All Types</option>
                <option value="rental_fee">Rental Fee</option>
                <option value="security_deposit">Security deposit</option>
                <option value="salik">Salik (Legacy)</option>
                <option value="salik_4_aed">Salik (4 AED)</option>
                <option value="salik_6_aed">Salik (6 AED)</option>
                <option value="salik_other_revenue">Salik Other Revenue (Auto)</option>
                <option value="fine">Fine</option>
                <option value="parking">Parking</option>
                <option value="damage">Damage</option>
                <option value="discount">Discount</option>
                <option value="payment_back">Payment Back</option>
                <option value="carwash">Carwash</option>
                <option value="fuel">Fuel</option>
            </select>
        </div>

        <!-- Date Filters -->
        <div class="col-md-2 mb-2">
            <input type="date" class="form-control" wire:model.live="dateFrom">
        </div>
        <div class="col-md-2 mb-2">
            <input type="date" class="form-control" wire:model.live="dateTo">
        </div>

        <!-- Clear All Filters -->
        <div class="col-md-2 mb-2">
            <button type="button" class="btn btn-secondary w-100" wire:click="clearFilters">Clear All Filters</button>
        </div>
    </div>

    <!-- Accordion for Grouped Payments -->
    <div class="accordion" id="processedPaymentAccordion">
        @forelse ($groupedPayments as $contractId => $paymentGroup)
            <div class="accordion-item">
                <h2 class="accordion-header" id="processedHeading{{ $contractId }}">
                    <button class="accordion-button {{ in_array($contractId, $this->openAccordions) ? '' : 'collapsed' }}"
                        type="button" data-bs-toggle="collapse" data-bs-target="#processedCollapse{{ $contractId }}"
                        aria-expanded="{{ in_array($contractId, $this->openAccordions) ? 'true' : 'false' }}"
                        aria-controls="processedCollapse{{ $contractId }}"
                        wire:click="toggleAccordion({{ $contractId }})">
                        @php($firstPayment = $paymentGroup->first())
                        <strong>Contract #{{ $contractId }} - Customer:
                            {{ $firstPayment?->customer?->fullName() ?? 'Unknown' }}</strong>
                        <span class="ms-3 badge bg-info">{{ count($paymentGroup) }} Payment(s)</span>
                    </button>
                </h2>
                <div id="processedCollapse{{ $contractId }}"
                    class="accordion-collapse collapse {{ in_array($contractId, $this->openAccordions) ? 'show' : '' }}"
                    aria-labelledby="processedHeading{{ $contractId }}" data-bs-parent="#processedPaymentAccordion">
                    <div class="accordion-body">
                        <div class="table-responsive text-nowrap">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Customer</th>
                                        <th>Car</th>
                                        <th>Amount</th>
                                        <th>Currency</th>
                                        <th>Type</th>
                                        <th>Payment Date</th>
                                        <th>Status</th>
                                        <th>Receipt</th>
                                        <th>Processed On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($paymentGroup as $payment)
                                        <tr>
                                            <td>{{ $payment->id }}</td>
                                            <td>{{ $payment->customer?->fullName() ?? '-' }}</td>
                                            <td>{{ $payment->contract?->car?->fullName() ?? '-' }}</td>
                                            <td>{{ number_format($payment->amount, 2) }}</td>
                                            <td>{{ $payment->currency }}</td>
                                            <td>
                                                {{ ucwords(str_replace('_', ' ', $payment->payment_type)) }}
                                                @if ($payment->isSalikBreakdownEntry())
                                                    <div class="small text-muted mt-1">
                                                        Trips: {{ $payment->salikTripCount() }},
                                                        Amount: {{ number_format($payment->salikBreakdownAmount(), 2) }} AED
                                                    </div>
                                                @elseif ($payment->payment_type === 'salik')
                                                    <div class="small text-muted mt-1">Legacy salik entry without breakdown</div>
                                                @endif
                                            </td>
                                            <td>{{ $payment->payment_date }}</td>
                                            <td>
                                                @switch($payment->approval_status)
                                                    @case('approved')
                                                        <span class="badge bg-success">Approved</span>
                                                    @break

                                                    @case('rejected')
                                                        <span class="badge bg-danger">Rejected</span>
                                                    @break

                                                    @default
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                @if ($payment->receipt)
                                                    <a href="{{ asset('storage/' . ltrim($payment->receipt, '/')) }}"
                                                        target="_blank">View</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ optional($payment->updated_at)->format('Y-m-d H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center p-3">No processed payments found.</div>
        @endforelse
    </div>

    <div class="mt-3">{{ $groupedPayments->links() }}</div>
</div>
