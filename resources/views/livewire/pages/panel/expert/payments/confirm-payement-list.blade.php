<div class="card">
    <h5 class="card-header">Payments (Grouped by Contract)</h5>

    <div class="row p-3">
        <!-- Search -->
        <div class="col-md-3 mb-2">
            <form class="input-group" wire:submit.prevent="applySearch">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="search" class="form-control"
                    placeholder="Search by Contract ID or Customer Last Name..." wire:model.defer="searchInput">
                <button class="btn btn-primary" type="submit" wire:loading.attr="disabled"
                    wire:target="applySearch">
                    <span wire:loading.remove wire:target="applySearch">Search</span>
                    <span wire:loading wire:target="applySearch">...</span>
                </button>
            </form>
        </div>

        <!-- Status Filter -->
        <div class="col-md-2 mb-2">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="">All Status</option>
                <option value="approved">Approved</option>
                <option value="pending">Pending</option>
                <option value="rejected">Rejected</option>
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
                <option value="salik">Salik</option>
                <option value="fine">Fine</option>
                <option value="parking">Parking</option>
                <option value="damage">Damage</option>
                <option value="discount">Discount</option>
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
            <button class="btn btn-secondary w-100" wire:click="clearFilters">Clear All Filters</button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Accordion for Grouped Payments -->
    <div class="accordion" id="paymentAccordion">
        @forelse($groupedPayments as $contractId => $paymentGroup)
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading{{ $contractId }}">
                    <button
                        class="accordion-button {{ in_array($contractId, $this->openAccordions) ? '' : 'collapsed' }}"
                        type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $contractId }}"
                        aria-expanded="{{ in_array($contractId, $this->openAccordions) ? 'true' : 'false' }}"
                        aria-controls="collapse{{ $contractId }}" wire:click="toggleAccordion({{ $contractId }})">
                        <strong>Contract #{{ $contractId }} - Customer:
                            {{ $paymentGroup[0]->customer?->fullName() ?? 'Unknown' }}</strong>
                        <span class="ms-3 badge bg-info">{{ count($paymentGroup) }} Payment(s)</span>
                    </button>
                </h2>
                <div id="collapse{{ $contractId }}"
                    class="accordion-collapse collapse {{ in_array($contractId, $this->openAccordions) ? 'show' : '' }}"
                    aria-labelledby="heading{{ $contractId }}" data-bs-parent="#paymentAccordion">
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
                                        <th>Actions</th>
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
                                            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}</td>
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
                                                    <a href="{{ asset('storage/' . $payment->receipt) }}"
                                                        target="_blank">View</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="d-flex flex-wrap gap-1">
                                                <a href="{{ route('payments.edit', $payment->id) }}"
                                                    class="btn btn-sm btn-outline-primary">Edit</a>
                                                <button class="btn btn-sm btn-outline-danger"
                                                    wire:click="deletePayment({{ $payment->id }})"
                                                    onclick="if(!confirm('Are you sure you want to delete this payment?')) { event.stopImmediatePropagation(); }">Delete</button>
                                                <button class="btn btn-sm btn-success"
                                                    wire:click="approve({{ $payment->id }})">Approve</button>
                                                <button class="btn btn-sm btn-warning text-dark"
                                                    wire:click="reject({{ $payment->id }})">Reject</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center p-3">No payments found.</div>
        @endforelse
    </div>

    <div class="mt-3">{{ $groupedPayments->links() }}</div>
</div>
