<div class="container">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Rental Request /</span> Payment Information
    </h4>

    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    @if (session()->has('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <ul class="nav nav-pills flex-column flex-md-row mb-3">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('rental-requests.form') ? 'active' : '' }}"
                href="{{ route('rental-requests.form', $contractId) }}">
                <i class="bx bxs-info-square me-1"></i> Rental Information
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('customer.documents') ? 'active' : '' }}"
                href="{{ route('customer.documents', [$contractId, $customerId]) }}">
                <i class="bx bx-file me-1"></i> Customer Document
                @if ($hasCustomerDocument)
                    ✔
                @endif
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('rental-requests.payment') ? 'active' : '' }}"
                href="{{ route('rental-requests.payment', [$contractId, $customerId]) }}">
                <i class="bx bx-money me-1"></i> Payment
                @if ($hasPayments)
                    ✔
                @endif
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('rental-requests.history') ? 'active' : '' }}"
                href="{{ route('rental-requests.history', $contractId) }}">
                <i class="bx bx-history me-1"></i> History
            </a>
        </li>
    </ul>


    <div class="card">
        <h5 class="card-header">Make a Payment</h5>
        <div class="card-body">
            <form wire:submit.prevent="submitPayment">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class="form-control" wire:model="amount" step="0.01">
                        @error('amount')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Payment Type</label>
                        <select class="form-control" wire:model="payment_type">
                            <option value="">Select Payment Type</option>
                            <option value="rental_fee">Rental Fee</option>
                            <option value="fine">Fine</option>
                        </select>
                        @error('payment_type')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Payment Date</label>
                        <input type="date" class="form-control" wire:model="payment_date">
                        @error('payment_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Submit Payment</button>
            </form>
        </div>
    </div>

    <div class="mt-4"></div>

    <!-- Display the total price and remaining balance based on rental fees -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Payment Overview</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="alert alert-info text-center">
                        <strong>Total Price:</strong> <span class="h5">{{ number_format($totalPrice, 2) }}
                            $</span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="alert alert-success text-center">
                        <strong>Total Paid (Rental Fee):</strong> <span
                            class="h5">{{ number_format($rentalPaid, 2) }} $</span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="alert alert-warning text-center">
                        <strong>Remaining Balance:</strong> <span
                            class="h5">{{ number_format($remainingBalance, 2) }} $</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Display the fines, if any -->
    @if ($fines->count())
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Fines Overview</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($fines as $fine)
                                <tr>
                                    <td>{{ number_format($fine->amount, 2) }} $</td>
                                    <td>{{ $fine->description }}</td>
                                    <td>{{ \Carbon\Carbon::parse($fine->fine_date)->format('d M Y') }}</td>
                                    <td>
                                        @if ($fine->is_paid)
                                            <span class="badge bg-success">Paid</span>
                                        @else
                                            <span class="badge bg-danger">Unpaid</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <strong>No fines recorded.</strong>
        </div>
    @endif

    <!-- Existing Payments Section -->
    <h5 class="mt-4 mb-3">Existing Payments</h5>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Amount</th>
                    <th>Payment Type</th>
                    <th>Payment Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($existingPayments as $payment)
                    <tr>
                        <td>{{ number_format($payment->amount, 2) }} $</td>
                        <td>{{ ucfirst($payment->payment_type) }}</td>
                        <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">No payments found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
