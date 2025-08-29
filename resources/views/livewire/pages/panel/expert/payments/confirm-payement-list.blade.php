<div class="card">
    <h5 class="card-header">Payments</h5>

    <div class="row p-3">
        <!-- Search -->
        <div class="col-md-3 mb-2">
            <div class="input-group">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="text" class="form-control" placeholder="Search by Contract ID or Customer Last Name..."
                    wire:model.live.debounce.1000ms="search">
            </div>
        </div>


        <!-- Status Filter -->
        <div class="col-md-2 mb-2">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="">All Status</option>
                <option value="1">Approved</option>
                <option value="0">Pending/Rejected</option>
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
                <option value="prepaid_fine">Prepaid Fine</option>
                <option value="toll">Toll</option>
                <option value="fine">Fine</option>
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

    <!-- Table -->
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Contract</th>
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
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->id }}</td>
                        <td>{{ $payment->customer?->fullName() ?? '-' }}</td>
                        <td>#{{ $payment->contract_id }}</td>
                        <td>{{ $payment->contract?->car?->fullName() ?? '-' }}</td>
                        <td>{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->currency }}</td>
                        <td>{{ ucfirst($payment->payment_type) }}</td>
                        <td>{{ $payment->payment_date }}</td>
                        <td>
                            @if ($payment->is_paid)
                                <span class="badge bg-success">Approved</span>
                            @else
                                <span class="badge bg-warning">Pending</span>
                            @endif
                        </td>
                        <td>
                            @if ($payment->receipt)
                                <a href="{{ asset('storage/' . $payment->receipt) }}" target="_blank">View</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-success"
                                wire:click="approve({{ $payment->id }})">Approve</button>
                            <button class="btn btn-sm btn-danger"
                                wire:click="reject({{ $payment->id }})">Reject</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center">No payments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $payments->links() }}</div>
</div>
